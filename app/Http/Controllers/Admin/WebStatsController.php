<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Analytics\Services\WebStatsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebStatsController extends Controller
{
    public function index(Request $request, WebStatsService $stats): View
    {
        $range = $stats->range($request->query('range'), $request->query('from'), $request->query('to'));
        $granularity = $request->query('group') === 'week' ? 'week' : 'day';

        return view('admin.web-stats', [
            'range' => $range,
            'granularity' => $granularity,
            'report' => $stats->report($range['from'], $range['to'], $granularity),
            'live' => $stats->live(),
        ]);
    }

    public function live(WebStatsService $stats): JsonResponse
    {
        return response()->json($stats->live());
    }

    public function export(Request $request, WebStatsService $stats): StreamedResponse
    {
        $range = $stats->range($request->query('range'), $request->query('from'), $request->query('to'));
        $rows = $stats->exportRows($range['from'], $range['to']);
        $name = 'statistik-web-'.$range['from']->format('Ymd').'-'.$range['to']->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
