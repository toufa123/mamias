{{--
    Makes MAMIAS installable as a desktop/mobile app (Edge/Chrome "Install
    app"), opening in its own window like YouTube. Included by the public
    layout and by the panel's HEAD_END hook, so one install covers both.

    The browser fires `beforeinstallprompt` once, early — often before Alpine
    boots — so it is captured here and replayed to the app-controls buttons.
--}}
<link rel="manifest" href="/manifest.webmanifest" />
<meta name="theme-color" content="#056273" />
<script>
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        window.mamiasInstallPrompt = event;
        window.dispatchEvent(new CustomEvent('mamias-installable'));
    });
    window.addEventListener('appinstalled', () => {
        window.mamiasInstallPrompt = null;
        window.dispatchEvent(new CustomEvent('mamias-installable'));
    });
</script>
