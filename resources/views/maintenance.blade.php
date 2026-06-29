<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Maintenance — EduPay Cameroun</title>
    @vite(['resources/css/app.css'])
    <style>
        @keyframes ep-spin-cw  { to { transform: rotate(360deg); } }
        @keyframes ep-spin-ccw { to { transform: rotate(-360deg); } }
        @keyframes ep-halo-pulse {
            0%   { transform: scale(0.9);  opacity: .6; }
            50%  { transform: scale(1.3);  opacity: .2; }
            100% { transform: scale(0.9);  opacity: .6; }
        }
        @keyframes ep-dot-bounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: .35; }
            40%           { transform: scale(1);   opacity: 1; }
        }

        .ep-loader {
            position: relative;
            width: 160px;
            height: 140px;
            margin: 6px auto 22px;
        }
        .ep-halo {
            position: absolute;
            top: 50%; left: 50%;
            width: 130px;
            height: 130px;
            margin: -65px 0 0 -65px;
            border-radius: 9999px;
            background: radial-gradient(circle, rgba(232,160,32,0.55) 0%, rgba(232,160,32,0) 70%);
            filter: blur(4px);
            animation: ep-halo-pulse 2.4s ease-in-out infinite;
            z-index: 0;
        }
        .ep-gear {
            position: absolute;
            z-index: 1;
            transform-origin: center;
        }
        .ep-gear svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        /* Grand engrenage central */
        .ep-gear-1 {
            width: 64px; height: 64px;
            top: 38px; left: 48px;
            color: #E8A020;
            filter: drop-shadow(0 3px 4px rgba(232,160,32,0.3));
            animation: ep-spin-cw 6s linear infinite;
        }
        /* Engrenage moyen, en haut a droite, engrene */
        .ep-gear-2 {
            width: 42px; height: 42px;
            top: 18px; left: 96px;
            color: #0D9E75;
            filter: drop-shadow(0 2px 3px rgba(13,158,117,0.3));
            animation: ep-spin-ccw 4s linear infinite;
        }
        /* Petit engrenage en bas a gauche, engrene */
        .ep-gear-3 {
            width: 32px; height: 32px;
            top: 88px; left: 18px;
            color: #185FA5;
            filter: drop-shadow(0 2px 3px rgba(24,95,165,0.3));
            animation: ep-spin-ccw 3s linear infinite;
        }
        .ep-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            margin: 0 2px;
            background: #0D9E75;
            border-radius: 9999px;
            animation: ep-dot-bounce 1.4s ease-in-out infinite;
        }
        .ep-dot:nth-child(2) { animation-delay: .15s; }
        .ep-dot:nth-child(3) { animation-delay: .3s; }

        .ep-gear-icon { fill: none; stroke: currentColor; stroke-width: 1.7; }
    </style>
</head>
<body class="h-full bg-gray-100 flex flex-col items-center justify-center font-sans antialiased text-center px-6">
    <div class="text-2xl font-bold text-[#0B2545] mb-2">
        Edu<span class="text-[#0D9E75]">Pay</span> Cameroun
    </div>

    <div class="ep-loader">
        <div class="ep-halo"></div>

        <div class="ep-gear ep-gear-1">
            <svg viewBox="0 0 24 24" class="ep-gear-icon">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
        </div>

        <div class="ep-gear ep-gear-2">
            <svg viewBox="0 0 24 24" class="ep-gear-icon">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
        </div>

        <div class="ep-gear ep-gear-3">
            <svg viewBox="0 0 24 24" class="ep-gear-icon">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
        </div>
    </div>

    <h1 class="text-lg font-bold text-gray-900 mb-2">Maintenance en cours</h1>
    <p class="text-sm text-gray-500 max-w-sm">
        La plateforme EduPay Cameroun est temporairement indisponible pour une opération de maintenance.
        Merci de revenir dans quelques instants
        <span class="inline-flex align-middle ml-1">
            <span class="ep-dot"></span><span class="ep-dot"></span><span class="ep-dot"></span>
        </span>
    </p>
</body>
</html>
