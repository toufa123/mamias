<!DOCTYPE html>
<html class="h-full" data-kt-theme="true" data-kt-theme-mode="light" dir="ltr"
      lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <base href="../../">
    <title>
        @hasSection('title')
            MAMIAS :: @yield('title') | Since 2012
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
    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet"/>
    <!-- Leaflet styles -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>


    {!! \Filament\Support\Facades\FilamentAsset::getTheme('app', 'filament/filament')->getHtml() !!}
    @filamentStyles
    @livewireStyles
    @stack('head')
    <style>
        [x-cloak] { display: none !important; }
    </style>

    {!! CookieConsent::styles() !!}
</head>
<body class=" light antialiased flex h-screen text-base text-foreground bg-background [--header-height:78px]">
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
                        <img class="hidden dark:inline-block w-mamias shrink-0" src="{{ asset('images/mamias_b.png') }}"/>
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
            <div class="flex items-center gap-2 lg:gap-3.5 lg:w-[400px] justify-end">
                <div class="flex items-center gap-2 me-0.5">
                    @php($role = Auth::user()?->getRoleNames()->first() ?? 'User')
                    @php($isSpecial = Auth::user()?->hasAnyRole(['super_admin', 'scientist']) ?? false)
                    @php($statusClass = $isSpecial ? 'kt-avatar-status-online' : 'kt-avatar-status-away')
                    @if (Route::has('filament.mamias.auth.login'))
                        @auth
                            <div data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px"
                                 data-kt-dropdown-offset-rtl="-20px, 10px" data-kt-dropdown-placement="bottom-end"
                                 data-kt-dropdown-placement-rtl="bottom-start" data-kt-dropdown-trigger="click">
                                <button class="relative size-9 overflow-visible"
                                        data-kt-dropdown-toggle="true">
                                    <div class="kt-avatar-image size-9 rounded-full border-2 border-primary overflow-hidden">
                                        <img class="size-full object-cover" src="{{ Auth::user()?->getFilamentAvatarUrl() ?? asset('img/avatar.png') }}"
                                             alt="{{ Auth::user()?->getFilamentName() ?? 'Avatar' }}">
                                    </div>
                                    <span class="absolute -bottom-0.5 -end-0.5 block size-3 rounded-full border-2 border-white {{ $isSpecial ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                </button>
                                <div class="kt-dropdown-menu w-[265px]" data-kt-dropdown-menu="true">
                                    <div class="flex items-center justify-between px-2.5 py-1.5 gap-1.5">
                                        <div class="flex items-center gap-2">
                                            <div class="kt-avatar size-14 rounded-full relative overflow-visible">
                                                <div class="kt-avatar-image size-14 rounded-full border-2 border-primary">
                                                    <img src="{{ Auth::user()?->getFilamentAvatarUrl() ?? asset('img/avatar.png') }}"
                                                         alt="{{ Auth::user()?->getFilamentName() ?? 'Avatar' }}">
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-1.5">
                                                <span class="text-sm text-foreground font-semibold leading-none">
                                                    {{ Auth::user()?->getFilamentName() ?? 'Avatar' }}
                                                </span>
                                                <a class="text-xs text-secondary-foreground hover:text-primary font-medium leading-none" href="#">
                                                    {{ Auth::user()->email }}
                                                </a>
                                                <span class="kt-badge kt-badge-sm kt-badge-primary kt-badge-outline gap-2.5">
                                                    {{ $role }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <ul class="kt-dropdown-menu-sub">
                                        @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'scientific']))
                                            <li>
                                                <div class="kt-dropdown-menu-separator"></div>
                                            </li>
                                            <a class="kt-dropdown-menu-link"
                                               href="{{ route('filament.mamias.pages.dashboard') }}">
                                                <i class="ki-filled ki-element-2"></i>
                                                Admin Area
                                            </a>
                                        @endif
                                        <li>
                                            <a class="kt-dropdown-menu-link" href="#">
                                                <i class="ki-filled ki-badge"></i>
                                                Public Profile
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="px-2.5 pt-1.5 mb-2.5 flex flex-col gap-3.5">
                                        <div class="flex items-center gap-2 justify-between">
                                            <span class="flex items-center gap-2">
                                                <i class="ki-filled ki-moon text-base text-muted-foreground"></i>
                                                <span class="font-medium text-2sm">Dark Mode</span>
                                            </span>
                                            <input class="kt-switch" data-kt-theme-switch-state="dark"
                                                   data-kt-theme-switch-toggle="true" name="check" type="checkbox"
                                                   value="1"/>
                                        </div>
                                        <form action="{{ route('filament.mamias.auth.logout') }}" method="POST"
                                              id="logout-form">
                                            @csrf
                                            <button type="submit" class="kt-btn kt-btn-outline justify-center w-full">
                                                Log out
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- End of User -->
                        @else
                            <div class="flex items-center gap-2">
                                <a href="{{ route('filament.mamias.auth.login') }}" class="kt-link">Login</a>
                            </div>
                            @if (Route::has('filament.mamias.auth.register'))
                                <div class="border-e border-border h-5"></div>
                                <div class="kt-menu" data-kt-menu="true">
                                    <a href="{{ route('filament.mamias.auth.register') }}" class="kt-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                             stroke-linecap="round" stroke-linejoin="round"
                                             class="icon icon-tabler icons-tabler-outline icon-tabler-user-plus">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/>
                                            <path d="M16 19h6"/>
                                            <path d="M19 16v6"/>
                                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4"/>
                                        </svg>
                                        Register
                                    </a>
                                </div>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
            <!-- End of Topbar -->
        </div>
        <!-- End of Container -->
    </header>
    <!-- End of Header -->

    <!-- Navbar -->
    @include('partials.navbar')
    <!-- End of Navbar -->

    <!-- Wrapper Container -->
    <div class="container-fixed w-full flex px-0">
        <!-- Content -->
        <main class="flex flex-col grow" id="content" role="content">
            <!-- Toolbar -->
            <div class="mb-5 lg:mb-7.5">
                <div class="kt-container-fixed flex items-center justify-between flex-wrap gap-5">
                    <div class="flex flex-col justify-center items-start flex-wrap gap-1 lg:gap-2">
                        <h1 class="font-medium text-lg text-mono">@yield('title')</h1>
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

            <!-- Content -->
            <div class="kt-container-fixed">
                <div class="grid gap-5 lg:gap-7.5">
                    @yield('content')
                </div>
            </div>
            <!-- End of Content -->

            <!-- Footer -->
            <footer class="footer">
                <div class="kt-container-fixed">
                    <div class="flex flex-col md:flex-row justify-center md:justify-between items-center gap-3 py-5">
                        <div class="flex order-2 md:order-1 gap-2 font-normal text-sm">
                            <span class="text-muted-foreground ">{{ now()->format('Y') }}©</span>
                            <a class="text-secondary-foreground hover:text-primary" href="https://spa-rac.org">SPA/RAC.</a>
                        </div>
                        <nav class="flex order-1 md:order-2 gap-4 font-normal text-sm text-secondary-foreground">
                            <a class="hover:text-primary" href="#">Legal notice</a>
                            <a class="hover:text-primary" href="#">Terme of Use</a>
                            <a class="hover:text-primary" href="#">Cookies policy</a>
                            <a class="hover:text-primary" onclick="showHideToggleCookiePreferencesModal()">Change Cookie Preferences</a>

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
{!! CookieConsent::scripts() !!}
@stack('scripts')
<script src="{{ asset('assets/js/core.bundle.js') }}"></script>
<script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}"></script>
<script src="{{ asset('assets/js/widgets/general.js') }}"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

@filamentScripts
@livewireScripts
<!-- End of Scripts -->
</body>
</html>
