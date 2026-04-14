@extends('layouts.app')

@section('title', 'Core Web Vitals Analyzer — Free Performance Audit')

@section('content')

<div class="max-w-5xl mx-auto px-4 pt-20 pb-32">

    {{-- Hero --}}
    <div class="text-center mb-16 fade-up">
        <div class="inline-flex items-center gap-2 bg-brand-500/10 border border-brand-500/20 rounded-full px-4 py-1.5 mb-6">
            <span class="text-brand-400 text-xs font-mono font-medium tracking-wide">FREE PERFORMANCE AUDIT</span>
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white leading-tight mb-5">
            Why is your website<br />
            <span class="text-brand-400">this slow?</span>
        </h1>

        <p class="text-gray-400 text-lg max-w-xl mx-auto leading-relaxed">
            Enter any URL and get a developer-grade performance report with
            <strong class="text-gray-200">specific, actionable fixes</strong> —
            not just raw numbers.
        </p>
    </div>

    {{-- Input card --}}
    <div class="max-w-2xl mx-auto fade-up delay-1">
        <div class="bg-dark-800 border border-white/8 rounded-2xl p-6 sm:p-8 shadow-2xl">
            <form action="{{ route('analyze') }}" method="POST" id="analyzeForm">
                @csrf

                <label for="url" class="block text-xs font-mono font-medium text-gray-400 mb-2 uppercase tracking-widest">
                    Website URL
                </label>

                <div class="flex gap-3 flex-col sm:flex-row">
                    <div class="flex-1 relative">
                        <input
                            type="url"
                            id="url"
                            name="url"
                            placeholder="yourwebsite.com"
                            value="{{ old('url') }}"
                            required
                            class="w-full bg-dark-700 border border-white/10 rounded-xl pl-2 pr-4 py-3.5
                                   text-white placeholder-gray-600 font-mono text-sm
                                   focus:outline-none focus:border-brand-500/50 focus:ring-2 focus:ring-brand-500/20
                                   transition-all duration-200"
                        />
                    </div>

                    <button type="submit"
                            id="analyzeBtn"
                            class="bg-brand-500 hover:bg-brand-600 text-white font-semibold px-6 py-3.5 rounded-xl
                                   transition-all duration-200 whitespace-nowrap text-sm
                                   focus:outline-none focus:ring-2 focus:ring-brand-500/50
                                   disabled:opacity-60 disabled:cursor-not-allowed flex items-center gap-2">
                        <span id="btnText">Analyze →</span>
                        <svg id="btnSpinner" class="hidden animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-dashoffset="15"/>
                        </svg>
                    </button>
                </div>

                @error('url')
                    <p class="mt-3 text-red-400 text-xs font-mono">⚠ {{ $message }}</p>
                @enderror

                <p class="mt-4 text-gray-600 text-xs font-mono text-center">
                    Analysis takes ~15–30 seconds · Mobile performance · Powered by Google PageSpeed
                </p>
            </form>
        </div>
    </div>

    {{-- What you'll get --}}
    <div class="mt-20 fade-up delay-2">
        <p class="text-center text-xs font-mono text-gray-600 uppercase tracking-widest mb-8">What you'll get</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach([
                ['icon' => '⚡', 'title' => 'Performance Score', 'desc' => 'Mobile score out of 100 from Google Lighthouse — the same metric Google uses for ranking.'],
                ['icon' => '📊', 'title' => 'Core Web Vitals', 'desc' => 'LCP, CLS, INP/TTI, FCP, TBT — colour-coded Good / Needs Work / Poor with actual values.'],
                ['icon' => '🔧', 'title' => 'Expert Fix Advice', 'desc' => 'Not just "reduce unused CSS." We tell you exactly what\'s causing it and how to fix it.'],
            ] as $feature)
            <div class="bg-dark-800/50 border border-white/5 rounded-xl p-5">
                <div class="text-2xl mb-3">{{ $feature['icon'] }}</div>
                <h3 class="font-semibold text-white text-sm mb-1.5">{{ $feature['title'] }}</h3>
                <p class="text-gray-500 text-xs leading-relaxed">{{ $feature['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>

</div>

<script>
document.getElementById('analyzeForm').addEventListener('submit', function () {
    const btn     = document.getElementById('analyzeBtn');
    const text    = document.getElementById('btnText');
    const spinner = document.getElementById('btnSpinner');
    btn.disabled  = true;
    text.textContent = 'Analyzing…';
    spinner.classList.remove('hidden');
});
</script>

@endsection