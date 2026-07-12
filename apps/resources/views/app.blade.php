<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr"
      lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <base href="../../">
    <title>
        @hasSection('title')
            MAMIAS :: @yield('title') | Since 2012
        @elseif(isset($pageTitle))
            MAMIAS :: {{ $pageTitle }} | Since 2012
        @else
            MAMIAS | Since 2012
        @endif
    </title>
    <meta charset="utf-8"/>
    <meta content="follow, index" name="robots"/>
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport"/>
    <meta content="" property="og:description"/>
    <meta content="assets/media/app/og-image.png" property="og:image"/>
    <link href="{{ asset('img/apple-touch-icon.png') }}" rel="apple-touch-icon" sizes="180x180"/>
    <link href="{{ asset('img/favicon-32x32.png') }}" rel="icon" sizes="32x32" type="image/png"/>
    <link href="{{ asset('img/favicon-16x16.png') }}" rel="icon" sizes="16x16" type="image/png"/>
    <link href="{{ asset('img/favicon.ico') }}" rel="shortcut icon"/>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="{{ asset('assets/vendors/keenicons/styles.bundle.css') }}" rel="stylesheet"/>
    @vite(['resources/css/app.css'])
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet"/>


    {!! \Filament\Support\Facades\FilamentAsset::getTheme('app', 'filament/filament')->getHtml() !!}
    @filamentStyles
    @livewireStyles
    @stack('styles')
    @stack('head')
    <!-- Add Laravel Notify CSS -->
    @notifyCss
    <style>
        [x-cloak] {
            display: none !important;
        }
        .notify .border-green-500 {
            border-color: #00899d !important;
        }
        .notify .text-green-400 {
            color: #00899d !important;
        }
    </style>
    <style>
        @media (width >= 64rem) {
            #navbar { display: flex !important; }
        }
        @media (width < 64rem) {
            #navbar { display: none; }
            #navbar.open { display: flex; }
        }

        .mobile-notice { display: flex; }
        .desktop-content { display: none; }

        @media (width >= 48rem) {
            .mobile-notice { display: none; }
            .desktop-content { display: block; }
        }
    </style>

    {!! CookieConsent::styles() !!}
</head>
<body
    class="light antialiased flex flex-col min-h-screen text-base text-foreground bg-background [--header-height:78px]">
<!-- Theme Mode -->
<script>
    const defaultThemeMode = 'light'; // light|dark|system
    let themeMode;

    if (document.documentElement) {
        if (localStorage.getItem('kt-theme')) {
            themeMode = localStorage.getItem('kt-theme');
        } else if (document.documentElement.hasAttribute('data-kt-theme-mode')) {
            themeMode = document.documentElement.getAttribute('data-kt-theme-mode');
        } else {
            themeMode = defaultThemeMode;
        }

        if (themeMode === 'system') {
            themeMode = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }

        document.documentElement.classList.add(themeMode);
    }
</script>
<!-- End of Theme Mode -->

