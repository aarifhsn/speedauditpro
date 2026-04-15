<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PageSpeedService
{
    protected string $baseUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /**
     * Run analysis for BOTH mobile AND desktop simultaneously.
     * Issue #1 fix: we no longer only show mobile — both are analyzed and displayed.
     */
    public function analyze(string $url, bool $forceFresh = false): array
    {
        $cacheKey = 'pagespeed_' . md5($url);

        if ($forceFresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($url) {

            $apiKey = config('services.pagespeed.key');

            // Fire both requests in parallel via HTTP pool
            $responses = Http::pool(fn($pool) => [
                $pool->as('mobile')->timeout(60)->get($this->baseUrl, [
                    'url' => $url,
                    'key' => $apiKey,
                    'strategy' => 'mobile',
                    'category' => 'performance',
                ]),
                $pool->as('desktop')->timeout(60)->get($this->baseUrl, [
                    'url' => $url,
                    'key' => $apiKey,
                    'strategy' => 'desktop',
                    'category' => 'performance',
                ]),
            ]);

            if ($responses['mobile']->failed()) {
                throw new \RuntimeException(
                    $responses['mobile']->json('error.message', 'PageSpeed API request failed.')
                );
            }

            $mobileRaw = $responses['mobile']->json();
            $desktopRaw = $responses['desktop']->ok() ? $responses['desktop']->json() : null;

            return $this->parseFromRaw($mobileRaw, $desktopRaw);
        });
    }

    /**
     * Re-parse from stored raw JSON — no API call needed.
     */
    public function parseFromRaw(array $mobileRaw, ?array $desktopRaw = null): array
    {
        return [
            'score' => $this->parseScore($mobileRaw),
            'desktop_score' => $desktopRaw ? $this->parseScore($desktopRaw) : null,
            'vitals' => $this->parseVitals($mobileRaw),
            'desktop_vitals' => $desktopRaw ? $this->parseVitals($desktopRaw) : null,
            'issues' => $this->parseIssues($mobileRaw),
            'raw' => ['mobile' => $mobileRaw, 'desktop' => $desktopRaw],
        ];
    }

    private function parseScore(array $raw): int
    {
        $score = $raw['lighthouseResult']['categories']['performance']['score'] ?? 0;
        return (int) round($score * 100);
    }

    private function parseVitals(array $raw): array
    {
        $audits = $raw['lighthouseResult']['audits'] ?? [];

        return [
            'lcp' => $this->metric($audits, 'largest-contentful-paint', 'Largest Contentful Paint', 'LCP'),
            'cls' => $this->metric($audits, 'cumulative-layout-shift', 'Cumulative Layout Shift', 'CLS'),
            'tti' => $this->metric($audits, 'interactive', 'Time to Interactive', 'TTI'),
            'fcp' => $this->metric($audits, 'first-contentful-paint', 'First Contentful Paint', 'FCP'),
            'tbt' => $this->metric($audits, 'total-blocking-time', 'Total Blocking Time', 'TBT'),
            'si' => $this->metric($audits, 'speed-index', 'Speed Index', 'SI'),
        ];
    }

    private function metric(array $audits, string $key, string $label, string $abbr): array
    {
        $audit = $audits[$key] ?? null;
        if (!$audit) {
            return ['label' => $label, 'abbr' => $abbr, 'fullName' => $label, 'value' => 'N/A', 'score' => null, 'status' => 'na'];
        }
        $score = $audit['score'] ?? null;
        $status = match (true) {
            $score === null => 'na',
            $score >= 0.9 => 'good',
            $score >= 0.5 => 'needs-improvement',
            default => 'poor',
        };
        return [
            'label' => $label,
            'abbr' => $abbr,
            'fullName' => $label,
            'value' => $audit['displayValue'] ?? 'N/A',
            'score' => $score,
            'status' => $status,
        ];
    }

    /**
     * Issue #2 fix:
     * - Added 4 extra audit types (preconnect, preload, third-party, animations)
     * - Lowered pass threshold from 0.90 → 0.95 (catches more borderline issues)
     * - Raised cap from 5 → 8 items shown
     */
    private function parseIssues(array $raw): array
    {
        $audits = $raw['lighthouseResult']['audits'] ?? [];

        $targets = [
            'unused-css-rules' => 'unusedCss',
            'render-blocking-resources' => 'renderBlocking',
            'uses-optimized-images' => 'unoptimizedImages',
            'uses-responsive-images' => 'oversizedImages',
            'unused-javascript' => 'unusedJs',
            'uses-text-compression' => 'textCompression',
            'uses-long-cache-ttl' => 'cachePolicy',
            'dom-size' => 'domSize',
            'uses-rel-preconnect' => 'preconnect',
            'uses-rel-preload' => 'preload',
            'third-party-summary' => 'thirdParty',
            'efficiently-animate-contents' => 'animationPerf',
        ];

        $issues = [];
        foreach ($targets as $auditKey => $issueKey) {
            $audit = $audits[$auditKey] ?? null;
            if (!$audit)
                continue;
            $score = $audit['score'] ?? 1;
            if ($score >= 0.95)
                continue; // Was 0.90 — now catches more real issues

            $issues[$issueKey] = [
                'title' => $audit['title'] ?? $auditKey,
                'displayValue' => $audit['displayValue'] ?? '',
                'score' => $score,
                'fix' => $this->getFixAdvice($issueKey, $audit),
            ];
        }

        uasort($issues, fn($a, $b) => $a['score'] <=> $b['score']);
        return array_slice($issues, 0, 8, true); // Was 5, now 8
    }

    private function getFixAdvice(string $issueKey, array $audit): array
    {
        $dv = $audit['displayValue'] ?? '';

        return match ($issueKey) {
            'unusedCss' => [
                'headline' => 'Unused CSS loaded on every page visit',
                'detail' => "Your stylesheet loads rules never applied on this page — {$dv} of dead CSS. Common with global theme stylesheets or frameworks like Bootstrap. Fix with PurgeCSS or WP Rocket's CSS optimization. Expected gain: 0.3–1.5s on LCP.",
                'tools' => ['PurgeCSS', 'WP Rocket', 'Laravel Mix'],
                'effort' => 'medium',
                'impact' => 'high',
            ],
            'renderBlocking' => [
                'headline' => 'Render-blocking resources delay first paint',
                'detail' => "Scripts or stylesheets pause rendering before any content is visible. {$dv} of savings available. Add `defer` or `async` to non-critical JS. Inline critical CSS and defer the rest.",
                'tools' => ['defer / async attributes', 'Critical CSS', 'WP Rocket'],
                'effort' => 'low',
                'impact' => 'high',
            ],
            'unoptimizedImages' => [
                'headline' => 'Images not compressed or using modern formats',
                'detail' => "Uncompressed or legacy-format images waste {$dv} on every load. Convert to WebP or AVIF — 25–50% smaller at the same quality. Use Spatie Media Library for automatic conversion in Laravel, or process through Squoosh.",
                'tools' => ['Squoosh', 'Spatie Media Library', 'Cloudflare Images'],
                'effort' => 'low',
                'impact' => 'high',
            ],
            'oversizedImages' => [
                'headline' => 'Images delivered larger than display size',
                'detail' => "Full-resolution images are sent when the display requires a fraction of that size — {$dv} wasted per load. Add `srcset` and `sizes` attributes or use an auto-resizing image CDN like Cloudflare or Imgix.",
                'tools' => ['srcset + sizes', 'Imgix', 'Cloudflare Images', 'Bunny.net'],
                'effort' => 'low',
                'impact' => 'medium',
            ],
            'unusedJs' => [
                'headline' => 'JavaScript bundles contain dead code',
                'detail' => "JS bundles include code that never executes on this page — {$dv} loading for nothing. Open Chrome DevTools → Coverage to identify unused files. Remove unneeded plugins and enable code-splitting in Laravel Mix.",
                'tools' => ['Chrome Coverage tab', 'Laravel Mix splitting', 'Bundle Analyzer'],
                'effort' => 'high',
                'impact' => 'high',
            ],
            'textCompression' => [
                'headline' => 'Text assets served without compression',
                'detail' => "HTML, CSS, and JS are sent uncompressed — {$dv} of savings with one config change. Enable `gzip on` in Nginx or `mod_deflate` in Apache. Typically cuts text transfer sizes by 60–80%.",
                'tools' => ['Nginx gzip', 'Apache mod_deflate', 'Cloudflare (auto)'],
                'effort' => 'low',
                'impact' => 'medium',
            ],
            'cachePolicy' => [
                'headline' => 'Static assets have no browser cache headers',
                'detail' => "Without `Cache-Control` headers, browsers re-download your CSS, JS, and images on every visit. Set `max-age=31536000` for versioned assets. Laravel Mix adds file hashes automatically — pair with server-level cache headers.",
                'tools' => ['Laravel Mix versioning', 'Nginx expires', '.htaccess headers'],
                'effort' => 'low',
                'impact' => 'medium',
            ],
            'domSize' => [
                'headline' => 'Excessive DOM size slows rendering',
                'detail' => "A DOM over 1,500 nodes increases memory and slows layout, style recalculation, and JS execution. {$dv}. Common in page-builder sites. Simplify template structure and lazy-load off-screen content.",
                'tools' => ['Lazy loading', 'Intersection Observer', 'DevTools Elements'],
                'effort' => 'high',
                'impact' => 'medium',
            ],
            'preconnect' => [
                'headline' => 'Missing preconnect hints for external origins',
                'detail' => "Resources load from external origins (fonts, analytics, CDN) without pre-warming the connections. Add `<link rel=\"preconnect\">` in `<head>` for each critical origin to cut DNS + TLS handshake latency on first use.",
                'tools' => ['<link rel="preconnect">', '<link rel="dns-prefetch">'],
                'effort' => 'low',
                'impact' => 'low',
            ],
            'preload' => [
                'headline' => 'Key resources not hinted for early loading',
                'detail' => "Critical assets like your hero image or main font are discovered late in the waterfall. Add `<link rel=\"preload\">` hints so the browser fetches them earlier. This directly improves LCP for above-the-fold content.",
                'tools' => ['<link rel="preload">', 'Webpack preload plugin'],
                'effort' => 'low',
                'impact' => 'medium',
            ],
            'thirdParty' => [
                'headline' => 'Third-party scripts add significant load time',
                'detail' => "External scripts (chat widgets, ad trackers, social embeds) are contributing {$dv} to your load time. Audit which are essential. Load non-critical scripts with `async` and consider self-hosting fonts and analytics.",
                'tools' => ['Script audit', 'Partytown', 'Self-hosted Plausible'],
                'effort' => 'medium',
                'impact' => 'high',
            ],
            'animationPerf' => [
                'headline' => 'Animations using non-composited CSS properties',
                'detail' => "Animating `top`, `left`, `width`, or `margin` forces full repaints on every frame. Replace with `transform` and `opacity` — these are composited on the GPU and don't trigger layout recalculation.",
                'tools' => ['CSS transform', 'CSS opacity', 'will-change: transform'],
                'effort' => 'low',
                'impact' => 'low',
            ],
            default => [
                'headline' => $audit['title'] ?? 'Performance issue detected',
                'detail' => $audit['description'] ?? 'Review this audit in Chrome DevTools → Lighthouse.',
                'tools' => ['Chrome DevTools', 'Lighthouse CLI'],
                'effort' => 'medium',
                'impact' => 'medium',
            ],
        };
    }
}