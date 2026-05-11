<?php
    
    declare(strict_types=1);
    
    namespace App\Services;
    
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\Http;
    
    /**
     * WoRMS (World Register of Marine Species) REST API Client.
     *
     * Provides read-only access to the Aphia taxonomy service.
     * All results are cached in Redis to reduce API load and improve latency.
     *
     * @see https://www.marinespecies.org/rest/
     */
    class WormsService
    {
        protected string $baseUrl = 'https://www.marinespecies.org/rest';
        
        /**
         * Get all Phyla from WoRMS grouped by Kingdom.
         *
         * Iterates over every Kingdom, fetches its direct children and keeps
         * only those whose rank is "Phylum". Results are grouped under each
         * Kingdom name with a phylum count (e.g. "Animalia (35)").
         *
         * Cached for 24 hours.
         *
         * @return array<string, array<int, string>> [Kingdom (count) => [AphiaID => scientific_name]]
         */
        public function getPhyla(): array
        {
            return Cache::remember('worms_v2.phyla', 86_400, function (): array {
                $kingdoms = $this->getKingdoms();
                $grouped = [];
                
                foreach ($kingdoms as $aphiaId => $name) {
                    $children = $this->fetchChildren($aphiaId);
                    $phyla = [];
                    
                    foreach ($children as $child) {
                        if (($child[ 'rank' ] ?? '') === 'Phylum') {
                            $phyla[ $child[ 'AphiaID' ] ] = $child[ 'scientificname' ];
                        }
                    }
                    
                    if (!empty($phyla)) {
                        asort($phyla);
                        $grouped[ "{$name} (".count($phyla).")" ] = $phyla;
                    }
                }
                
                return $grouped;
            });
        }
        
        /**
         * Get all Kingdoms from WoRMS (children of AphiaID 1 = Biota).
         *
         * Cached for 24 hours.
         *
         * @return array<int, string> [AphiaID => scientific_name]
         */
        public function getKingdoms(): array
        {
            return Cache::remember('worms_v2.kingdoms', 86_400, function (): array {
                $children = $this->fetchChildren(1);
                
                $kingdoms = [];
                foreach ($children as $child) {
                    if (($child[ 'rank' ] ?? '') === 'Kingdom') {
                        $kingdoms[ $child[ 'AphiaID' ] ] = $child[ 'scientificname' ];
                    }
                }
                
                asort($kingdoms);
                
                return $kingdoms;
            });
        }
        
        /**
         * Fetch direct children of a given AphiaID from WoRMS.
         *
         * @param  int  $aphiaId  WoRMS AphiaID
         *
         * @return array<int, array<string, mixed>>
         */
        protected function fetchChildren(int $aphiaId): array
        {
            $response = Http::timeout(15)
                ->get("{$this->baseUrl}/AphiaChildrenByAphiaID/{$aphiaId}", [
                    'marine_only' => 'true',
                    'offset' => 1,
                ]);
            
            if (!$response->successful()) {
                return [];
            }
            
            $data = $response->json();
            
            return is_array($data) ? $data : [];
        }
        
        /**
         * Search taxa by scientific name via the WoRMS REST API.
         *
         * Cached for 1 hour.
         *
         * @param  string  $name  Scientific name to search for
         * @param  bool  $like  Use wildcard/like search when true
         *
         * @return array<int, array<string, mixed>> List of matching Aphia records
         */
        public function searchByName(string $name, bool $like = true): array
        {
            if (blank($name)) {
                return [];
            }
            
            $cacheKey = 'worms_v2.search.'.md5($name.($like ? '_like' : '_exact'));
            
            return Cache::remember($cacheKey, 3_600, function () use ($name, $like): array {
                $response = Http::timeout(15)
                    ->get("{$this->baseUrl}/AphiaRecordsByName/{$name}", [
                        'like' => $like ? 'true' : 'false',
                        'marine_only' => 'true',
                    ]);
                
                if (!$response->successful()) {
                    return [];
                }
                
                $data = $response->json();
                
                return is_array($data) ? $data : [];
            });
        }
        
        /**
         * Get the full classification tree for a given AphiaID.
         *
         * The WoRMS response is a nested object:
         *   { AphiaID, scientificname, rank, child: { … } }
         *
         * This method flattens it into an ordered array from Kingdom to the target.
         *
         * Cached for 24 hours.
         *
         * @param  int  $aphiaId  WoRMS AphiaID
         *
         * @return array<int, array{aphia_id: int, name: string, rank: string}>
         */
        public function getClassificationTree(int $aphiaId): array
        {
            $cacheKey = 'worms_v2.classification.'.$aphiaId;
            
            return Cache::remember($cacheKey, 86_400, function () use ($aphiaId): array {
                $response = Http::timeout(15)
                    ->get("{$this->baseUrl}/AphiaClassificationByAphiaID/{$aphiaId}");
                
                if (!$response->successful()) {
                    return [];
                }
                
                $tree = $response->json();
                
                return is_array($tree) ? $this->flattenClassificationTree($tree) : [];
            });
        }
        
        /**
         * Recursively flatten the nested WoRMS classification tree.
         *
         * @param  array<string, mixed>  $node  Current tree node
         * @param  array<int, array{aphia_id: int, name: string, rank: string}>  $carry  Accumulator
         *
         * @return array<int, array{aphia_id: int, name: string, rank: string}>
         */
        protected function flattenClassificationTree(array $node, array $carry = []): array
        {
            $carry[] = [
                'aphia_id' => $node[ 'AphiaID' ],
                'name' => $node[ 'scientificname' ],
                'rank' => $node[ 'rank' ],
            ];
            
            if (!empty($node[ 'child' ]) && is_array($node[ 'child' ])) {
                return $this->flattenClassificationTree($node[ 'child' ], $carry);
            }
            
            return $carry;
        }
        
        /**
         * Get a single Aphia record by its ID.
         *
         * Cached for 24 hours.
         *
         * @param  int  $aphiaId  WoRMS AphiaID
         *
         * @return array<string, mixed>|null
         */
        public function getRecord(int $aphiaId): ?array
        {
            $cacheKey = 'worms_v2.record.'.$aphiaId;
            
            return Cache::remember($cacheKey, 86_400, function () use ($aphiaId): ?array {
                $response = Http::timeout(15)
                    ->get("{$this->baseUrl}/AphiaRecordByAphiaID/{$aphiaId}");
                
                return $response->successful() ? $response->json() : null;
            });
        }
    }
