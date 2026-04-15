@extends('layouts.app')

@section('title', 'PageVitals — Free Core Web Vitals Analyzer')

@section('content')

{{-- ── Hero ────────────────────────────────────────────────────────────────── --}}
<section class="max-w-5xl mx-auto px-5 pt-20 pb-12">
    <div class="max-w-2xl mx-auto text-center">

        {{-- Badge --}}
        <div class="inline-flex items-center gap-2 bg-accent-600/10 border border-accent-500/25
                    text-accent-400 rounded-full px-4 py-1.5 text-xs font-medium tracking-wide mb-7 fade-up">
            Free performance audit
        </div>

        <h1 class="text-4xl sm:text-5xl font-bold text-white leading-tight tracking-tight mb-5 fade-up delay-1">
            Find out exactly why<br />
            <span class="text-accent-400">your site is slow</span>
        </h1>

        <p class="text-gray-400 text-lg leading-relaxed mb-10 fade-up delay-2">
            Enter any URL and get a detailed performance report with
            <span class="text-gray-200 font-medium">specific, actionable fixes</span> —
            not just raw numbers.
        </p>

        {{-- ── Input card ──────────────────────────────────────────────────── --}}
        <div class="bg-navy-900/80 border border-navy-700 rounded-2xl p-6 sm:p-8 shadow-card text-left fade-up delay-2">
            <form action="{{ route('analyze') }}" method="POST" id="analyzeForm">
                @csrf

                <label for="url" class="block text-xs font-medium text-gray-400 uppercase tracking-widest mb-3">
                    Website URL
                </label>

                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 16 16">
                                <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.25"/>
                                <path d="M5.5 8a2.5 2.5 0 0 0 5 0 2.5 2.5 0 0 0-5 0zM8 1.5v2M8 12.5v2M1.5 8h2M12.5 8h2" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <input
                            type="url"
                            id="url"
                            name="url"
                            placeholder="https://yourwebsite.com"
                            value="{{ old('url') }}"
                            required
                            autocomplete="url"
                            class="w-full h-12 pl-10 pr-4 bg-navy-800 border border-navy-600 rounded-xl
                                   text-white placeholder-gray-600 text-[15px]
                                   focus:outline-none focus:border-accent-500 focus:ring-2 focus:ring-accent-500/20
                                   transition-all duration-150"
                        />
                    </div>

                    <button
                        type="submit"
                        id="analyzeBtn"
                        class="h-12 px-6 bg-accent-600 hover:bg-accent-500 active:scale-[0.98]
                               text-white font-semibold text-[15px] rounded-xl
                               transition-all duration-150 flex items-center justify-center gap-2
                               focus:outline-none focus:ring-2 focus:ring-accent-400/40
                               disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap flex-shrink-0">
                        <span id="btnLabel">Analyze site</span>
                        <svg id="btnArrow" class="w-4 h-4" fill="none" viewBox="0 0 16 16">
                            <path d="M3 8h10M9 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <svg id="btnSpinner" class="hidden w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-dashoffset="20" opacity="0.3"/>
                            <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </button>
                </div>

                @error('url')
                    <p class="mt-2.5 text-sm text-red-400 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 1a7 7 0 1 1 0 14A7 7 0 0 1 8 1zm0 9a.75.75 0 1 0 0 1.5A.75.75 0 0 0 8 10zm.75-5.25a.75.75 0 0 0-1.5 0v3.5a.75.75 0 0 0 1.5 0v-3.5z"/>
                        </svg>
                        {{ $message }}
                    </p>
                @enderror

                <p class="mt-4 text-xs text-gray-400 text-center">
                    Analysis takes ~20 seconds · Tests both mobile &amp; desktop · Powered by Google PageSpeed
                </p>
            </form>
        </div>

    </div>
</section>

