<?php

namespace App\Providers;

use App\Filament\Auth\Responses\EmailVerificationResponse;
use App\Filament\Auth\Responses\LoginResponse;
use App\Filament\Auth\Responses\RegistrationResponse;
use App\Listeners\LogRoleChangeListener;
use App\Listeners\TaxonImportCompletedListener;
use App\Models\IntroEventRecord;
use App\Models\User;
use App\Policies\AuthenticationLogPolicy;
use App\Services\TaxonMatcher;
use App\Support\MamiasNavigationManager;
use Asignua\FilamentSeoFiles\Contracts\LlmsIndexSource;
use Asignua\FilamentSeoFiles\Contracts\SitemapSource;
use Asignua\FilamentSeoFiles\Data\LlmsLink;
use Asignua\FilamentSeoFiles\Data\LlmsSection;
use Asignua\FilamentSeoFiles\Data\SitemapEntry;
use Asignua\FilamentSeoFiles\SeoFiles;
use Asignua\FilamentSeoFiles\Sources\ModelSource;
use BladeUI\Icons\Factory as IconFactory;
use Crumbls\Layup\Models\Page;
use EduardoRibeiroDev\FilamentLeaflet\Fields\MapPicker;
use EduardoRibeiroDev\FilamentLeaflet\Infolists\MapEntry;
use EduardoRibeiroDev\FilamentLeaflet\Tables\MapColumn;
use Filament\Actions\Imports\Events\ImportCompleted;
use Filament\Auth\Http\Responses\Contracts\EmailVerificationResponse as EmailVerificationResponseContract;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as LoginResponseContract;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse as RegistrationResponseContract;
use Filament\Navigation\NavigationManager;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Facades\FilamentColor;
use Heyosseus\Vacuum\Vacuum;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;
/**
 * Core service provider for the MAMIAS application.
 *
 * Registers custom Filament auth responses, IDE helper debugbar
 * (local only), application-wide colour palette, event listeners, and
 * server health checks.
 */
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
        $this->app->bind(RegistrationResponseContract::class, RegistrationResponse::class);
        $this->app->bind(EmailVerificationResponseContract::class, EmailVerificationResponse::class);

        // Marine kingdom icons (resources/svg/marine), used as "marine-fish" etc.
        // Tabler has no seaweed or seagrass, so these come from other open sets.
        $this->callAfterResolving(IconFactory::class, fn (IconFactory $factory): IconFactory => $factory->add('marine', [
            'path' => resource_path('svg/marine'),
            'prefix' => 'marine',
        ]));

        // Re-homes Vacuum under System and limits System to super_admin; scoped
        // like Filament's own binding so each request builds a fresh sidebar.
        $this->app->scoped(NavigationManager::class, fn (): NavigationManager => new MamiasNavigationManager);

        // Singleton because it indexes the whole catalogue (accepted names
        // plus every recorded synonym) on first use. An import resolves a
        // thousand-odd names in one pass; rebuilding that index per row would
        // turn one query into one per row.
        $this->app->singleton(TaxonMatcher::class);

        if ($this->app->isLocal() && class_exists(\Fruitcake\LaravelDebugbar\ServiceProvider::class)) {
            $this->app->register(\Fruitcake\LaravelDebugbar\ServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerSeoFiles();

        // The cookie-consent script writes its choice cookie in plain text, which
        // EncryptCookies would null out. The banner view reads it server-side to
        // render already visible or already hidden (same name as its data-cookie-prefix).
        EncryptCookies::except(Str::slug(config('laravel-cookie-consent.cookie_prefix')).'_'.date('Y'));

        // Every Leaflet map uses the UNEP/MAP basemap (config/filament-leaflet.php).
        // Runs at make(), so a map's own ->zoom()/->center() still wins.
        foreach ([MapPicker::class, MapEntry::class, MapColumn::class] as $map) {
            $map::configureUsing(fn (MapPicker|MapEntry|MapColumn $component) => $component
                ->tileLayersUrl([config('filament-leaflet.basemap.label') => config('filament-leaflet.basemap.url')])
                ->maxZoom(config('filament-leaflet.basemap.max_zoom')));
        }

        // Surfaces N+1 access outside production. Filament tables eager-load in
        // their own modifyQueryUsing(); anything that still lazy-loads is a bug
        // we want raised before it is paid for per row in production.
        Model::preventLazyLoading(! $this->app->isProduction());

        // Throwing is only useful where something is watching: the suite fails
        // on a violation, so it cannot be ignored. Browsing locally logs instead
        // — a page that has always lazy-loaded should report itself, not 500 and
        // block whatever else was being worked on.
        if (! $this->app->runningUnitTests()) {
            Model::handleLazyLoadingViolationUsing(
                fn (Model $model, string $relation) => logger()->warning(
                    'Lazy loaded relation ['.$relation.'] on ['.$model::class.'].'
                )
            );
        }

        // Banner toasts (filament/hooks/*-alert) are re-sent on every render; the
        // toast's close button reaches the server as this event, so remember it.
        \Livewire\on('call', function ($component, string $method, array $params): void {
            $id = $params[1]['id'] ?? null;

            if ($component instanceof Notifications && $method === '__dispatch' && $params[0] === 'notificationClosed' && is_string($id) && str_starts_with($id, 'banner.')) {
                session()->push('dismissed_banners', $id);
            }
        });

        $invasive = [
            50 => '#feecea', 100 => '#fddcd8', 200 => '#f5c9c4', 300 => '#ff9e93',
            400 => '#e5665a', 500 => '#d33d2f', 600 => '#b42318', 700 => '#912018',
            800 => '#7a1c15', 900 => '#5e1712', 950 => '#3a1512',
        ];

        // Same ramp as MamiasPanelProvider::panel() and --mamias-teal-* in
        // app.css — this one covers Filament components rendered outside a
        // panel. All three must move together; see DESIGN-SYSTEM.md.
        FilamentColor::register([
            'primary' => [
                50 => '#eaf6f8',
                100 => '#d3edf1',
                200 => '#a9dbe3',
                300 => '#6fc3d0',
                400 => '#29a3b7',
                500 => '#078da0', // logo teal — never under white text
                600 => '#056273', // fills under white text, 7.08:1
                700 => '#044e5c',
                800 => '#033b46',
                900 => '#022c35',
                950 => '#011e24',
            ],
            // Status vocabulary from DESIGN-SYSTEM.md: 50 is the documented fill,
            // 200 the border, 600 the text, 300 the dark text, 950 the dark fill.
            // 500 is kept under 4.5:1 on the fill so Filament's badge text lands
            // on 600. "Unresolved" is the gray ramp itself — enums use 'gray'.
            // One red: 'danger' (rejected, destructive) is the invasive ramp, so
            // red means "bad" everywhere instead of two near-identical reds.
            'invasive' => $invasive,
            'danger' => $invasive,
            'established' => [
                50 => '#ffefd4', 100 => '#ffe3b3', 200 => '#f3ddb6', 300 => '#ffc26b',
                400 => '#f59e2a', 500 => '#d97706', 600 => '#b45309', 700 => '#92400e',
                800 => '#78350f', 900 => '#5c2a0c', 950 => '#3a2610',
            ],
            'casual' => [
                50 => '#e3edf5', 100 => '#d2e3f0', 200 => '#bfd4e4', 300 => '#7fc1ef',
                400 => '#3f95cf', 500 => '#1a74b0', 600 => '#00558c', 700 => '#004672',
                800 => '#003858', 900 => '#002b44', 950 => '#0e2c44',
            ],
            'verified' => [
                50 => '#edf8f2', 100 => '#dcf0e5', 200 => '#c8e4d6', 300 => '#79d3a6',
                400 => '#3fae78', 500 => '#2a8a5c', 600 => '#1f6b49', 700 => '#19573b',
                800 => '#14452f', 900 => '#103724', 950 => '#0f2e22',
            ],
            // Not in the species vocabulary proper: NisStatus::RangeExpansion,
            // the one value meaning "not introduced". Violet is used nowhere else.
            'native' => [
                50 => '#f0edf7', 100 => '#e5e0f1', 200 => '#d9d2ea', 300 => '#b9a9e8',
                400 => '#9886c9', 500 => '#7a68ae', 600 => '#5b4b8a', 700 => '#4a3d72',
                800 => '#3b3159', 900 => '#2e2645', 950 => '#231b3a',
            ],
            // Same sea-cast grey as MamiasPanelProvider::panel(). Without it,
            // Filament on public pages fell back to stock zinc, so neutral
            // badges ("Unresolved", categories) drifted from the panel's.
            'gray' => [
                50 => '#f7fafb', 100 => '#edf3f5', 200 => '#d8e3e8', 300 => '#bfd0d8',
                400 => '#9fb4be', 500 => '#5f7783', 600 => '#47606b', 700 => '#2b4652',
                800 => '#1a333f', 900 => '#0e2630', 950 => '#08191f',
            ],
        ]);

        // Embed the MAMIAS logo inline (CID "mamias-logo@mamias", referenced by the
        // mail header) so it renders reliably without depending on a publicly
        // reachable asset URL and isn't blocked as a remote image by mail clients.
        // The fixed Content-ID keeps the HTML identical before and after Symfony
        // prepares the parts; with a generated one, the dev mailbox (which stores
        // the pre-send HTML) can't resolve the image.
        Event::listen(MessageSending::class, function (MessageSending $event): void {
            $logo = public_path('images/mamias.png');

            if (is_file($logo)) {
                $event->message->addPart(
                    (new DataPart(new File($logo), 'mamias-logo', 'image/png'))->asInline()->setContentId('mamias-logo@mamias'),
                );
            }
        });

        Event::listen(ImportCompleted::class, TaxonImportCompletedListener::class);

        // unifilemanager/filament-file-manager's DefaultFileManagerAuthorizer
        // checks $user->can('manageFileManager'), which resolves through this
        // gate — the package requires this exact ability name.
        Gate::define('manageFileManager', fn (User $user): bool => $user->hasRole('super_admin'));

        // The lock manager page and the force-unlock button on a locked record.
        Gate::define('manageResourceLocks', fn (User $user): bool => $user->hasRole('super_admin'));

        // redberry/mailbox-for-laravel dashboard (/mamias/mailbox). Open to
        // everyone, guests included, so new registrants can read their
        // verification mail. Captured mail includes password-reset links, so
        // never in production (mailbox.enabled also unregisters the routes there).
        Gate::define('viewMailbox', fn (?User $user = null): bool => ! app()->isProduction());

        // A vendor model, so Laravel's policy discovery never finds this one.
        Gate::policy(AuthenticationLog::class, AuthenticationLogPolicy::class);

        // heyosseus/vacuum exposes the database shape and query statistics;
        // outside `local` it refuses everyone unless this callback allows.
        Vacuum::auth(fn (Request $request): bool => $request->user()?->hasRole('super_admin') === true);

        Event::listen(
            [RoleAttachedEvent::class, RoleDetachedEvent::class, PermissionAttachedEvent::class, PermissionDetachedEvent::class],
            LogRoleChangeListener::class,
        );

        Health::checks([
            OptimizedAppCheck::new(),
            DebugModeCheck::new(),
            EnvironmentCheck::new(),
            DatabaseCheck::new(),
            RedisCheck::new(),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(80)
                ->failWhenUsedSpaceIsAbovePercentage(90),
            CacheCheck::new(),
            QueueCheck::new(),
        ]);
    }

    /**
     * What sitemap.xml, llms.txt and llms-full.txt list (asignua/filament-seo-files):
     * the published CMS pages, the data explorer and one page per catalogued species.
     * Generated from the panel (System → SEO files) or `seo-files:sitemap` / `seo-files:llms`.
     */
    private function registerSeoFiles(): void
    {
        $home = config('layup.pages.default_slug', 'home');

        SeoFiles::siteNameUsing(fn (): string => 'MAMIAS');
        SeoFiles::descriptionUsing(fn (): string => 'Marine Mediterranean Invasive Alien Species: the database of non-indigenous species recorded in the Mediterranean Sea, with their first records, status, pathways and occurrences.');

        // A manual "Sitemap URL" can never duplicate a page the site already serves.
        SeoFiles::ownedPathUsing(fn (string $locale, string $path): bool => in_array($path, ['', 'pages/data', 'pages/manual'], true)
            || preg_match('#^pages/data/\d+$#', $path) === 1
            || Page::query()->where('slug', $path)->exists());

        SeoFiles::source(
            ModelSource::make(Page::class)
                ->query(fn (Builder $query): Builder => $query->published())
                // Page::getUrl() builds from `path`, which these pages leave empty.
                ->url(fn (Page $page): string => $page->slug === $home ? '/' : '/'.$page->slug)
                ->title(fn (Page $page): string => $page->title)
                ->description(fn (Page $page): ?string => $page->getMetaDescription())
                ->body(fn (Page $page): string => $page->toHtml())
                ->section('Pages'),
            new class implements LlmsIndexSource, SitemapSource
            {
                public function sitemapEntries(): iterable
                {
                    yield new SitemapEntry(url: SeoFiles::localizedUrl(SeoFiles::defaultLocale(), 'pages/data'));
                    yield new SitemapEntry(url: SeoFiles::localizedUrl(SeoFiles::defaultLocale(), 'pages/map'));
                    yield new SitemapEntry(url: SeoFiles::localizedUrl(SeoFiles::defaultLocale(), 'pages/manual'));
                }

                public function llmsSections(string $locale): iterable
                {
                    yield new LlmsSection('Data', [
                        new LlmsLink('NIS data explorer', SeoFiles::localizedUrl($locale, 'pages/data'), 'Every non-indigenous species in the catalogue, searchable, with its first Mediterranean record.'),
                        new LlmsLink('NIS map', SeoFiles::localizedUrl($locale, 'pages/map'), 'The species on a map of the Mediterranean, by EcAp sub-region or by country of first record, with a summary of each.'),
                        new LlmsLink('User manual', SeoFiles::localizedUrl($locale, 'pages/manual'), 'How to browse MAMIAS, create an account and contribute references, sightings and species suggestions.'),
                    ]);
                }
            },
            ModelSource::make(IntroEventRecord::class)
                // Events of species deleted from the catalogue have no page (NisSpecies aborts 404).
                ->query(fn (Builder $query): Builder => $query->whereHas('taxon')->with('taxon'))
                ->url(fn (IntroEventRecord $record): string => route('data.species', $record, absolute: false))
                ->title(fn (IntroEventRecord $record): string => (string) $record->taxon?->scientificname)
                ->description(fn (IntroEventRecord $record): string => self::speciesSummary($record))
                ->body(fn (IntroEventRecord $record): string => '# '.trim($record->taxon?->scientificname.' '.$record->taxon?->authority)."\n\n".self::speciesSummary($record))
                ->markdown()
                ->section('Species'),
        );
    }

    /** One line on a species page: NIS and establishment status, first Mediterranean record. */
    private static function speciesSummary(IntroEventRecord $record): string
    {
        // Either status can be empty on imported events, whatever the docblock says.
        $statuses = implode(', ', array_filter([$record->nis_status?->getLabel(), $record->establishment_status?->getLabel()]));

        return implode(' ', array_filter([
            $statuses === '' ? null : "{$statuses}.",
            $record->first_introduction_year ? "First recorded in the Mediterranean in {$record->first_introduction_year}." : null,
        ]));
    }
}
