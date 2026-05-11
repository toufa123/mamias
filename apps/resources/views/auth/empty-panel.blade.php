<div class="absolute inset-0 overflow-hidden" style="background: linear-gradient(to bottom, #003d61 0%, #005f98 35%, #018d9a 70%, #4cafbf 100%);">

    {{-- Dot grid overlay --}}
    <svg class="absolute inset-0 w-full h-full" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <pattern id="dots" width="28" height="28" patternUnits="userSpaceOnUse">
                <circle cx="14" cy="14" r="1.4" fill="white" opacity="0.55"/>
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#dots)"/>
    </svg>

    {{-- Animated rings --}}
    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div class="mamias-ring mamias-ring-1"></div>
        <div class="mamias-ring mamias-ring-2"></div>
        <div class="mamias-ring mamias-ring-3"></div>
    </div>

    {{-- Centered content --}}
    <div class="absolute inset-0 flex flex-col items-center justify-center px-12 text-white">

        {{-- Logo with float animation --}}
        <div class="mamias-logo-wrap">
            <img src="{{ asset('images/Logoweb.png') }}"
                 alt="MAMIAS"
                 style="height:8rem;filter:brightness(0) invert(1);drop-shadow(0 0 24px rgba(76,175,191,0.6));">
        </div>

        {{-- MAMIAS wordmark --}}
        <div class="mamias-title">MAMIAS</div>

        {{-- Tagline --}}
        <p class="mamias-tagline">
            Marine Mediterranean <br>Invasive Alien Species
        </p>

        {{-- Footer credits --}}
        <div class="mamias-credits">
           
            <span>Since 2012</span>
        </div>
    </div>

    <style>
        /* Floating logo */
        .mamias-logo-wrap {
            animation: mamias-float 4s ease-in-out infinite;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 0 20px rgba(76,175,191,0.5));
        }
        @keyframes mamias-float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-10px); }
        }

        /* Wordmark fade+slide in */
        .mamias-title {
            font-size: 4rem;
            font-weight: 800;
            letter-spacing: 0.3em;
            text-transform: uppercase;
            color: #fff;
            animation: mamias-fadein 1s ease forwards;
            opacity: 0;
            animation-delay: 0.2s;
        }
        @keyframes mamias-fadein {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .mamias-tagline {
            font-size: 1rem;
            opacity: 0;
            text-align: center;
            line-height: 1.7;
            color: rgba(255,255,255,0.75);
            animation: mamias-fadein 1s ease forwards;
            animation-delay: 0.5s;
            margin-top: 0.75rem;
        }

        .mamias-credits {
            margin-top: 2.5rem;
            font-size: 0.82rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.45);
            opacity: 0;
            animation: mamias-fadein 1s ease forwards;
            animation-delay: 0.9s;
            display: flex;
            align-items: center;
        }

        /* Pulsing rings */
        .mamias-ring {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(76,175,191,0.35);
            animation: mamias-pulse 4s ease-out infinite;
        }
        .mamias-ring-1 { width: 320px; height: 320px; animation-delay: 0s; }
        .mamias-ring-2 { width: 500px; height: 500px; animation-delay: 1s; }
        .mamias-ring-3 { width: 700px; height: 700px; animation-delay: 2s; }

        @keyframes mamias-pulse {
            0%   { transform: scale(0.85); opacity: 0.6; }
            60%  { opacity: 0.15; }
            100% { transform: scale(1.15); opacity: 0; }
        }
    </style>
</div>
