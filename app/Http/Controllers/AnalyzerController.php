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

    public function index()
    {
        return view('index');
    }

    public function analyze(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'max:500'],
        ]);

        if ($validator->fails()) {
            return redirect()->route('index')->withErrors($validator)->withInput();
        }

        $url = trim($request->input('url'));
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }
        $url = rtrim($url, '/');

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
            'raw_json' => $result['raw'], // stores both mobile + desktop
        ]);

        // Flash parsed data so report page doesn't need to re-parse
        session()->flash('vitals', $result['vitals']);
        session()->flash('desktop_vitals', $result['desktop_vitals']);
        session()->flash('issues', $result['issues']);
        session()->flash('desktop_score', $result['desktop_score']);

        return redirect()->route('report.show', $report->slug);
    }

    public function show(Report $report)
    {
        if (session()->has('vitals')) {
            $vitals = session('vitals');
            $desktopVitals = session('desktop_vitals');
            $issues = session('issues');
            $desktopScore = session('desktop_score');
        } else {
            // Re-parse from stored raw JSON
            $raw = $report->raw_json;
            $mobile = $raw['mobile'] ?? $raw;   // handle legacy single-strategy storage
            $desktop = $raw['desktop'] ?? null;

            $result = $this->pageSpeed->parseFromRaw($mobile, $desktop);
            $vitals = $result['vitals'];
            $desktopVitals = $result['desktop_vitals'];
            $issues = $result['issues'];
            $desktopScore = $result['desktop_score'];
        }

        return view('report', compact('report', 'vitals', 'desktopVitals', 'issues', 'desktopScore'));
    }
}