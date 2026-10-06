{{--
    Web app manifest, included by the public layout and by the panel's
    HEAD_END hook. Its display is "browser": a standalone app window made
    Chrome/Edge capture every in-scope link (e.g. the email-verification
    link) into that window. Browsers don't fire `beforeinstallprompt` for a
    "browser" display, so the Install app button stays hidden.

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
