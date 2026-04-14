@extends('layouts.app')

@section('title', 'Performance Report — ' . parse_url($report->url, PHP_URL_HOST))

@section('content')

    @php
        $score = $report->performance_score;
        $scoreColor = match (true) {
            $score >= 90 => ['ring' => '#22c55e', 'text' => 'text-green-400', 'bg' => 'bg-green-500/10', 'border' => 'border-green-500/20', 'label' => 'Good'],
            $score >= 50 => ['ring' => '#f59e0b', 'text' => 'text-amber-400', 'bg' => 'bg-amber-500/10', 'border' => 'border-amber-500/20', 'label' => 'Needs Work'],
            default => ['ring' => '#ef4444', 'text' => 'text-red-400', 'bg' => 'bg-red-500/10', 'border' => 'border-red-500/20', 'label' => 'Poor'],
        };
        $circumference = 314; // 2π × 50
        $targetOffset = $circumference - ($score / 100 * $circumference);
        $shareUrl = route('report.show', $report->slug);
    @endphp

    <div class="max-w-5xl mx-auto px-4 pt-10 pb-32 space-y-10">

        {{-- Header row --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 fade-up">
            <div>
                <p class="text-gray-500 text-xs font-mono mb-1">performance report</p>
                <h1 class="text-white font-bold text-xl break-all">
                    {{ parse_url($report->url, PHP_URL_HOST) }}
                </h1>
                <p class="text-gray-600 text-xs font-mono mt-0.5">{{ $report->url }}</p>
            </div>

            {{-- Share button --}}
            <button onclick="copyShare()" id="shareBtn" class="flex items-center gap-2 bg-dark-800 border border-white/8 text-gray-400
                               hover:text-white hover:border-white/20 rounded-lg px-4 py-2.5 text-sm
                               transition-all duration-200 self-start sm:self-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span id="shareBtnText">Copy shareable link</span>
            </button>
        </div>

        {{-- Score + Vitals row --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 fade-up delay-1">

            {{-- Score card --}}
            <div
                class="lg:col-span-1 relative scanlines bg-dark-800 border {{ $scoreColor['border'] }} rounded-2xl p-6 flex flex-col items-center justify-center gap-3">
                <p class="text-gray-500 text-xs font-mono uppercase tracking-widest">Performance</p>

                {{-- SVG ring --}}
                <div class="relative w-32 h-32">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 120 120">
                        <circle cx="60" cy="60" r="50" stroke="rgba(255,255,255,0.05)" stroke-width="8" fill="none" />
                        <circle cx="60" cy="60" r="50" stroke="{{ $scoreColor['ring'] }}" stroke-width="8" fill="none"
                            stroke-linecap="round" class="score-ring" style="--target-offset: {{ $targetOffset }}" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="{{ $scoreColor['text'] }} text-4xl font-bold font-mono">{{ $score }}</span>
                        <span class="text-gray-600 text-xs font-mono">/100</span>
                    </div>
                </div>

                <span
                    class="{{ $scoreColor['bg'] }} {{ $scoreColor['border'] }} {{ $scoreColor['text'] }} border text-xs font-mono font-semibold px-3 py-1 rounded-full">
                    {{ $scoreColor['label'] }}
                </span>
                <p class="text-gray-600 text-xs font-mono text-center">mobile · lighthouse</p>
            </div>

            {{-- Vitals grid --}}
            <div class="lg:col-span-3 grid grid-cols-2 sm:grid-cols-3 gap-3">
                @foreach($vitals as $vital)
                    @php
                        $statusClasses = match ($vital['status']) {
                            'good' => ['bg' => 'bg-green-500/8', 'border' => 'border-green-500/20', 'dot' => 'bg-green-400', 'val' => 'text-green-400'],
                            'needs-improvement' => ['bg' => 'bg-amber-500/8', 'border' => 'border-amber-500/20', 'dot' => 'bg-amber-400', 'val' => 'text-amber-400'],
                            'poor' => ['bg' => 'bg-red-500/8', 'border' => 'border-red-500/20', 'dot' => 'bg-red-400', 'val' => 'text-red-400'],
                            default => ['bg' => 'bg-white/3', 'border' => 'border-white/8', 'dot' => 'bg-gray-500', 'val' => 'text-gray-400'],
                        };
                    @endphp
                    <div class="{{ $statusClasses['bg'] }} {{ $statusClasses['border'] }} border rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-2 h-2 rounded-full {{ $statusClasses['dot'] }} flex-shrink-0"></span>
                            <span class="text-gray-400 text-xs font-mono font-medium">{{ $vital['label'] }}</span>
                        </div>
                        <p class="{{ $statusClasses['val'] }} text-2xl font-bold font-mono">{{ $vital['value'] }}</p>
                    </div>
                @endforeach
            </div>

        </div>

        {{-- Issues + Fixes --}}
        @if(count($issues) > 0)
            <div class="fade-up delay-2">
                <div class="flex items-center gap-3 mb-5">
                    <h2 class="text-white font-bold text-lg">Top Issues & Fixes</h2>
                    <span
                        class="bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-mono px-2.5 py-0.5 rounded-full">
                        {{ count($issues) }} found
                    </span>
                </div>

                <div class="space-y-4">
                    @foreach($issues as $key => $issue)
                        @php
                            $effortColors = [
                                'low' => 'text-green-400 bg-green-500/10 border-green-500/20',
                                'medium' => 'text-amber-400 bg-amber-500/10 border-amber-500/20',
                                'high' => 'text-red-400   bg-red-500/10   border-red-500/20',
                            ];
                            $effort = $issue['fix']['effort'] ?? 'medium';
                            $effortClass = $effortColors[$effort] ?? $effortColors['medium'];
                            $effortLabel = ['low' => 'Quick win', 'medium' => 'Moderate effort', 'high' => 'Deep fix'][$effort];
                            $loopIndex = $loop->index + 1;
                        @endphp

                        <div
                            class="bg-dark-800 border border-white/8 rounded-2xl overflow-hidden
                                                hover:border-white/12 transition-colors duration-200 fade-up delay-{{ $loopIndex }}">

                            {{-- Issue header --}}
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-5 pb-0">
                                <div class="flex items-start gap-3">
                                    <span
                                        class="flex-shrink-0 w-6 h-6 rounded-full bg-red-500/15 border border-red-500/25
                                                             text-red-400 text-xs font-mono font-bold flex items-center justify-center mt-0.5">
                                        {{ $loopIndex }}
                                    </span>
                                    <div>
                                        <h3 class="text-white font-semibold text-sm">{{ $issue['fix']['headline'] }}</h3>
                                        @if($issue['displayValue'])
                                            <span class="text-gray-500 text-xs font-mono">{{ $issue['displayValue'] }}</span>
                                        @endif
                                    </div>
                                </div>
                                <span
                                    class="border {{ $effortClass }} text-xs font-mono font-medium px-2.5 py-1 rounded-full self-start sm:self-center whitespace-nowrap flex-shrink-0">
                                    {{ $effortLabel }}
                                </span>
                            </div>

                            {{-- Fix detail --}}
                            <div class="p-5 pt-4">
                                <p class="text-gray-400 text-sm leading-relaxed mb-4">
                                    {{ $issue['fix']['detail'] }}
                                </p>

                                {{-- Tools --}}
                                @if(!empty($issue['fix']['tools']))
                                    <div class="flex flex-wrap gap-2">
                                        <span class="text-gray-600 text-xs font-mono self-center">fix with:</span>
                                        @foreach($issue['fix']['tools'] as $tool)
                                            <span
                                                class="bg-dark-700 border border-white/8 text-gray-300 text-xs font-mono px-2.5 py-1 rounded-lg">
                                                {{ $tool }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-green-500/8 border border-green-500/20 rounded-2xl p-8 text-center fade-up delay-2">
                <p class="text-green-400 text-2xl mb-2">🎉</p>
                <p class="text-green-300 font-semibold">No major issues found!</p>
                <p class="text-gray-500 text-sm mt-1">This site is already well-optimized.</p>
            </div>
        @endif

        {{-- CTA — your lead generation engine --}}
        <div class="bg-gradient-to-br from-brand-500/10 to-brand-500/5 border border-brand-500/20
                        rounded-2xl p-8 text-center fade-up delay-3">
            <h2 class="text-white font-bold text-2xl mb-3">Want these issues fixed?</h2>
            <p class="text-gray-400 max-w-lg mx-auto mb-6 leading-relaxed">
                I fix Core Web Vitals and performance issues for businesses.
                Most sites see a <strong class="text-brand-400">15–40 point score improvement</strong>
                in 2–3 days.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                {{-- 👇 Replace with your actual Calendly / WhatsApp / contact link --}}
                <a href="https://calendly.com/aarifhsn/30min" target="_blank" class="bg-brand-500 hover:bg-brand-600 text-white font-semibold px-7 py-3.5 rounded-xl
                              transition-all duration-200 text-sm flex items-center gap-2">
                    📅 Book a free 15-min call
                </a>
                <a href="https://wa.me/01750128167?text=Hi!%20I%20just%20ran%20a%20performance%20report%20on%20my%20site%20and%20would%20love%20your%20help%20fixing%20it."
                    target="_blank" class="bg-dark-700 hover:bg-dark-600 border border-white/10 hover:border-white/20
                              text-white font-semibold px-7 py-3.5 rounded-xl transition-all duration-200 text-sm
                              flex items-center gap-2">
                    💬 WhatsApp me
                </a>
            </div>

            <p class="text-gray-600 text-xs font-mono mt-5">
                Share this report: <span class="text-gray-500 cursor-pointer hover:text-gray-400 transition-colors"
                    onclick="copyShare()">{{ $shareUrl }}</span>
            </p>
        </div>

        {{-- Analyzed at --}}
        <p class="text-center text-gray-700 text-xs font-mono fade-up delay-4">
            analyzed {{ $report->created_at->diffForHumans() }} ·
            <a href="{{ route('index') }}" class="hover:text-gray-500 transition-colors">run another ↗</a>
        </p>

    </div>

    <input type="hidden" id="shareUrl" value="{{ $shareUrl }}" />

    <script>
        function copyShare() {
            const url = document.getElementById('shareUrl').value;
            navigator.clipboard.writeText(url).then(() => {
                const btn = document.getElementById('shareBtnText');
                btn.textContent = '✓ Copied!';
                setTimeout(() => btn.textContent = 'Copy shareable link', 2000);
            });
        }
    </script>

@endsection