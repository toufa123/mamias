<?php

declare(strict_types=1);

namespace Database\Seeders;

use Crumbls\Layup\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Creates the pages behind the navbar's Resources menu as empty, published
 * Layup pages, so every menu link resolves while editors write the content.
 *
 * Pages that already exist are left untouched: re-running the seeder never
 * overwrites what editors have built. A soft-deleted page is restored, since
 * a trashed page still holds its path.
 *
 * Run: php artisan db:seed --class=LayupResourcesPagesSeeder
 */
class LayupResourcesPagesSeeder extends Seeder
{
    /**
     * @var array<string, array{title: string, description: string}>
     */
    public const PAGES = [
        'pages/spa-bd-protocol' => [
            'title' => 'Non-indigenous species under the SPA/BD Protocol',
            'description' => 'Non-indigenous species under the Protocol concerning Specially Protected Areas and Biological Diversity in the Mediterranean.',
        ],
        'pages/post-2020-sapbio' => [
            'title' => 'Non-indigenous species and the Post-2020 SAPBIO',
            'description' => 'The Post-2020 Strategic Action Programme for the Conservation of Biodiversity in the Mediterranean.',
        ],
        'pages/mediterranean-action-plan' => [
            'title' => 'Action Plan concerning Species Introductions and Invasive Species in the Mediterranean Sea',
            'description' => 'The Barcelona Convention action plan on species introductions and invasive species.',
        ],
        'pages/imap' => [
            'title' => 'Non-indigenous species and IMAP',
            'description' => 'The Integrated Monitoring and Assessment Programme and its non-indigenous species indicator.',
        ],
        'pages/ballast-water/strategy' => [
            'title' => 'Non-indigenous species and Ballast Water Management',
            'description' => 'The Mediterranean Strategy on Ships\' Ballast Water Management.',
        ],
    ];

    public function run(): void
    {
        foreach (self::PAGES as $slug => $page) {
            $existing = Page::withTrashed()->where('slug', $slug)->first();

            if ($existing !== null) {
                if ($existing->trashed()) {
                    $existing->restore();
                }

                continue;
            }

            Page::create([
                'slug' => $slug,
                'title' => $page['title'],
                'status' => Page::STATUS_PUBLISHED,
                'published_at' => now(),
                'meta' => ['description' => $page['description']],
                'content' => ['rows' => []],
            ]);
        }
    }
}
