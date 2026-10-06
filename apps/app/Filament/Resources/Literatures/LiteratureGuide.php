<?php

declare(strict_types=1);

namespace App\Filament\Resources\Literatures;

use Filament\Actions\Action;
use Illuminate\Support\Str;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;

/**
 * Step-by-step guides to the literature workflow, shown in a scrollable
 * popup from a "Guide" button. Each guide is a Markdown file in
 * resources/docs/ (editable without touching PHP; `[TOC]` becomes the table
 * of contents), with its screenshots in public/images/docs/<guide>/:
 *
 * - `literatures`: reviewers, on the panel's Literatures resource;
 * - `references`: contributors, on the public "My Bibliographic References";
 * - `taxa`: the MAMIAS catalogue (NIS Taxon list);
 * - `intro-events`: the Introduction Events list;
 * - `occurrences`: the Occurrences review list;
 * - `species-reports`: contributors, on the public "My Species Reports";
 * - `suggestions`: contributors, on the public "My Species Suggestions".
 *
 * Two manuals render the same way as whole pages rather than popups:
 * `admin-manual` (panel, Pages\AdminManual) and `user-manual` (public, /pages/manual).
 * Their master copies are the claude.ai documents they were written in; keep both in step.
 * Both also download as PDF (Services\ManualPdf), screenshots included; the
 * screenshots come from resources/docs/screenshots/*.cjs, anonymised (anonymise.cjs).
 *
 * Demo data for the occurrence and suggestion screenshots: `php artisan guides:demo-data`.
 *
 * The screenshots were taken from the dev stack with temporary demo
 * references; retake them when the screens change.
 */
class LiteratureGuide
{
    public static function action(string $guide = 'literatures', string $heading = 'Literature guide'): Action
    {
        return Action::make('guide')
            ->label('Guide')
            ->icon('tabler-help')
            ->color('gray')
            ->modalHeading($heading)
            ->modalIcon(null)
            ->modalWidth('5xl')
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->modalContent(fn () => view('filament.literatures.guide', ['html' => self::html($guide)]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close');
    }

    /**
     * The guide rendered to HTML. The Markdown is ours, not user input, so
     * it is trusted.
     */
    public static function html(string $guide = 'literatures'): string
    {
        return Str::markdown(
            file_get_contents(resource_path("docs/{$guide}.md")),
            [
                'heading_permalink' => [
                    'symbol' => '#',
                    'insert' => 'after',
                    'id_prefix' => 'guide',
                    'fragment_prefix' => 'guide',
                    'apply_id_to_heading' => true,
                ],
                'table_of_contents' => [
                    'placeholder' => '[TOC]',
                    'position' => 'placeholder',
                    'min_heading_level' => 2,
                    'max_heading_level' => 2,
                ],
            ],
            [new HeadingPermalinkExtension, new TableOfContentsExtension],
        );
    }
}
