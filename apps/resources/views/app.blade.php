<!DOCTYPE html>
<html
    class="h-full"
    data-kt-theme="true"
    data-kt-theme-mode="light"
    dir="ltr"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
>
<head>
    <base href="../../" />
    <title>
        @hasSection ('title')
            MAMIAS ::
            @yield ('title')
            | Since 2012
        @elseif (isset($pageTitle))
            MAMIAS :: {{ $pageTitle }} | Since 2012
        @else
            MAMIAS | Since 2012
        @endif
    </title>
    <meta charset="utf-8" />
    <meta content="follow, index" name="robots" />
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport" />
    <meta content="" property="og:description" />
    <meta content="assets/media/app/og-image.png" property="og:image" />
    <link href="{{ asset('images/apple-touch-icon.png') }}" rel="apple-touch-icon" sizes="180x180" />
    <link href="{{ asset('images/favicon-32x32.png') }}" rel="icon" sizes="32x32" type="image/png" />
    <link href="{{ asset('images/favicon-16x16.png') }}" rel="icon" sizes="16x16" type="image/png" />
    <link href="{{ asset('images/favicon.ico') }}" rel="shortcut icon" />
    @include ('partials.pwa-head')
    {{--
        Geist / Geist Mono — see DESIGN-SYSTEM.md. Self-hosted: the @font-face
        rules are in resources/css/app.css, the files in public/fonts/geist.

        Weights are deliberately few. Hierarchy comes from size and from
        negative tracking on the large sizes, not from bolding.
    --}}
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet" />

    {!! \Filament\Support\Facades\FilamentAsset::getTheme('app', 'filament/filament')->getHtml() !!}
    {{--
        Only the plugins the public pages render: the My* Livewire pages reuse
        the Occurrence, NisSuggestion and Literature schemas and tables (maps,
        stepper, comments, export), and Layup renders the CMS pages. Without a
        list, @filamentStyles/@filamentScripts emit every panel plugin (logs
        explorer, file manager, jobs monitor…) — ~1.5 MB this site never uses.
        A public page that starts using another plugin must add it here and
        to @filamentScripts below.
    --}}
    @filamentStyles ([
        'crumbls/layup',
        'eduardoribeirodev/filament-leaflet',
        'filament-stepper',
        'jeffersongoncalves/filament-action-export',
        'kirschbaum-development/commentions',
    ])

    {{--
        The public site's own stylesheets must load AFTER @filamentStyles.

        @filamentStyles emits Filament plugin CSS onto this public layout.
        Several of those are full Tailwind builds
        that ship bare, unscoped utilities in the `utilities` cascade layer: a
        plain `.flex-col` with no media query, for instance. Loading them after
        the site's own CSS let such a rule beat the navbar's `lg:flex-row` and
        collapse the whole menu into a stacked column at every viewport.

        Keep these two in this relative order: app.css defines --font-sans, so
        swapping it past styles.css would change the site font.
    --}}
    @vite (['resources/css/app.css'])
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet" />
    @livewireStyles
    @stack ('styles')
    @stack ('head')
    <!-- Add Laravel Notify CSS -->
    @notifyCss
    <style>
        [x-cloak] {
            display: none !important;
        }
        .notify .border-green-500 {
            border-color: var(--mamias-teal-500) !important;
        }
        .notify .text-green-400 {
            color: var(--mamias-teal-600) !important;
        }
        /*
            The package slides the banner in from bottom:-150% over 1s, and only
            after DOMContentLoaded — while its dim overlay is already up. The page
            sat greyed out for ~1.5s before the banner arrived. `html` outranks the
            package rule, whose stylesheet is emitted after this block.
        */
        html .cookie-consent-root {
            transition-duration: 0.25s;
        }
    </style>
    <style>
        @media (width >= 64rem) {
            #navbar {
                display: flex !important;
            }
        }
        @media (width < 64rem) {
            #navbar {
                display: none;
            }
            #navbar.open {
                display: flex;
            }
        }

        .mobile-notice {
            display: flex;
        }
        .desktop-content {
            display: none;
        }

        @media (width >= 48rem) {
            .mobile-notice {
                display: none;
            }
            .desktop-content {
                display: block;
            }
        }
    </style>

    {!! CookieConsent::styles() !!}
