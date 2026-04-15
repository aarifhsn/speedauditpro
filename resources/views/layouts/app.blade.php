<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'speedAuditPro — Core Web Vitals Analyzer')</title>
    <meta name="description"
        content="Get a free expert performance report for any website. Understand exactly what's slowing you down — and how to fix it." />

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"DM Sans"', 'system-ui', 'sans-serif'],
                        mono: ['"DM Mono"', 'monospace'],
                    },
                    colors: {
                        /* ── Single design-token palette ── */
                        navy: {
                            950: '#0d1117',   /* page background */
                            900: '#13192a',   /* card background */
                            800: '#1a2235',   /* card hover / input bg */
                            700: '#22304a',   /* borders */
                            600: '#2e3f5e',   /* subtle borders */
                        },
                        accent: {
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                        },
                        /* Status colours — used consistently everywhere */
                        good: { DEFAULT: '#22c55e', light: 'rgba(34,197,94,0.12)', border: 'rgba(34,197,94,0.25)' },
                        warn: { DEFAULT: '#f59e0b', light: 'rgba(245,158,11,0.12)', border: 'rgba(245,158,11,0.25)' },
                        poor: { DEFAULT: '#ef4444', light: 'rgba(239,68,68,0.12)', border: 'rgba(239,68,68,0.25)' },
                    },
                    boxShadow: {
                        card: '0 1px 3px rgba(0,0,0,0.3), 0 1px 2px rgba(0,0,0,0.2)',
                        glow: '0 0 0 3px rgba(59,130,246,0.25)',
                    },
                }
            }
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=DM+Mono:wght@400;500&display=swap"
        rel="stylesheet" />

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            -webkit-font-smoothing: antialiased;
        }

        body {
            font-family: 'DM Sans', system-ui, sans-serif;
        }

        /* Score ring */
        @keyframes ring-in {
            from {
                stroke-dashoffset: 314;
            }

            to {
                stroke-dashoffset: var(--offset);
            }
        }

        .score-arc {
            stroke-dasharray: 314;
            stroke-dashoffset: 314;
            animation: ring-in 1s cubic-bezier(0.4, 0, 0.2, 1) 0.3s forwards;
        }

        /* Page fade-up */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-up {
            animation: fadeUp 0.45s ease both;
        }

        .delay-1 {
            animation-delay: 0.08s;
        }

        .delay-2 {
            animation-delay: 0.16s;
        }

        .delay-3 {
            animation-delay: 0.24s;
        }

        .delay-4 {
            animation-delay: 0.32s;
        }

        /* Subtle dot-grid page texture */
        body {
            background-color: #0d1117;
            background-image: radial-gradient(rgba(255, 255, 255, 0.60) 1px, transparent 1px), url('/speedaudit-bg.webp');
            background-size: 28px 28px, cover;
            background-position: 0 0, center;
            background-attachment: scroll, fixed;
        }
    </style>
</head>

<body class="min-h-full text-gray-100">

    {{-- ── Dark Overlay ────────────────────────────────────────────────────── --}}
    <div class="fixed inset-0 bg-black/90 z-0 pointer-events-none"></div>

    {{-- ── Nav ─────────────────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-50 border-b border-navy-700 bg-navy-950/90 backdrop-blur-sm">
        <div class="max-w-5xl mx-auto px-5 h-14 flex items-center justify-between">

            <a href="{{ route('index') }}" class="flex items-center gap-2.5 group">
                <div class="w-7 h-7 rounded-lg bg-accent-600 flex items-center justify-center flex-shrink-0
                            group-hover:bg-accent-500 transition-colors">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 16 16">
                        <path d="M2.5 12.5L8 3.5l5.5 9H2.5z" stroke="currentColor" stroke-width="1.5"
                            stroke-linejoin="round" />
                        <path d="M5.5 9.5h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </div>
                <span class="font-semibold text-white text-[15px]">{{ config('app.name') }}</span>
            </a>

            <a href="{{ route('index') }}"
                class="text-sm text-gray-500 hover:text-gray-300 transition-colors flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 14 14">
                    <path d="M11 7H3M6 4L3 7l3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
                New analysis
            </a>
        </div>
    </header>

    {{-- ── Flash errors ─────────────────────────────────────────────────────── --}}
    @if(session('error'))
        <div class="max-w-5xl mx-auto px-5 pt-5">
            <div class="flex gap-3 bg-red-900/30 border border-red-500/30 text-red-300 rounded-xl px-4 py-3 text-sm">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 16 16">
                    <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.5" />
                    <path d="M8 5v3M8 10v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="max-w-5xl mx-auto px-5 pt-5">
            <div class="flex gap-3 bg-red-900/30 border border-red-500/30 text-red-300 rounded-xl px-4 py-3 text-sm">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 16 16">
                    <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.5" />
                    <path d="M8 5v3M8 10v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
                {{ $errors->first() }}
            </div>
        </div>
    @endif

    <main>@yield('content')</main>

    {{-- ── Footer ─────────────────────────────────────────────────────────── --}}
    <footer class="border-t border-navy-700 mt-24 py-8">
        <div class="max-w-5xl mx-auto px-5 flex items-center justify-between flex-wrap gap-4 z-20 relative ">
            <div class="flex items-center gap-2">
                <div class="w-5 h-5 rounded-md bg-accent-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 16 16">
                        <path d="M2.5 12.5L8 3.5l5.5 9H2.5z" stroke="currentColor" stroke-width="1.5"
                            stroke-linejoin="round" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-200">{{ config('app.name') }}</span>
            </div>
            <p class="text-sm text-gray-200">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </footer>

</body>

</html>