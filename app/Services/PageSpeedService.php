<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PageSpeedService
{
    protected string $baseUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    /**
     * Run a live PageSpeed analysis on a URL.
     */
    public function analyze(string $url): array
    {
        $response = Http::timeout(60)->get($this->baseUrl, [
            'url' => $url,
            'key' => config('services.pagespeed.key'),
            'strategy' => 'mobile',
            'category' => 'performance',
        ]);

        if ($response->failed()) {
            $error = $response->json('error.message', 'PageSpeed API request failed.');
            throw new \RuntimeException($error);
        }

        return $this->parseFromRaw($response->json());
    }

    /**
     * Re-parse stored raw JSON without hitting the API again.
     */
    public function parseFromRaw(array $raw): array
    {
        return [
            'score' => $this->parseScore($raw),
            'vitals' => $this->parseVitals($raw),
            'issues' => $this->parseIssues($raw),
            'raw' => $raw,
        ];
    }

    // ─── Private parsers ────────────────────────────────────────────────────────

    private function parseScore(array $raw): int
    {
        $score = $raw['lighthouseResult']['categories']['performance']['score'] ?? 0;
        return (int) round($score * 100);
    }

    private function parseVitals(array $raw): array
    {
        $audits = $raw['lighthouseResult']['audits'] ?? [];
        return [
            'lcp' => $this->extractMetric($audits, 'largest-contentful-paint', 'LCP'),
            'cls' => $this->extractMetric($audits, 'cumulative-layout-shift', 'CLS'),
            'inp' => $this->extractMetric($audits, 'interactive', 'INP / TTI'),
            'fcp' => $this->extractMetric($audits, 'first-contentful-paint', 'FCP'),
            'tbt' => $this->extractMetric($audits, 'total-blocking-time', 'TBT'),
            'si' => $this->extractMetric($audits, 'speed-index', 'Speed Index'),
        ];
    }

    private function extractMetric(array $audits, string $key, string $label): array
    {
        $audit = $audits[$key] ?? null;
        if (!$audit) {
            return ['label' => $label, 'value' => 'N/A', 'score' => null, 'status' => 'na', 'numericValue' => null];
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
            'value' => $audit['displayValue'] ?? 'N/A',
            'score' => $score,
            'status' => $status,
            'numericValue' => $audit['numericValue'] ?? null,
        ];
    }

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
        ];

        $issues = [];
        foreach ($targets as $auditKey => $issueKey) {
            $audit = $audits[$auditKey] ?? null;
            if (!$audit)
                continue;
            $score = $audit['score'] ?? 1;
            if ($score >= 0.9)
                continue;
            $issues[$issueKey] = [
                'title' => $audit['title'] ?? $auditKey,
                'displayValue' => $audit['displayValue'] ?? '',
                'score' => $score,
                'fix' => $this->getFixAdvice($issueKey, $audit),
            ];
        }

        uasort($issues, fn($a, $b) => $a['score'] <=> $b['score']);
        return array_slice($issues, 0, 5, true);
    }

    private function getFixAdvice(string $issueKey, array $audit): array
    {
        $dv = $audit['displayValue'] ?? '';

        return match ($issueKey) {
            'unusedCss' => [
                'headline' => 'Your theme loads CSS that is never used on this page.',
                'detail' => "Most themes and CSS frameworks ship global stylesheets where 70–90% of rules go unused per page. {$dv} of CSS loading for nothing. Fix with PurgeCSS, WP Rocket's CSS optimization, or per-page splitting via Laravel Mix. This single fix can shave 0.5–2 seconds off render time.",
                'tools' => ['PurgeCSS', 'WP Rocket', 'Laravel Mix', 'Critical CSS'],
                'effort' => 'medium',
            ],
            'renderBlocking' => [
                'headline' => 'Scripts or stylesheets are blocking the page from rendering.',
                'detail' => "Render-blocking resources force the browser to pause before showing anything. {$dv} of savings available. Add `defer` or `async` to non-critical scripts, and load non-essential CSS asynchronously. Every render-blocking resource is guaranteed white-screen time for your users.",
                'tools' => ['async / defer attributes', 'WP Rocket', 'Critical CSS inlining'],
                'effort' => 'low',
            ],
            'unoptimizedImages' => [
                'headline' => 'Images are uncompressed — serving files heavier than necessary.',
                'detail' => "Unoptimized images are the #1 culprit of slow sites. {$dv} can be recovered by converting to WebP/AVIF and running images through a compressor. Try Spatie Media Library with WebP conversion, or Cloudflare Image Resizing. Never serve raw JPEGs — they're 5–10× larger than needed.",
                'tools' => ['Squoosh', 'Cloudflare Images', 'Spatie Media Library', 'ShortPixel'],
                'effort' => 'low',
            ],
            'oversizedImages' => [
                'headline' => 'Full-resolution images sent even when displayed small.',
                'detail' => "Your server sends 2400px images even when the browser renders them at 400px. {$dv} wasted per load. Add responsive `srcset` attributes or use an auto-resizing CDN (Cloudflare, Imgix, Bunny.net). Usually a 30-minute fix with massive impact.",
                'tools' => ['srcset + sizes', 'Imgix', 'Bunny.net', 'Cloudflare Images'],
                'effort' => 'low',
            ],
            'unusedJs' => [
                'headline' => 'JavaScript bundles include dead code that never executes.',
                'detail' => "Page-builder scripts, unused plugins, and jQuery bloat ship code that's never called. {$dv} executing unnecessarily. Audit with Chrome DevTools Coverage tab. Remove unused plugins, code-split with Laravel Mix, and replace heavy jQuery libs with lightweight vanilla JS alternatives.",
                'tools' => ['Chrome Coverage Tab', 'Laravel Mix', 'Webpack Bundle Analyzer'],
                'effort' => 'high',
            ],
            'textCompression' => [
                'headline' => 'HTML, CSS, and JS files are sent uncompressed to the browser.',
                'detail' => "Your server isn't using Gzip or Brotli compression. {$dv} of savings — zero code changes required. Enable `mod_deflate` in Apache `.htaccess` or `gzip on` in Nginx. This 5-minute server fix typically reduces text transfer sizes by 60–80%.",
                'tools' => ['Nginx gzip', 'Apache mod_deflate', 'Cloudflare (auto-enabled)'],
                'effort' => 'low',
            ],
            'cachePolicy' => [
                'headline' => 'Static assets are re-downloaded by the browser on every visit.',
                'detail' => "Without Cache-Control headers, returning visitors download your CSS, JS, and images fresh every time. Set `Cache-Control: max-age=31536000` for versioned assets. Laravel Mix appends file hashes automatically — pair with cache headers in Nginx or `.htaccess`.",
                'tools' => ['Laravel Mix versioning', 'Nginx expires directive', '.htaccess cache headers'],
                'effort' => 'low',
            ],
            'domSize' => [
                'headline' => 'Too many HTML elements — the DOM is bloated.',
                'detail' => "A DOM over 1,500 nodes slows rendering, style recalculation, and all JS operations. {$dv}. Usually caused by page builders (Elementor, Divi) or deeply nested templates. Simplify template structure, lazy-load off-screen sections, and cap nesting at 4–5 levels.",
                'tools' => ['Chrome DevTools Elements panel', 'Lazy loading', 'Intersection Observer'],
                'effort' => 'high',
            ],
            default => [
                'headline' => $audit['title'] ?? 'Performance issue detected.',
                'detail' => $audit['description'] ?? 'Review this audit in Chrome DevTools → Lighthouse tab.',
                'tools' => ['Chrome DevTools', 'Lighthouse CLI'],
                'effort' => 'medium',
            ],
        };
    }
}