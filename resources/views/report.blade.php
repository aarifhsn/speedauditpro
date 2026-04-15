@extends('layouts.app')

@section('title', 'Performance Report — ' . parse_url($report->url, PHP_URL_HOST))

@section('content')

    @php
        /* ── Colour helpers ───────────────────────────────────────────────── */
        $scoreTheme = fn(int $s) => match (true) {
            $s >= 90 => ['ring' => '#22c55e', 'text' => 'text-green-400', 'bg' => 'bg-green-500/10', 'border' => 'border-green-500/25', 'label' => 'Good'],
            $s >= 50 => ['ring' => '#f59e0b', 'text' => 'text-amber-400', 'bg' => 'bg-amber-500/10', 'border' => 'border-amber-500/25', 'label' => 'Needs work'],
            default => ['ring' => '#ef4444', 'text' => 'text-red-400', 'bg' => 'bg-red-500/10', 'border' => 'border-red-500/25', 'label' => 'Poor'],
        };

        $mt = $scoreTheme($mobileScore);
        $dt = $desktopScore ? $scoreTheme($desktopScore) : null;

        $mobileOffset = 314 - ($mobileScore / 100 * 314);
        $desktopOffset = $desktopScore ? 314 - ($desktopScore / 100 * 314) : 314;

        $vitalTheme = fn(string $s) => match ($s) {
            'good' => ['text' => 'text-green-400', 'num' => 'text-green-300', 'bg' => 'bg-green-500/8', 'border' => 'border-green-500/20', 'dot' => 'bg-green-400'],
            'needs-improvement' => ['text' => 'text-amber-400', 'num' => 'text-amber-300', 'bg' => 'bg-amber-500/8', 'border' => 'border-amber-500/20', 'dot' => 'bg-amber-400'],
            'poor' => ['text' => 'text-red-400', 'num' => 'text-red-300', 'bg' => 'bg-red-500/8', 'border' => 'border-red-500/20', 'dot' => 'bg-red-400'],
            default => ['text' => 'text-gray-400', 'num' => 'text-gray-300', 'bg' => 'bg-white/3', 'border' => 'border-white/8', 'dot' => 'bg-gray-500'],
        };

        $effortMap = [
            'low' => ['label' => 'Quick win', 'class' => 'text-green-400 bg-green-500/10 border-green-500/25'],
            'medium' => ['label' => 'Moderate effort', 'class' => 'text-amber-400 bg-amber-500/10 border-amber-500/25'],
            'high' => ['label' => 'Significant effort', 'class' => 'text-red-400   bg-red-500/10   border-red-500/25'],
        ];

        $impactMap = [
            'high' => ['label' => 'High impact', 'class' => 'text-accent-400 bg-accent-600/10 border-accent-500/25'],
            'medium' => ['label' => 'Medium impact', 'class' => 'text-gray-300   bg-white/5       border-white/10'],
            'low' => ['label' => 'Low impact', 'class' => 'text-gray-500   bg-white/3       border-white/8'],
        ];

        $shareUrl = route('report.show', $report->slug);
        $host = parse_url($report->url, PHP_URL_HOST);
    @endphp

    <div class="max-w-5xl mx-auto px-5 pt-10 pb-24 space-y-10">

        {{-- ── Page header ──────────────────────────────────────────────────────── --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 fade-up">
            <div>
                <p class="text-gray-600 text-xs font-mono mb-1.5">performance report</p>
                <h1 class="text-white font-bold text-2xl break-all">{{ $host }}</h1>
                <p class="text-gray-600 text-xs font-mono mt-1 break-all">{{ $report->url }}</p>
                <p class="text-gray-600 text-xs mt-1.5">Analyzed {{ $report->created_at->diffForHumans() }}</p>
            </div>

            <button onclick="copyShareLink()" id="shareBtn" class="inline-flex items-center gap-2 bg-navy-800 border border-navy-600
                               text-gray-400 hover:text-gray-200 hover:border-navy-500
                               rounded-lg px-4 py-2.5 text-sm transition-all duration-150
                               self-start flex-shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16">
                    <path d="M10.5 1.5h4v4M14.5 1.5l-6 6M6 3H2.5a1 1 0 0 0-1 1v9a1 1 0 0 0 1 1h9a1 1 0 0 0 1-1V9.5"
                        stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <span id="shareBtnLabel">Share report</span>
            </button>
        </div>

        {{-- ── Performance Scores ───────────────────────────────────────────────── --}}
        <section class="fade-up delay-1">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-widest mb-4">Performance Scores</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">

                {{-- Mobile --}}
                <div class="bg-navy-900 border {{ $mt['border'] }} rounded-2xl p-5 flex items-center gap-5 shadow-card">
                    <div class="relative w-[88px] h-[88px] flex-shrink-0">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="50" stroke="rgba(255,255,255,0.07)" stroke-width="10" fill="none" />
                            <circle cx="60" cy="60" r="50" stroke="{{ $mt['ring'] }}" stroke-width="10" fill="none"
                                stroke-linecap="round" class="score-arc" style="--offset:{{ $mobileOffset }}" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span
                                class="{{ $mt['text'] }} text-[28px] font-bold leading-none font-mono">{{ $mobileScore }}</span>
                            <span class="text-gray-600 text-[10px] mt-0.5">/ 100</span>
                        </div>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 16 16">
                                <rect x="4" y="1.5" width="8" height="13" rx="1.5" stroke="currentColor"
                                    stroke-width="1.25" />
                                <path d="M7 12.5h2" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" />
                            </svg>
                            <span class="text-sm font-semibold text-white">Mobile</span>
                        </div>
                        <p class="text-xs text-gray-500 leading-relaxed mb-2.5">
                            Google ranks sites on mobile performance. This is the most critical score.
                        </p>
                        <span
                            class="{{ $mt['bg'] }} {{ $mt['border'] }} {{ $mt['text'] }} border text-xs font-semibold px-2.5 py-1 rounded-full">
                            {{ $mt['label'] }}
                        </span>
                    </div>
                </div>

                {{-- Desktop --}}
                @if($desktopScore && $dt)
                    <div class="bg-navy-900 border {{ $dt['border'] }} rounded-2xl p-5 flex items-center gap-5 shadow-card">
                        <div class="relative w-[88px] h-[88px] flex-shrink-0">
                            <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="50" stroke="rgba(255,255,255,0.07)" stroke-width="10" fill="none" />
                                <circle cx="60" cy="60" r="50" stroke="{{ $dt['ring'] }}" stroke-width="10" fill="none"
                                    stroke-linecap="round" class="score-arc" style="--offset:{{ $desktopOffset }}" />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span
                                    class="{{ $dt['text'] }} text-[28px] font-bold leading-none font-mono">{{ $desktopScore }}</span>
                                <span class="text-gray-600 text-[10px] mt-0.5">/ 100</span>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" viewBox="0 0 16 16">
                                    <rect x="1.5" y="2.5" width="13" height="9" rx="1.25" stroke="currentColor"
                                        stroke-width="1.25" />
                                    <path d="M5.5 13.5h5M8 11.5v2" stroke="currentColor" stroke-width="1.25"
                                        stroke-linecap="round" />
                                </svg>
                                <span class="text-sm font-semibold text-white">Desktop</span>
                            </div>
                            <p class="text-xs text-gray-500 leading-relaxed mb-2.5">
                                Usually higher than mobile. Fixes that improve mobile almost always help desktop too.
                            </p>
                            <span
                                class="{{ $dt['bg'] }} {{ $dt['border'] }} {{ $dt['text'] }} border text-xs font-semibold px-2.5 py-1 rounded-full">
                                {{ $dt['label'] }}
                            </span>
                        </div>
                    </div>
                @endif

            </div>
        </section>

        {{-- ── Core Web Vitals ──────────────────────────────────────────────────── --}}
        <section class="fade-up delay-2">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-widest">Core Web Vitals</p>
                <span class="text-xs text-gray-600">Mobile · measured against Google's thresholds</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                @foreach($vitals as $vital)
                    @php $vt = $vitalTheme($vital['status']); @endphp
                    <div class="{{ $vt['bg'] }} border {{ $vt['border'] }} rounded-xl p-4">
                        <div class="flex items-center gap-1.5 mb-2.5">
                            <span class="w-1.5 h-1.5 rounded-full {{ $vt['dot'] }} flex-shrink-0"></span>
                            <span class="{{ $vt['text'] }} text-[11px] font-semibold font-mono">{{ $vital['label'] }}</span>
                        </div>
                        <p class="{{ $vt['num'] }} text-xl font-bold font-mono leading-none">{{ $vital['value'] }}</p>
                        <p class="text-gray-600 text-[10px] mt-1.5 leading-tight">{{ $vital['fullName'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Legend --}}
            <div class="flex items-center gap-5 mt-3 flex-wrap">
                <span class="flex items-center gap-1.5 text-xs text-gray-600"><span
                        class="w-2 h-2 rounded-full bg-green-400"></span>Good</span>
                <span class="flex items-center gap-1.5 text-xs text-gray-600"><span
                        class="w-2 h-2 rounded-full bg-amber-400"></span>Needs improvement</span>
                <span class="flex items-center gap-1.5 text-xs text-gray-600"><span
                        class="w-2 h-2 rounded-full bg-red-400"></span>Poor</span>
            </div>
        </section>

        {{-- ── Issues & Fixes ───────────────────────────────────────────────────── --}}
        <section class="fade-up delay-3">
            <div class="flex items-center gap-3 mb-5 flex-wrap">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-widest">Issues & Fix Recommendations</p>
                @if(count($issues) > 0)
                    <span
                        class="bg-red-500/10 border border-red-500/25 text-red-400 text-xs font-semibold px-2.5 py-0.5 rounded-full">
                        {{ count($issues) }} {{ Str::plural('issue', count($issues)) }} found
                    </span>
                @endif
            </div>

            @if(count($issues) === 0)

                <div class="bg-green-500/8 border border-green-500/20 rounded-2xl p-10 text-center">
                    <div
                        class="w-12 h-12 rounded-full bg-green-500/15 border border-green-500/25 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-green-300 mb-1">No major issues found</h3>
                    <p class="text-sm text-gray-500">This site is already well-optimized.</p>
                </div>

            @else

                <div class="space-y-3">
                    @foreach($issues as $key => $issue)
                        @php
                            $effort = $issue['fix']['effort'] ?? 'medium';
                            $impact = $issue['fix']['impact'] ?? 'medium';
                            $em = $effortMap[$effort] ?? $effortMap['medium'];
                            $im = $impactMap[$impact] ?? $impactMap['medium'];
                            $idx = $loop->iteration;
                        @endphp

                        <div class="bg-navy-900 border border-navy-700 rounded-2xl overflow-hidden
                                                hover:border-navy-600 transition-colors duration-150 shadow-card">

                            <div class="p-5">
                                {{-- Title row --}}
                                <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">

                                    {{-- Number --}}
                                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-navy-700 border border-navy-600
                                                             text-gray-500 text-[11px] font-mono font-semibold
                                                             flex items-center justify-center mt-0.5">
                                        {{ $idx }}
                                    </span>

                                    <div class="flex-1 min-w-0">

                                        {{-- Headline + badges --}}
                                        <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                                            <h3 class="font-semibold text-white text-[15px] leading-snug">
                                                {{ $issue['fix']['headline'] }}
                                            </h3>
                                            <div class="flex items-center gap-1.5 flex-shrink-0 flex-wrap">
                                                <span
                                                    class="{{ $im['class'] }} border text-[11px] font-medium px-2.5 py-0.5 rounded-full whitespace-nowrap">
                                                    {{ $im['label'] }}
                                                </span>
                                                <span
                                                    class="{{ $em['class'] }} border text-[11px] font-medium px-2.5 py-0.5 rounded-full whitespace-nowrap">
                                                    {{ $em['label'] }}
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Measurement pill --}}
                                        @if($issue['displayValue'])
                                            <span
                                                class="inline-flex items-center text-[11px] font-mono text-gray-500
                                                                         bg-navy-800 border border-navy-600 rounded-md px-2.5 py-1 mb-3">
                                                {{ $issue['displayValue'] }}
                                            </span>
                                        @endif

                                        {{-- Detail --}}
                                        <p class="text-sm text-gray-400 leading-relaxed mb-4">
                                            {{ $issue['fix']['detail'] }}
                                        </p>

                                        {{-- Tools --}}
                                        @if(!empty($issue['fix']['tools']))
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-xs text-gray-600 font-medium">Fix with:</span>
                                                @foreach($issue['fix']['tools'] as $tool)
                                                    <span
                                                        class="text-[11px] font-mono text-gray-400 bg-navy-800 border border-navy-600 rounded-md px-2.5 py-1">
                                                        {{ $tool }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif

                                    </div>
                                </div>
                            </div>

                        </div>
                    @endforeach
                </div>

            @endif
        </section>

        {{-- ── CTA ───────────────────────────────────────────────────────────────── --}}
        <section class="fade-up delay-4">
            <div class="bg-navy-900 border border-navy-700 rounded-2xl p-8 sm:p-10 text-center
                            relative overflow-hidden">

                {{-- Subtle accent glow behind --}}
                <div
                    class="absolute inset-0 bg-gradient-to-b from-accent-600/5 to-transparent pointer-events-none rounded-2xl">
                </div>

                <div class="relative">
                    <h2 class="text-2xl font-bold text-white mb-3">Want these issues fixed?</h2>
                    <p class="text-gray-400 leading-relaxed max-w-xl mx-auto mb-8">
                        I fix Core Web Vitals and performance issues for businesses.
                        Most sites see a <span class="text-white font-semibold">15–40 point score improvement</span>
                        in 2–3 days — with real speed gains for your visitors.
                    </p>

                    <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                        {{-- 👇 Replace with your Calendly link --}}
                        <a href="https://calendly.com/aarifhsn/30min" target="_blank" rel="noopener" class="inline-flex items-center gap-2.5 bg-accent-600 hover:bg-accent-500
                                      text-white font-semibold text-[15px] px-7 py-3.5 rounded-xl
                                      transition-colors duration-150 w-full sm:w-auto justify-center">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 16 16">
                                <rect x="1.5" y="3" width="13" height="11" rx="1.25" stroke="currentColor"
                                    stroke-width="1.25" />
                                <path d="M1.5 6.5h13M5 1.5V5M11 1.5V5" stroke="currentColor" stroke-width="1.25"
                                    stroke-linecap="round" />
                            </svg>
                            Book a free 15-min call
                        </a>

                        {{-- 👇 Replace 01750128167 with your number --}}
                        <a href="https://wa.me/01750128167?text={{ urlencode('Hi! I ran a speed audit on ' . $report->url . ' and would love your help fixing the issues.') }}"
                            target="_blank" rel="noopener" class="inline-flex items-center gap-2.5 bg-navy-800 hover:bg-navy-700
                                      border border-navy-600 hover:border-navy-500
                                      text-white font-semibold text-[15px] px-7 py-3.5 rounded-xl
                                      transition-all duration-150 w-full sm:w-auto justify-center">
                            <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 16 16">
                                <path
                                    d="M8 .5a7.5 7.5 0 0 1 6.5 11.22L16 15.5l-3.88-1.48A7.5 7.5 0 1 1 8 .5zm0 1.5A6 6 0 1 0 12.4 12.5l.16.06 2.19.83-.84-2.13.09-.18A6 6 0 0 0 8 2zm-1.8 2.82c.17 0 .34.01.47.04.15.04.32.11.48.47l.62 1.44c.1.23.06.49-.07.69l-.45.62a.32.32 0 0 0-.03.36c.28.47.74 1.03 1.22 1.4.49.39 1.07.66 1.53.8a.32.32 0 0 0 .34-.11l.5-.66c.17-.22.42-.3.67-.2l1.5.6c.37.14.46.31.48.47.06.35.02 1.16-.69 1.55-.7.38-1.62.43-2.87-.28-1.25-.72-2.52-2-3.16-3.24-.63-1.22-.38-2.14-.09-2.7.3-.55.81-.7 1.05-.7z" />
                            </svg>
                            WhatsApp me
                        </a>
                    </div>

                    <p class="mt-6 text-xs text-gray-600">
                        Share this report:
                        <button onclick="copyShareLink()"
                            class="text-gray-500 hover:text-gray-300 underline underline-offset-2 transition-colors ml-1 font-mono">
                            {{ $shareUrl }}
                        </button>
                    </p>
                </div>
            </div>
        </section>

        {{-- Footer meta --}}
        <p class="text-center text-gray-700 text-xs font-mono fade-up delay-4">
            analyzed {{ $report->created_at->diffForHumans() }} ·
            <a href="{{ route('index') }}" class="hover:text-gray-500 transition-colors">run another ↗</a>
        </p>

    </div>

    <input type="hidden" id="shareUrlInput" value="{{ $shareUrl }}" />

    <script>
        function copyShareLink() {
            navigator.clipboard.writeText(document.getElementById('shareUrlInput').value).then(() => {
                document.getElementById('shareBtnLabel').textContent = 'Copied!';
                setTimeout(() => document.getElementById('shareBtnLabel').textContent = 'Share report', 2000);
            });
        }
    </script>

@endsection