<!-- Main -->
<div class="flex grow flex-col in-data-kt-[sticky-header=on]:pt-(--header-height)">
    <!-- Header -->
    <header class="flex items-center transition-[height] shrink-0 bg-background h-(--header-height)"
            data-kt-sticky="true"
            data-kt-sticky-class="transition-[height] fixed z-10 top-0 left-0 right-0 shadow-xs backdrop-blur-md bg-background/70 border border-border"
            data-kt-sticky-name="header" data-kt-sticky-offset="100px" id="header">
        <!-- Container -->
        <div class="kt-container-fixed flex lg:justify-between items-center gap-2.5">
            <!-- Logo -->
            <div class="flex items-center gap-1 lg:w-[400px] grow lg:grow-0">
                <button class="kt-btn kt-btn-icon kt-btn-ghost -ms-2.5 lg:hidden" data-kt-drawer-toggle="#navbar">
                    <i class="ki-filled ki-menu"></i>
                </button>
                <div class="flex items-center gap-2">
                    <a class="flex items-center shrink-0" href="{{ route('home') }}">
                        <img class="dark:hidden w-mamias shrink-0" src="{{ asset('images/Logoweb.png') }}"/>
                        <img class="hidden dark:inline-block w-mamias shrink-0"
                             src="{{ asset('images/mamias_b.png') }}"/>
                    </a>
                </div>
                <!-- Navs -->
                <div class="hidden lg:flex items-center">
                    <div class="border-e border-border h-5 mx-4"></div>
                    <h3 class="text-mono text-lg font-medium hidden md:block">MAMIAS</h3>
                </div>
                <!-- End of Navs -->
            </div>
            <!-- End of Logo -->

            <!-- Topbar -->
            @include('partials.usermenu')
            <!-- End of Topbar -->
        </div>
        <!-- End of Container -->
    </header>
    <!-- End of Header -->

    <!-- Navbar -->
    @include('partials.navbar')
    <!-- End of Navbar -->

    <!-- Wrapper Container -->
    <div class="container-fixed w-full flex grow px-0">
        <!-- Content -->
        <x-notify::notify />
        <main class="flex flex-col grow" id="content" role="content">
            <!-- Toolbar -->
            <div class="mb-5 lg:mb-7.5">
                <div class="kt-container-fixed flex items-center justify-between flex-wrap gap-5">
                    <div class="flex flex-col justify-center items-start flex-wrap gap-1 lg:gap-2">
                        <h1 class="font-medium text-lg text-mono">@hasSection('title')
                                @yield('title')
                            @elseif(isset($pageTitle))
                                {{ $pageTitle }}
                            @endif</h1>
                        <div class="flex items-center gap-1 text-sm font-normal">
                            @hasSection('breadcrumbs')
                                @yield('breadcrumbs')
                            @else
                                {{ Breadcrumbs::render('home') }}
                            @endif
                        </div>
                    </div>
                    {{--                     <div class="flex items-center flex-wrap gap-1.5 lg:gap-3.5"> --}}
                    {{--                         <a class="kt-btn kt-btn-sm kt-btn-outline" href="#"> --}}
                    {{--                             <i class="ki-filled ki-exit-down"></i> --}}
                    {{--                             Export --}}
                    {{--                         </a> --}}
                    {{--                     </div> --}}
                </div>
            </div>
            <!-- End of Toolbar -->

            <!-- Mobile notice -->
            <div class="mobile-notice kt-container-fixed grow">
                <div class="flex flex-col items-center justify-center text-center px-6" style="min-height: 60vh;">
                    <svg class="w-16 h-16 text-primary mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                    <h2 class="text-xl font-semibold text-mono mb-2">Optimized for Larger Screens</h2>
                    <p class="text-base text-muted-foreground max-w-md">This website is best viewed on a tablet or desktop computer. Please switch to a larger screen for the full experience.</p>
                </div>
            </div>

            <!-- Content -->
            <div class="desktop-content kt-container-fixed grow">
                @yield('content')
            </div>
            <!-- End of Content -->

            <!-- Footer -->
            <footer class="footer">
                <div class="kt-container-fixed">
                    <div class="flex flex-col items-center gap-3 py-5 md:flex-row md:justify-between">
                        <div class="flex order-2 gap-2 text-sm font-normal md:order-1">
                            <span class="text-muted-foreground">{{ now()->format('Y') }}©</span>
                            <a class="text-secondary-foreground hover:text-primary"
                               href="https://spa-rac.org">SPA/RAC.</a>
                        </div>
                        <nav class="flex order-1 flex-wrap justify-center gap-x-4 gap-y-1 text-sm font-normal text-secondary-foreground md:order-2">
                            <a class="hover:text-primary" href="#">Legal notice</a>
                            <a class="hover:text-primary" href="#">Terme of Use</a>
                            <a class="hover:text-primary" href="#">Cookies policy</a>
                            <a class="hover:text-primary" onclick="showHideToggleCookiePreferencesModal()">Change Cookie
                                Preferences</a>
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
@filamentScripts
@livewireScripts
@notifyJs
{!! CookieConsent::scripts() !!}
@stack('scripts')
<script>
    document.addEventListener('x-modal-opened', () => {
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
    document.addEventListener('livewire:navigated', () => {
        if (window.KTMenu && typeof KTMenu.init === 'function') {
            KTMenu.init();
        }
        if (window.KTDropdown && typeof KTDropdown.reinit === 'function') {
            KTDropdown.reinit();
        } else if (window.KTDropdown && typeof KTDropdown.init === 'function') {
            KTDropdown.init();
        }
        if (window.KTDrawer && typeof KTDrawer.reinit === 'function') {
            KTDrawer.reinit();
        } else if (window.KTDrawer && typeof KTDrawer.init === 'function') {
            KTDrawer.init();
        }
    });
</script>
<!-- End of Scripts -->
</body>
</html>
