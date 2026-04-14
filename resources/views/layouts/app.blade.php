<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Core Web Vitals Analyzer')</title>
    <meta name="description"
        content="Get a free, expert-level performance report for any website. Understand exactly what's slowing you down — and how to fix it." />

    {{-- Tailwind CSS (CDN for MVP — swap to Vite/Mix build in production) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        mono: ['"JetBrains Mono"', 'monospace'],
                        display: ['"Space Grotesk"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                        },
                        dark: {
                            900: '#0a0f0d',
                            800: '#0f1a14',
                            700: '#162010',
                            600: '#1e2d1a',
                        }
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Space+Grotesk:wght@400;500;600;700&display=swap"
        rel="stylesheet" />

    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
        }

        code,
        .mono {
            font-family: 'JetBrains Mono', monospace;
        }

        /* Terminal-style scanline effect on score card */
        .scanlines::after {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(0deg,
                    transparent,
                    transparent 2px,
                    rgba(0, 0, 0, 0.03) 2px,
                    rgba(0, 0, 0, 0.03) 4px);
            pointer-events: none;
            border-radius: inherit;
        }

        /* Subtle grid background */
        .grid-bg {
            background-image:
                linear-gradient(rgba(34, 197, 94, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(34, 197, 94, 0.04) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        /* Score ring animation */
        @keyframes ring-fill {
            from {
                stroke-dashoffset: 314;
            }

            to {
                stroke-dashoffset: var(--target-offset);
            }
        }

        .score-ring {
            animation: ring-fill 1.2s cubic-bezier(0.4, 0, 0.2, 1) forwards;
            stroke-dasharray: 314;
            stroke-dashoffset: 314;
        }

        /* Fade-in-up for cards */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-up {
            animation: fadeUp 0.5s ease forwards;
            opacity: 0;
        }

        .delay-1 {
            animation-delay: 0.1s;
        }

        .delay-2 {
            animation-delay: 0.2s;
        }

        .delay-3 {
            animation-delay: 0.3s;
        }

        .delay-4 {
            animation-delay: 0.4s;
        }

        .delay-5 {
            animation-delay: 0.5s;
        }
    </style>
</head>

<body class="min-h-full bg-dark-900 text-gray-100 grid-bg">

    {{-- Nav --}}
    <nav class="border-b border-white/5 bg-dark-900/80 backdrop-blur-sm sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 h-14 flex items-center justify-between">
            <a href="{{ route('index') }}" class="flex items-center gap-2 group">
                <span
                    class="text-brand-400 font-mono text-lg font-semibold group-hover:text-brand-500 transition-colors">&gt;_
                    {{ config('app.name') }}</span>
            </a>
            <a href="{{ route('index') }}"
                class="text-xs font-mono text-gray-500 hover:text-brand-400 transition-colors">
                ← new analysis
            </a>
        </div>
    </nav>

    {{-- Flash error --}}
    @if(session('error'))
        <div class="max-w-5xl mx-auto px-4 pt-4">
            <div class="bg-red-900/30 border border-red-500/30 text-red-300 rounded-lg px-4 py-3 text-sm font-mono">
                ⚠ {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- Main content --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="border-t border-white/5 mt-24 py-8">
        <div class="max-w-5xl mx-auto px-4 text-center text-gray-600 text-xs font-mono">
            cwv-analyzer · built with Laravel + PageSpeed Insights API
        </div>
    </footer>

</body>

</html>