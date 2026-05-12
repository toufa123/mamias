<div class="flex items-center gap-2 lg:gap-3.5 lg:w-[400px] justify-end">
    <div class="flex items-center gap-2 me-0.5">
        @php($role = Auth::user()?->getRoleNames()->first() ?? 'User')
        @php($isSpecial = Auth::user()?->hasAnyRole(['super_admin', 'panel_user']) ?? false)
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
                                    <div class="kt-avatar-image size-14 rounded-full border-2 border-primary overflow-hidden">
                                        <img class="size-full object-cover" src="{{ Auth::user()?->getFilamentAvatarUrl() ?? asset('img/avatar.png') }}"
                                             alt="{{ Auth::user()?->getFilamentName() ?? 'Avatar' }}">
                                    </div>
                                    <span class="absolute -bottom-0.5 -end-0.5 block size-3.5 rounded-full border-2 border-white {{ $isSpecial ? 'bg-green-500' : 'bg-gray-400' }}"></span>
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
                                <a class="kt-dropdown-menu-link" href="{{ route('profile') }}">
                                    <i class="ki-filled ki-badge"></i>
                                    Public Profile
                                </a>
                            </li>
                                <div class="kt-separator my-2.5"></div>
                            <li>
                                    <a class="kt-dropdown-menu-link" href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-clipboard-plus">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                                            <path d="M9 5a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2" />
                                            <path d="M10 14h4" />
                                            <path d="M12 12v4" />
                                        </svg>
                                        Report Species
                                    </a>
                            </li>
                            <li>
                                    <a class="kt-dropdown-menu-link" href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-cylinder-plus">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M5 6a7 3 0 1 0 14 0a7 3 0 1 0 -14 0" />
                                            <path d="M5 6v12c0 1.657 3.134 3 7 3c.173 0 .345 -.003 .515 -.008m6.485 -8.992v-6" />
                                            <path d="M16 19h6" />
                                            <path d="M19 16v6" />
                                        </svg>
                                        Add Bibliographic reference
                                    </a>
                            </li>
                            <li>
                                    <a class="kt-dropdown-menu-link" href="#">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-messages">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                            <path d="M21 14l-3 -3h-7a1 1 0 0 1 -1 -1v-6a1 1 0 0 1 1 -1h9a1 1 0 0 1 1 1v10" />
                                            <path d="M14 15v2a1 1 0 0 1 -1 1h-7l-3 3v-10a1 1 0 0 1 1 -1h2" />
                                        </svg>
                                        Suggestions
                                    </a>
                            </li>

                        </ul>
                        <div class="kt-separator my-2.5"></div>
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
