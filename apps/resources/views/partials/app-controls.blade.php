{{--
    "Install app" (only while the browser offers it) and a fullscreen toggle.
    Shared by the public header and the panel topbar.
--}}
<div
    x-data="{
        canInstall: !! window.mamiasInstallPrompt,
        canFullscreen: document.fullscreenEnabled,
        isFullscreen: !! document.fullscreenElement,
        install() {
            window.mamiasInstallPrompt?.prompt();
            window.mamiasInstallPrompt = null;
            this.canInstall = false;
        },
        toggleFullscreen() {
            document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen();
        },
    }"
    x-on:mamias-installable.window="canInstall = !! window.mamiasInstallPrompt"
    x-on:fullscreenchange.document="isFullscreen = !! document.fullscreenElement"
    class="flex items-center gap-1"
>
    <x-filament::icon-button
        x-cloak
        x-show="canInstall"
        x-on:click="install()"
        icon="heroicon-o-arrow-down-tray"
        color="gray"
        :tooltip="__('Install MAMIAS as an app')"
        :label="__('Install app')"
    />

    <span x-cloak x-show="canFullscreen && ! isFullscreen">
        <x-filament::icon-button
            x-on:click="toggleFullscreen()"
            icon="heroicon-o-arrows-pointing-out"
            color="gray"
            :tooltip="__('Full screen')"
            :label="__('Full screen')"
        />
    </span>
    <span x-cloak x-show="isFullscreen">
        <x-filament::icon-button
            x-on:click="toggleFullscreen()"
            icon="heroicon-o-arrows-pointing-in"
            color="gray"
            :tooltip="__('Exit full screen')"
            :label="__('Exit full screen')"
        />
    </span>
</div>
