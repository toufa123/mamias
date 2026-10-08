<?php

declare(strict_types=1);

namespace App\Services;

use App\Filament\Resources\Literatures\LiteratureGuide;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * A manual (resources/docs/<manual>.md, as LiteratureGuide renders it) as a
 * downloadable PDF, screenshots included. Rendering a few dozen screenshots
 * takes seconds, so the file is kept in storage/app/manuals and rebuilt only
 * when the manual, one of its images or the PDF layout changes.
 */
class ManualPdf
{
    /** @var array<string, string> manual => PDF title */
    public const TITLES = [
        'user-manual' => 'MAMIAS User Manual',
        'admin-manual' => 'MAMIAS Administration Manual',
    ];

    public function download(string $manual): BinaryFileResponse
    {
        $title = self::TITLES[$manual];

        return response()->download($this->path($manual), str_replace(' ', '-', $title).'.pdf', ['Content-Type' => 'application/pdf']);
    }

    /** Absolute path of the PDF, built first if it is missing or stale. */
    public function path(string $manual): string
    {
        $markdown = resource_path("docs/{$manual}.md");
        $images = $this->images((string) file_get_contents($markdown));
        // The layout (this class, the PDF view) is part of the version too.
        $sources = [$markdown, __FILE__, resource_path('views/manuals/pdf.blade.php'), ...array_map(public_path(...), $images)];
        $version = md5(implode('|', array_map(fn (string $file): string => $file.'@'.@filemtime($file), $sources)));
        $file = "manuals/{$manual}-{$version}.pdf";

        if (! Storage::exists($file)) {
            // Old versions of this manual go; the others stay.
            Storage::delete(array_filter(Storage::files('manuals'), fn (string $old): bool => str_starts_with(basename($old), "{$manual}-")));
            Storage::put($file, $this->render($manual));
        }

        return Storage::path($file);
    }

    private function render(string $manual): string
    {
        // Dompdf reads images from disk: point the site-relative paths at public/.
        // Full-page screenshots fill the width; a narrow one (a menu, a dialog)
        // keeps its proportion of it instead of being blown up.
        $html = preg_replace_callback('#<img src="/(images/[^"]+)"#', function (array $match): string {
            $file = public_path($match[1]);
            $width = (int) (@getimagesize($file) ?: [0])[0];
            $percent = $width > 0 ? min(100, (int) round($width / 1750 * 100)) : 100;

            return '<img style="width: '.max($percent, 35).'%" src="'.$file.'"';
        }, LiteratureGuide::html($manual));

        return Pdf::setOption(['chroot' => public_path(), 'isRemoteEnabled' => false])
            ->loadView('manuals.pdf', ['title' => self::TITLES[$manual], 'html' => $html, 'date' => now()->format('j F Y')])
            ->setPaper('a4')
            ->output();
    }

    /**
     * @return list<string> the site-relative image paths the manual shows
     */
    private function images(string $markdown): array
    {
        preg_match_all('#\]\(/(images/[^)\s]+)\)#', $markdown, $matches);

        return $matches[1];
    }
}
