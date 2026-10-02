{{--
    "Install app", shown only while the browser offers it.
    Shared by the public header and the panel topbar.
--}}
<div
    x-data="{
        canInstall: !! window.mamiasInstallPrompt,
        install() {
            window.mamiasInstallPrompt?.prompt();
            window.mamiasInstallPrompt = null;
            this.canInstall = false;
        },
    }"
    x-on:mamias-installable.window="canInstall = !! window.mamiasInstallPrompt"
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
</div>
