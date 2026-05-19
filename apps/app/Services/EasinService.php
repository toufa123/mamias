<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EasinService
{
    private string $baseUrl = 'https://easin.jrc.ec.europa.eu/apixg/catxg/term';

    public function fetchEasinId(string $scientificName): ?string
    {
        if (strlen($scientificName) < 4) {
            return null;
        }

        $term = str_replace(' ', '%20', $scientificName);
        $url = "{$this->baseUrl}/{$term}";

        return Cache::remember('easin_id_'.md5($scientificName), 86400, function () use ($url, $scientificName) {
            try {
                $response = Http::timeout(10)->get($url);

                if ($response->successful()) {
                    $data = $response->json();

                    if (is_array($data) && count($data) > 0) {
                        return $data[0]['EASINID'] ?? $data[0]['easinId'] ?? null;
                    }
                }
            } catch (\Exception $e) {
                Log::error('EASIN API request failed', [
                    'scientific_name' => $scientificName,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        });
    }
}
