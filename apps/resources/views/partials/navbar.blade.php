<!-- Navbar -->
<div
    class="bg-muted border-input border-y [--kt-drawer-enable:true] lg:mb-10 lg:flex lg:items-stretch lg:[--kt-drawer-enable:false]"
    data-kt-drawer="true"
    data-kt-drawer-class="kt-drawer kt-drawer-start fixed z-10 top-0 bottom-0 w-full me-5 max-w-[250px] p-5 lg:p-0 overflow-auto"
    id="navbar"
>
    <!-- Container -->
    <div class="kt-container-fixed gap-2 px-0 lg:flex lg:flex-wrap lg:items-center lg:justify-between lg:px-7.5">
        <!-- Mega Menu -->
        <div
            class="kt-menu grow flex-col items-stretch gap-5 lg:grow-0 lg:flex-row lg:gap-7.5"
            data-kt-menu="true"
            id="mega_menu"
        >
            <div class="kt-menu-item">
                <a
                    class="kt-menu-link kt-menu-item-active:border-b-mono text-foreground kt-menu-item-hover:text-mono kt-menu-item-active:text-mono kt-menu-item-here:border-b-mono kt-menu-item-here:text-mono border-b border-b-transparent lg:py-3.5"
                    href="{{ route('home') }}"
                >
                    <span class="kt-menu-title text-foreground grow-0 text-sm font-medium">Home</span>
                </a>
            </div>
            <div class="kt-menu-item">
                <a
                    class="kt-menu-link kt-menu-item-active:border-b-mono text-foreground kt-menu-item-hover:text-mono kt-menu-item-active:text-mono kt-menu-item-here:border-b-mono kt-menu-item-here:text-mono border-b border-b-transparent lg:py-3.5"
                    href="{{ route('about') }}"
                >
                    <span class="kt-menu-title text-foreground grow-0 text-sm font-medium">About MAMIAS</span>
                </a>
            </div>
            <div
                class="kt-menu-item"
                data-kt-menu-item-offset="0,0|lg:-20px, 0"
                data-kt-menu-item-offset-rtl="0,0|lg:20px, 0"
                data-kt-menu-item-overflow="true"
                data-kt-menu-item-placement="bottom-start"
                data-kt-menu-item-placement-rtl="bottom-end"
                data-kt-menu-item-toggle="dropdown"
                data-kt-menu-item-trigger="click|lg:hover"
            >
                <div class="kt-menu-link kt-menu-item-active:border-b-mono text-foreground kt-menu-item-hover:text-mono kt-menu-item-active:text-mono kt-menu-item-here:border-b-mono kt-menu-item-here:text-mono border-b border-b-transparent lg:py-3.5">
                    <span class="kt-menu-title text-foreground text-sm font-medium">Explore MAMIAS</span>
                    <span class="kt-menu-arrow flex lg:hidden">
                        <span class="kt-menu-item-show:hidden flex">
                            <i class="ki-filled ki-plus text-secondary-foreground text-xs"></i>
                        </span>
                        <span class="kt-menu-item-show:inline-flex hidden">
                            <i class="ki-filled ki-minus text-secondary-foreground text-xs"></i>
                        </span>
                    </span>
                </div>
                <div class="kt-menu-dropdown kt-menu-default w-full max-w-[220px] py-2.5">
                    <div class="kt-menu-item">
                        <a class="kt-menu-link" href="{{ url('pages/data') }}" tabindex="0">
                            <span class="kt-menu-icon"><i class="ki-filled ki-data"></i></span>
                            <span class="kt-menu-title">Data</span>
                            <span class="kt-menu-badge" data-kt-tooltip="#menu_tooltip_3">
                                <i class="ki-filled ki-information-2 text-muted-foreground text-base"></i>
                            </span>
                            <div class="kt-tooltip" id="menu_tooltip_3">Search MAMIAS.</div>
                        </a>
                    </div>
                    <div
                        class="kt-menu-item"
                        data-kt-menu-item-placement="bottom-start|lg:right-start"
                        data-kt-menu-item-placement-rtl="bottom-start|lg:left-start"
                        data-kt-menu-item-toggle="dropdown"
                        data-kt-menu-item-trigger="click|lg:hover"
                    >
                        <div class="kt-menu-link">
                            <span class="kt-menu-icon"><i class="ki-filled ki-information"></i></span>
                            <span class="kt-menu-title">Dashboard</span>
                            <span class="kt-menu-arrow">
                                <span class="lg:hidden">
                                    <span class="kt-menu-item-show:hidden flex">
                                        <i class="ki-filled ki-plus text-secondary-foreground text-xs"></i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-secondary-foreground text-xs"></i>
                                    </span>
                                </span>
                                <span class="hidden lg:inline-flex">
                                    <i class="ki-filled ki-right text-xs rtl:rotate-180 rtl:transform"></i>
                                </span>
                            </span>
                        </div>
                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px] lg:max-w-[220px]">
                            <div class="kt-menu-item">
                                <a class="kt-menu-link" href="{{ url('pages/dashboard/mediterranean') }}" tabindex="0">
                                    <span class="kt-menu-icon"><i class="ki-filled ki-graph"></i></span>
                                    <span class="kt-menu-title grow-0">Mediterranean</span>
                                </a>
                            </div>
                            <div class="kt-menu-item">
                                <a class="kt-menu-link" href="{{ url('pages/dashboard/by-country') }}" tabindex="0">
                                    <span class="kt-menu-icon"><i class="ki-filled ki-graph-up"></i></span>
                                    <span class="kt-menu-title grow-0">By Country</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="kt-menu-item">
                        <a class="kt-menu-link" href="{{ url('pages/map') }}" tabindex="0">
                            <span class="kt-menu-icon"><i class="ki-filled ki-map"></i></span>
                            <span class="kt-menu-title">MAP</span>
                            <span class="kt-menu-badge" data-kt-tooltip="#menu_tooltip_4">
                                <i class="ki-filled ki-information-2 text-muted-foreground text-base"></i>
                            </span>
                            <div class="kt-tooltip" id="menu_tooltip_4">Explore the MAMIAS map.</div>
                        </a>
                    </div>
                </div>
            </div>
            <div
                class="kt-menu-item"
                data-kt-menu-item-offset="0,0|lg:-20px, 0"
                data-kt-menu-item-offset-rtl="0,0|lg:20px, 0"
                data-kt-menu-item-overflow="true"
                data-kt-menu-item-placement="bottom-start"
                data-kt-menu-item-placement-rtl="bottom-end"
                data-kt-menu-item-toggle="dropdown"
                data-kt-menu-item-trigger="click|lg:hover"
            >
                <div class="kt-menu-link kt-menu-item-active:border-b-mono text-foreground kt-menu-item-hover:text-mono kt-menu-item-active:text-mono kt-menu-item-here:border-b-mono kt-menu-item-here:text-mono border-b border-b-transparent lg:py-3.5">
                    <span class="kt-menu-title text-foreground text-sm font-medium">Resources</span>
                    <span class="kt-menu-arrow flex lg:hidden">
                        <span class="kt-menu-item-show:hidden flex">
                            <i class="ki-filled ki-plus text-secondary-foreground text-xs"></i>
                        </span>
                        <span class="kt-menu-item-show:inline-flex hidden">
                            <i class="ki-filled ki-minus text-secondary-foreground text-xs"></i>
                        </span>
                    </span>
                </div>
                <div class="kt-menu-dropdown kt-menu-default w-full max-w-[260px] py-2.5">
                    <div class="kt-menu-item">
                        <a class="kt-menu-link" href="{{ url('pages/resources') }}" tabindex="0">
                            <span class="kt-menu-icon"><i class="ki-filled ki-data"></i></span>
                            <span class="kt-menu-title">Resources</span>
                        </a>
                    </div>
                    <div class="kt-menu-item">
                        <a class="kt-menu-link" href="{{ url('pages/post-2020-sapbio') }}" tabindex="0">
                            <span class="kt-menu-icon"><i class="ki-filled ki-information"></i></span>
                            <span class="kt-menu-title">Post-2020 SAPBIO</span>
                        </a>
                    </div>
                    <div
                        class="kt-menu-item"
                        data-kt-menu-item-placement="bottom-start|lg:right-start"
                        data-kt-menu-item-placement-rtl="bottom-start|lg:left-start"
                        data-kt-menu-item-toggle="dropdown"
                        data-kt-menu-item-trigger="click|lg:hover"
                    >
                        <div class="kt-menu-link">
                            <span class="kt-menu-icon"><i class="ki-filled ki-information"></i></span>
                            <span class="kt-menu-title">Ballast Water Management</span>
                            <span class="kt-menu-arrow">
                                <span class="lg:hidden">
                                    <span class="kt-menu-item-show:hidden flex">
                                        <i class="ki-filled ki-plus text-secondary-foreground text-xs"></i>
                                    </span>
                                    <span class="kt-menu-item-show:inline-flex hidden">
                                        <i class="ki-filled ki-minus text-secondary-foreground text-xs"></i>
                                    </span>
                                </span>
                                <span class="hidden lg:inline-flex">
                                    <i class="ki-filled ki-right text-xs rtl:rotate-180 rtl:transform"></i>
                                </span>
                            </span>
                        </div>
                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[260px]">
                            <div class="kt-menu-item">
                                <a class="kt-menu-link" href="{{ url('pages/ballast-water/strategy') }}" tabindex="0">
                                    <span class="kt-menu-title">The Mediterranean Ballast Water Management Strategy</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="kt-menu-item">
                        <a
                            class="kt-menu-link"
                            href="{{ url('pages/ballast-water/non-indigenous-species') }}"
                            tabindex="0"
                        >
                            <span class="kt-menu-icon"><i class="ki-filled ki-information"></i></span>
                            <span class="kt-menu-title">Non-indigenous species management</span>
                        </a>
                    </div>
                    <div class="kt-menu-item">
                        <a class="kt-menu-link" href="{{ url('pages/ballast-water/imap') }}" tabindex="0">
                            <span class="kt-menu-icon"><i class="ki-filled ki-information"></i></span>
                            <span class="kt-menu-title">IMAP</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Mega Menu -->
    </div>
    <!-- End of Container -->
</div>
<!-- End of Navbar -->
