<!-- Navbar -->
<div class="bg-muted hidden lg:flex lg:items-stretch border-y border-input lg:mb-10 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]"
     data-kt-drawer="true"
     data-kt-drawer-class="kt-drawer kt-drawer-start fixed z-10 top-0 bottom-0 w-full me-5 max-w-[250px] p-5 lg:p-0 overflow-auto"
     id="navbar">
    <!-- Container -->
    <div class="kt-container-fixed lg:flex lg:flex-wrap lg:justify-between lg:items-center gap-2 px-0 lg:px-7.5">
        <!-- Mega Menu -->
        <div class="kt-menu items-stretch flex-col lg:flex-row gap-5 lg:gap-7.5 grow lg:grow-0" data-kt-menu="true"
             id="mega_menu">
            <div class="kt-menu-item">
                <a class="kt-menu-link lg:py-3.5 border-b border-b-transparent kt-menu-item-active:border-b-mono text-foreground kt-menu-item-hover:text-mono kt-menu-item-active:text-mono kt-menu-item-here:border-b-mono kt-menu-item-here:text-mono"
                   href="{{ route('home') }}">
                    <span class="kt-menu-title font-medium text-foreground text-sm grow-0">Home</span>
                </a>
            </div>
            <div class="kt-menu-item">
                <a class="kt-menu-link lg:py-3.5 border-b border-b-transparent kt-menu-item-active:border-b-mono text-foreground kt-menu-item-hover:text-mono kt-menu-item-active:text-mono kt-menu-item-here:border-b-mono kt-menu-item-here:text-mono"
                   href="{{ route('about') }}">
                    <span class="kt-menu-title font-medium text-foreground text-sm grow-0">About MAMIAS</span>
                </a>
            </div>
            <div class="kt-menu-item" data-kt-menu-item-offset="0,0|lg:-20px, 0"
                 data-kt-menu-item-offset-rtl="0,0|lg:20px, 0" data-kt-menu-item-overflow="true"
                 data-kt-menu-item-placement="bottom-start" data-kt-menu-item-placement-rtl="bottom-end"
                 data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click|lg:hover">
                <div class="kt-menu-link lg:py-3.5 border-b border-b-transparent kt-menu-item-active:border-b-mono text-foreground kt-menu-item-hover:text-mono kt-menu-item-active:text-mono kt-menu-item-here:border-b-mono kt-menu-item-here:text-mono">
                    <span class="kt-menu-title font-medium text-foreground text-sm">Explore MAMIAS</span>
                    <span class="kt-menu-arrow flex lg:hidden">
                        <span class="flex kt-menu-item-show:hidden">
                            <i class="ki-filled ki-plus text-xs text-secondary-foreground"></i>
                        </span>
                        <span class="hidden kt-menu-item-show:inline-flex">
                            <i class="ki-filled ki-minus text-xs text-secondary-foreground"></i>
                        </span>
                    </span>
                </div>
                <div class="kt-menu-dropdown kt-menu-default py-2.5 w-full max-w-[220px]">
                    <div class="kt-menu-item">
                        <a class="kt-menu-link" href="#" tabindex="0">
                            <span class="kt-menu-icon"><i class="ki-filled ki-data"></i></span>
                            <span class="kt-menu-title">Data</span>
                            <span class="kt-menu-badge" data-kt-tooltip="#menu_tooltip_3">
                                <i class="ki-filled ki-information-2 text-muted-foreground text-base"></i>
                            </span>
                            <div class="kt-tooltip" id="menu_tooltip_3">Search MAMIAS.</div>
                        </a>
                    </div>
                    <div class="kt-menu-item" data-kt-menu-item-placement="right-start"
                         data-kt-menu-item-placement-rtl="left-start" data-kt-menu-item-toggle="dropdown"
                         data-kt-menu-item-trigger="click|lg:hover">
                        <div class="kt-menu-link">
                            <span class="kt-menu-icon"><i class="ki-filled ki-information"></i></span>
                            <span class="kt-menu-title">Dashboard</span>
                            <span class="kt-menu-arrow">
                                <i class="ki-filled ki-right text-xs rtl:transform rtl:rotate-180"></i>
                            </span>
                        </div>
                        <div class="kt-menu-dropdown kt-menu-default w-full max-w-[175px] lg:max-w-[220px]">
                            <div class="kt-menu-item">
                                <a class="kt-menu-link" href="#" tabindex="0">
                                    <span class="kt-menu-icon"><i class="ki-filled ki-graph"></i></span>
                                    <span class="kt-menu-title grow-0">Mediterranean</span>
                                </a>
                            </div>
                            <div class="kt-menu-item">
                                <a class="kt-menu-link" href="#" tabindex="0">
                                    <span class="kt-menu-icon"><i class="ki-filled ki-graph-up"></i></span>
                                    <span class="kt-menu-title grow-0">By Country</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="kt-menu-item">
                        <a class="kt-menu-link" href="#" tabindex="0">
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
            <div class="kt-menu-item">
                <a class="kt-menu-link lg:py-3.5 border-b border-b-transparent kt-menu-item-active:border-b-mono text-foreground kt-menu-item-hover:text-mono kt-menu-item-active:text-mono kt-menu-item-here:border-b-mono kt-menu-item-here:text-mono"
                   href="#">
                    <span class="kt-menu-title font-medium text-foreground text-sm grow-0">Resources</span>
                </a>
            </div>
        </div>
        <!-- End of Mega Menu -->
    </div>
    <!-- End of Container -->
</div>
<!-- End of Navbar -->

