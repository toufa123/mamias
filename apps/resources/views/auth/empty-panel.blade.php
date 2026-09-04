{{--
    Decorative panel beside the auth forms (AuthUIEnhancerPlugin).

    Everything is scoped under .mamias-auth so the styles cannot leak into the
    Filament form panel sharing the page, and it is all inline: this view is
    rendered on panel routes that load the Filament theme, not the public
    app.css bundle, so Tailwind utilities defined there are not guaranteed.
--}}
<div class="mamias-auth">
    <div class="mamias-auth-texture" aria-hidden="true"></div>

    <div class="mamias-auth-rings" aria-hidden="true">
        <span class="mamias-auth-ring"></span>
        <span class="mamias-auth-ring"></span>
        <span class="mamias-auth-ring"></span>
    </div>

    <div class="mamias-auth-content">
        <div class="mamias-auth-brand">
            <img src="{{ asset('images/Logoweb.png') }}" alt="MAMIAS" class="mamias-auth-logo" />
        </div>

        <h2 class="mamias-auth-title">
            Marine Mediterranean<br />Invasive Alien Species
        </h2>

        <p class="mamias-auth-lede">
            The regional database for monitoring, reporting and analysing
            Non-Indigenous Species in the Mediterranean Sea.
        </p>

        <div class="mamias-auth-footer">
            <span class="mamias-auth-since">Since 2012</span>
        </div>
    </div>
</div>

<style>
    .mamias-auth {
        position: absolute;
        inset: 0;
        overflow: hidden;
        color: #fff;
        /* Deep water to shallows, with a soft light source behind the logo so
           the panel has a focal point instead of a flat wash. */
        background:
            radial-gradient(115% 70% at 50% 22%, rgba(94, 214, 226, 0.28) 0%, transparent 60%),
            linear-gradient(to bottom, #002b47 0%, #004a78 32%, #00768a 66%, #128fa0 100%);
    }

    /* Dot texture, faded out behind the text. The old panel ran the dots at
       full strength edge to edge, which left the tagline sitting on a busy
       field and hard to read. */
    .mamias-auth-texture {
        position: absolute;
        inset: 0;
        background-image: radial-gradient(circle, rgba(255, 255, 255, 0.4) 1.1px, transparent 1.1px);
        background-size: 26px 26px;
        -webkit-mask-image: radial-gradient(ellipse 62% 48% at 50% 44%, transparent 12%, rgba(0, 0, 0, 0.45) 58%, #000 100%);
        mask-image: radial-gradient(ellipse 62% 48% at 50% 44%, transparent 12%, rgba(0, 0, 0, 0.45) 58%, #000 100%);
        opacity: 0.5;
    }

    .mamias-auth-rings {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }

    .mamias-auth-ring {
        position: absolute;
        border-radius: 50%;
        border: 1px solid rgba(255, 255, 255, 0.16);
        /* Sized against the panel, which is a narrow column on desktop — the
           fixed 700px ring used to spill out of it. */
        width: min(78%, 30rem);
        aspect-ratio: 1;
        animation: mamias-auth-sonar 6s ease-out infinite;
    }
    .mamias-auth-ring:nth-child(2) { animation-delay: 2s; }
    .mamias-auth-ring:nth-child(3) { animation-delay: 4s; }

    @keyframes mamias-auth-sonar {
        0%   { transform: scale(0.55); opacity: 0; }
        18%  { opacity: 0.55; }
        100% { transform: scale(1.35); opacity: 0; }
    }

    .mamias-auth-content {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0;
        padding: 3rem 2.5rem;
        text-align: center;
    }

    .mamias-auth-brand {
        animation: mamias-auth-rise 0.9s ease both;
    }

    .mamias-auth-logo {
        height: 6.5rem;
        /* Two filters in one declaration. The previous markup put the
           drop-shadow after a semicolon, which made it a second, invalid
           declaration that the browser dropped. */
        filter: brightness(0) invert(1) drop-shadow(0 6px 28px rgba(94, 214, 226, 0.45));
    }

    /* The logo already carries the wordmark, so the panel no longer repeats
       "MAMIAS" underneath it in 4rem letters. The name of the thing is the
       picture; the words explain what it is. */
    .mamias-auth-title {
        margin-top: 1.75rem;
        font-size: clamp(1.35rem, 2.4vw, 1.9rem);
        font-weight: 600;
        line-height: 1.3;
        letter-spacing: 0.01em;
        color: #fff;
        text-wrap: balance;
        animation: mamias-auth-rise 0.9s ease 0.12s both;
    }

    .mamias-auth-lede {
        margin-top: 0.9rem;
        max-width: 26rem;
        font-size: 0.95rem;
        line-height: 1.65;
        /* Raised from 0.75 — over the dot field the old tagline sat close to
           the contrast floor. */
        color: rgba(255, 255, 255, 0.86);
        animation: mamias-auth-rise 0.9s ease 0.24s both;
    }

    .mamias-auth-footer {
        position: absolute;
        bottom: 2.25rem;
        display: flex;
        align-items: center;
        animation: mamias-auth-rise 0.9s ease 0.5s both;
    }

    .mamias-auth-since {
        font-size: 0.75rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.7);
    }

    @keyframes mamias-auth-rise {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: none; }
    }

    /* Short viewports: shrink, then drop the supporting copy before it can
       collide with the footer rather than letting the column overflow. */
    @media (max-height: 760px) {
        .mamias-auth-logo { height: 5rem; }
    }

    @media (max-height: 620px) {
        .mamias-auth-lede { display: none; }
        .mamias-auth-footer { position: static; margin-top: 2rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .mamias-auth-ring { animation: none; opacity: 0.28; }
        .mamias-auth-brand,
        .mamias-auth-title,
        .mamias-auth-lede,
        .mamias-auth-footer {
            animation: none;
        }
    }
</style>