</head>
{{--
    The auth state is published as a body class so page content stored in the
    CMS can switch its call-to-action buttons. Layup echoes stored HTML raw, it
    never runs it through the Blade compiler, so an @auth inside page content
    would be printed literally. The class is server-rendered, so there is no
    flash of the wrong variant.
--}}
<body
    @class ([
    {{-- Layout B: header and mega menu are one 64px bar, not 78px + a strip. --}}
    {{-- No theme class here: the script below writes `light` or `dark` onto
         <html>, which is what the token blocks in app.css key off. A `light`
         class pinned on <body> only contradicts it. --}}
    'antialiased flex flex-col min-h-screen text-base text-foreground bg-background [--header-height:64px]',
    'is-authenticated' => auth()->check(),
    'is-guest' => ! auth()->check(),
    'is-staff' => (bool) auth()->user()?->hasAnyRole(['super_admin', 'scientist', 'admin']),
    // Printed on A4 landscape (app.css, @media print).
    'print-landscape' => request()->is('pages/map'),
])
>
    <!-- Theme Mode -->
    <script>
        const defaultThemeMode = "light"; // light|dark|system
        let themeMode;

        if (document.documentElement) {
            if (localStorage.getItem("kt-theme")) {
                themeMode = localStorage.getItem("kt-theme");
            } else if (document.documentElement.hasAttribute("data-kt-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-kt-theme-mode");
            } else {
                themeMode = defaultThemeMode;
            }

            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }

            document.documentElement.classList.add(themeMode);
        }
    </script>
    <!-- End of Theme Mode -->

    <!-- Main -->
    <div class="in-data-kt-[sticky-header=on]:pt-(--header-height) flex grow flex-col">
        <!-- Header -->
        <header
            class="bg-background print-hidden flex h-(--header-height) shrink-0 items-center transition-[height]"
            data-kt-sticky="true"
            data-kt-sticky-class="transition-[height] fixed z-10 top-0 left-0 right-0 backdrop-blur-md bg-background/70 border border-border"
            data-kt-sticky-name="header"
            data-kt-sticky-offset="100px"
            id="header"
        >
            <!-- Container -->
            <div class="kt-container-fixed flex items-center gap-4">
                <!-- Logo -->
                <div class="flex shrink-0 items-center gap-1">
                    <button
                        class="kt-btn kt-btn-icon kt-btn-ghost -ms-2.5 lg:hidden"
                        data-kt-drawer-toggle="#navbar"
                        type="button"
                    >
                        <i class="ki-filled ki-menu"></i>
                    </button>
                    <a class="flex shrink-0 items-center" href="{{ route('home') }}">
                        <img class="h-[47px] w-auto shrink-0 dark:hidden" src="{{ asset('images/Logoweb.png') }}" />
                        <img
                            class="hidden h-[47px] w-auto shrink-0 dark:inline-block"
                            src="{{ asset('images/mamias_b.png') }}"
                        />
                    </a>
                </div>
                <!-- End of Logo -->

                <div class="border-border hidden h-5 shrink-0 border-e lg:block"></div>

                {{--
                    The mega menu now lives inside the bar rather than in its
                    own strip below it. On mobile it is still the drawer that
                    the button above toggles — the partial keeps its #navbar id
                    and data-kt-drawer-* attributes, which is what KTDrawer
                    binds to.
                --}}
                @include ('partials.navbar')

                <!-- Topbar -->
                <div class="ms-auto flex shrink-0 items-center">
                    @include ('partials.usermenu')
                </div>
                <!-- End of Topbar -->
            </div>
            <!-- End of Container -->
        </header>
        <!-- End of Header -->

        <!-- Wrapper Container -->
        <div class="container-fixed flex w-full grow px-0">
            <!-- Content -->
            <x-notify::notify />
            <main class="flex grow flex-col" id="content" role="content">
                {{--
                    Page head (Layout B). The old toolbar sat on plain paper
                    directly under the menu strip; with the strip gone, this
                    tinted band is what separates the bar from the content and
                    gives the page title somewhere to sit. Breadcrumbs move to
                    the right so the title owns the left edge.
                --}}
                <div class="bg-muted border-border print-hidden mb-5 border-b lg:mb-7.5">
                    <div class="kt-container-fixed flex flex-wrap items-end justify-between gap-5 py-7">
                        <div class="flex flex-col flex-wrap items-start justify-center gap-1 lg:gap-2">
                            <h1 class="text-mono text-2xl font-medium tracking-tight">
                                @hasSection ('title')
                                @yield ('title')
                                @elseif (isset($pageTitle))
                                {{ $pageTitle }}
                                @endif
                            </h1>
                            <div class="flex items-center gap-1 text-sm font-normal">
                                @hasSection ('breadcrumbs')
                                @yield ('breadcrumbs')
                                @else
                                {{ Breadcrumbs::render('home') }}
                                @endif
                            </div>
                        </div>
                        {{-- The pages with a print layout (app.css, @media print). --}}
                        @if (request()->is('pages/dashboard/*', 'pages/map'))
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" onclick="window.print()">
                            <i class="ki-filled ki-printer"></i>
                            Print
                        </button>
                        @endif
                    </div>
                </div>
                <!-- End of Toolbar -->

                <!-- Mobile notice -->
                <div class="mobile-notice kt-container-fixed grow">
                    <div class="flex flex-col items-center justify-center px-6 text-center" style="min-height: 60vh">
                        <svg class="text-primary mb-4 h-16 w-16" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="4" y="2" width="16" height="20" rx="2" ry="2" />
                            <line x1="12" y1="18" x2="12.01" y2="18" />
                        </svg>
                        <h2 class="text-mono mb-2 text-xl font-semibold">Optimized for Larger Screens</h2>
                        <p class="text-muted-foreground max-w-md text-base">This website is best viewed on a tablet or desktop computer. Please switch to a larger screen for the full experience.</p>
                    </div>
                </div>

                <!-- Content -->
                {{-- The Explore MAMIAS pages print through a frame whose <thead> and <tfoot>
                     repeat on every sheet (partials/print-header, print-footer); on
                     screen it is display: contents, so the page lays out as before. --}}
                @php
                    $printable = request()->is('pages/dashboard/*', 'pages/map');
                @endphp
                <div class="desktop-content kt-container-fixed grow">
                    @if ($printable)
                    <table class="print-frame">
                        <thead>
                            <tr>
                                <td>@include ('partials.print-header')</td>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <td>@include ('partials.print-footer')</td>
                            </tr>
                        </tfoot>
                        <tbody>
                            <tr>
                                <td>
                                    @endif
                                    @yield ('content')
                                    @if ($printable)
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    @endif
                </div>
                <!-- End of Content -->

                <!-- Footer -->
                <footer class="footer print-hidden">
                    <div class="kt-container-fixed">
                        <div class="flex flex-col items-center gap-3 py-5 md:flex-row md:justify-between">
                            <div class="order-2 flex gap-2 text-sm font-normal md:order-1">
                                <span class="text-muted-foreground">{{ now()->format('Y') }}©</span>
                                <a class="text-secondary-foreground hover:text-primary" href="https://spa-rac.org"
                                    >SPA/RAC.</a
                                >
                            </div>
                            <nav
                                class="text-secondary-foreground order-1 flex flex-wrap justify-center gap-x-4 gap-y-1 text-sm font-normal md:order-2"
                            >
                                <a class="hover:text-primary" href="{{ route('manual') }}">User manual</a>
                                <a class="hover:text-primary" href="{{ url('legal-notice') }}">Legal notice</a>
                                <a class="hover:text-primary" href="{{ url('terms-of-use') }}">Terms of use</a>
                                <a class="hover:text-primary" href="{{ url('cookies-policy') }}">Cookies policy</a>
                                <button
                                    type="button"
                                    class="hover:text-primary cursor-pointer"
                                    onclick="showHideToggleCookiePreferencesModal()"
                                >
                                    Change Cookie Preferences
                                </button>
                                <a class="hover:text-primary" href="{{ url('sitemap.xml') }}">SiteMap</a>
                            </nav>
                        </div>
                    </div>
                </footer>
                <!-- End of Footer -->
            </main>
            <!-- End of Content -->
        </div>
        <!-- End of Wrapper Container -->
    </div>
    <!-- End of Main -->

    <!-- Scripts -->
    @filamentScripts ([
        'app',
        'filament/actions',
        'filament/notifications',
        'filament/schemas',
        'filament/support',
        'filament/tables',
        'eduardoribeirodev/filament-leaflet',
        'jeffersongoncalves/filament-action-export',
        'kirschbaum-development/commentions',
    ])
    @livewireScripts
    {{--
        Not @notifyJs: that emits notify.js as a classic script, whose top-level
        `var L=[]` overwrites the global Leaflet. filament-leaflet's map
        components call the bare global `L` at runtime, so every map on this
        layout rendered grey. The script is a bundled Alpine that only starts
        when window.Alpine is unset, which Livewire never leaves it, so as a
        module it changes nothing but keeps its vars private.
    --}}
    <script type="module" src="{{ asset('vendor/mckenziearts/laravel-notify/dist/notify.js') }}"></script>
    {!! CookieConsent::scripts() !!}
    @stack ('scripts')
    <script>
        document.addEventListener("x-modal-opened", () => {
            setTimeout(() => {
                document.querySelectorAll('[x-data^="leafletMapField"]').forEach((el) => {
                    const data = window.Alpine?.$data(el);
                    if (data?.mapCore?.map) {
                        data.mapCore.map.invalidateSize();
                    }
                });
            }, 100);
        });
    </script>
    <script src="{{ asset('assets/js/core.bundle.js') }}"></script>
    <script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
    <script src="{{ asset('assets/js/widgets/general.js') }}"></script>
    <script>
        // KTUI does not self-initialise here, so the navbar's dropdowns only
        // work once this runs. It used to be bound to `livewire:navigated`
        // alone, which never fires on a first page load — so on a cold visit
        // the mega menu had no behaviour at all.
        const initKtui = () => {
            if (window.KTMenu && typeof KTMenu.init === "function") {
                KTMenu.init();
            }
            if (window.KTDropdown && typeof KTDropdown.reinit === "function") {
                KTDropdown.reinit();
            } else if (window.KTDropdown && typeof KTDropdown.init === "function") {
                KTDropdown.init();
            }
            if (window.KTDrawer && typeof KTDrawer.reinit === "function") {
                KTDrawer.reinit();
            } else if (window.KTDrawer && typeof KTDrawer.init === "function") {
                KTDrawer.init();
            }
        };

        // This script sits at the end of <body>, so the menu markup is parsed.
        initKtui();
        document.addEventListener("livewire:navigated", initKtui);
    </script>
    <!-- End of Scripts -->
</body>
</html>