{{-- ── Feature strip ───────────────────────────────────────────────────────── --}}
<section class="border-t border-navy-700 bg-navy-900/80/50 py-14 mt-4 fade-up delay-3">
    <div class="max-w-5xl mx-auto px-5">

        <p class="text-center text-xs font-medium text-gray-500 uppercase tracking-widest mb-10">
            What you'll get in the report
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

            <div class="bg-navy-900/80 border border-navy-700 rounded-xl p-6 hover:border-navy-600 transition-colors">
                <div class="w-9 h-9 rounded-lg bg-accent-600/10 border border-accent-500/20
                            flex items-center justify-center mb-4 flex-shrink-0">
                    <svg class="w-5 h-5 text-accent-400" fill="none" viewBox="0 0 20 20">
                        <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M10 6v4.5l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white mb-2">Performance Score</h3>
                <p class="text-sm text-gray-500 leading-relaxed">
                    Mobile <em>and</em> desktop Lighthouse scores — the exact metrics Google uses for search ranking.
                </p>
            </div>

            <div class="bg-navy-900/80 border border-navy-700 rounded-xl p-6 hover:border-navy-600 transition-colors">
                <div class="w-9 h-9 rounded-lg bg-accent-600/10 border border-accent-500/20
                            flex items-center justify-center mb-4 flex-shrink-0">
                    <svg class="w-5 h-5 text-accent-400" fill="none" viewBox="0 0 20 20">
                        <rect x="2" y="10" width="3.5" height="8" rx="1" stroke="currentColor" stroke-width="1.5"/>
                        <rect x="8.25" y="6" width="3.5" height="12" rx="1" stroke="currentColor" stroke-width="1.5"/>
                        <rect x="14.5" y="2" width="3.5" height="16" rx="1" stroke="currentColor" stroke-width="1.5"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white mb-2">Core Web Vitals</h3>
                <p class="text-sm text-gray-500 leading-relaxed">
                    LCP, CLS, INP, FCP, TBT — colour-coded against Google's Good / Needs Work / Poor thresholds.
                </p>
            </div>

            <div class="bg-navy-900/80 border border-navy-700 rounded-xl p-6 hover:border-navy-600 transition-colors">
                <div class="w-9 h-9 rounded-lg bg-accent-600/10 border border-accent-500/20
                            flex items-center justify-center mb-4 flex-shrink-0">
                    <svg class="w-5 h-5 text-accent-400" fill="none" viewBox="0 0 20 20">
                        <path d="M4 10h12M4 6h8M4 14h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <circle cx="16" cy="14" r="3.5" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M15 14l.8.8L17.5 13" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="font-semibold text-white mb-2">Expert Fix Advice</h3>
                <p class="text-sm text-gray-500 leading-relaxed">
                    Not just "reduce unused CSS." Each issue comes with a plain-English explanation and concrete fix steps.
                </p>
            </div>

        </div>
    </div>
</section>

{{-- ── Trust row ───────────────────────────────────────────────────────────── --}}
<section class="max-w-5xl mx-auto px-5 py-12 fade-up delay-4">
    <div class="flex flex-wrap items-center justify-center gap-8">
        @foreach([
            'Powered by Google PageSpeed API',
            'Mobile & desktop tested',
            'Shareable report link',
            'No account needed',
        ] as $item)
        <span class="flex items-center gap-2 text-sm text-gray-500">
            <svg class="w-4 h-4 text-good flex-shrink-0" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 1a7 7 0 1 1 0 14A7 7 0 0 1 8 1zm3.22 4.72a.75.75 0 0 0-1.06-1.06L7 7.81 5.84 6.65a.75.75 0 1 0-1.06 1.06l1.69 1.7a.75.75 0 0 0 1.06 0l3.69-3.69z"/>
            </svg>
            {{ $item }}
        </span>
        @endforeach
    </div>
</section>

<script>
document.getElementById('analyzeForm').addEventListener('submit', function () {
    const btn     = document.getElementById('analyzeBtn');
    const label   = document.getElementById('btnLabel');
    const arrow   = document.getElementById('btnArrow');
    const spinner = document.getElementById('btnSpinner');
    btn.disabled  = true;
    label.textContent = 'Analyzing…';
    arrow.classList.add('hidden');
    spinner.classList.remove('hidden');
});
</script>

@endsection