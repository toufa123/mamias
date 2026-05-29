<style>
    .fi-mobile-notice {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: linear-gradient(135deg, #003d61 0%, #005f98 40%, #018d9a 75%, #4cafbf 100%);
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 2rem;
        color: #fff;
    }

    .fi-mobile-notice svg {
        width: 4rem;
        height: 4rem;
        margin-bottom: 1.5rem;
        opacity: 0.9;
    }

    .fi-mobile-notice h2 {
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        margin-bottom: 0.75rem;
        font-family: monospace;
    }

    .fi-mobile-notice p {
        font-size: 1rem;
        max-width: 28rem;
        line-height: 1.6;
        color: rgba(255, 255, 255, 0.8);
    }

    @media (width < 48rem) {
        .fi-mobile-notice { display: flex; }
        .fi-sidebar-nav,
        .fi-main-content { display: none !important; }
    }

    @media (width >= 48rem) {
        .fi-mobile-notice { display: none !important; }
    }
</style>

<div class="fi-mobile-notice">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="4" y="2" width="16" height="20" rx="2" ry="2"/>
        <line x1="12" y1="18" x2="12.01" y2="18"/>
    </svg>
    <h2>MAMIAS — Desktop Only</h2>
    <p>The management panel is not available on mobile devices. Please use a tablet or desktop computer to access this application.</p>
</div>
