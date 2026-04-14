<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\PageSpeedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnalyzerController extends Controller
{
    public function __construct(protected PageSpeedService $pageSpeed)
    {
    }

    /**
     * Show the homepage with URL input form.
     */
    public function index()
    {
        return view('index');
    }

    /**
     * Accept URL submission, run PageSpeed analysis, save & redirect to report.
     */
    public function analyze(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'url', 'max:500'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('index')
                ->withErrors($validator)
                ->withInput();
        }

        $url = rtrim($request->input('url'), '/');

        // Enforce https:// scheme so PSI always gets a valid URL
        if (!str_starts_with($url, 'http')) {
            $url = 'https://' . $url;
        }

        try {
            $result = $this->pageSpeed->analyze($url);
        } catch (\RuntimeException $e) {
            return redirect()->route('index')
                ->with('error', 'Could not analyze this URL: ' . $e->getMessage())
                ->withInput();
        }

        $report = Report::create([
            'url' => $url,
            'performance_score' => $result['score'],
            'raw_json' => $result['raw'],
        ]);

        // Cache parsed data in session so we don't re-parse on redirect
        session()->flash('vitals', $result['vitals']);
        session()->flash('issues', $result['issues']);

        return redirect()->route('report.show', $report->slug);
    }

    /**
     * Display a saved report by slug.
     */
    public function show(Report $report)
    {
        // Use session-flashed data (fresh analysis) OR re-parse from stored raw JSON
        $vitals = session('vitals') ?? $this->reparsedVitals($report);
        $issues = session('issues') ?? $this->reparsedIssues($report);

        return view('report', compact('report', 'vitals', 'issues'));
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private function reparsedVitals(Report $report): array
    {
        if (!$report->raw_json)
            return [];
        $result = $this->pageSpeed->analyze($report->url);
        return $result['vitals'];
    }

    private function reparsedIssues(Report $report): array
    {
        if (!$report->raw_json)
            return [];

        // Re-use stored raw JSON rather than hitting the API again
        // We'll call a lightweight re-parse directly
        $raw = $report->raw_json;
        $audits = $raw['lighthouseResult']['audits'] ?? [];

        // Inline minimal re-parse from stored JSON via the service
        // To avoid a second API hit, expose a parseFromRaw method
        return app(PageSpeedService::class)->parseFromRaw($raw)['issues'];
    }
}