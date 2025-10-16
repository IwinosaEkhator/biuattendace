<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as SysLog;
use Laravel\Sanctum\PersonalAccessToken;

class LogsExportController extends Controller
{
    public function exportCsv(Request $req)
    {
        try {
            // --- Quick auth via PAT in query (optional) ---
            if ($token = $req->query('bearer')) {
                if ($pat = PersonalAccessToken::findToken($token)) {
                    Auth::login($pat->tokenable);
                }
            }

            [$start, $end] = $this->computeWindow($req);

            // Build base query
            $q = Log::with(['user', 'campus', 'service'])
                ->whereBetween('created_at', [$start, $end])
                ->orderByDesc('id');

            // Example policy: non-admin restricted by campus
            if (Auth::check() && (Auth::user()->user_type ?? null) !== 'admin') {
                $q->where('campus_id', Auth::user()->campus_id);
            }

            $name = 'logs_' . $start->format('Y-m-d_His') . '__' . $end->format('Y-m-d_His') . '.csv';

            if (ob_get_length()) {
                @ob_end_clean();
            }

            return response()->streamDownload(function () use ($q) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['ID', 'Username', 'Log', 'Service', 'Campus', 'Time']);

                // Stream in chunks to keep memory low
                $q->chunkById(2000, function ($chunk) use ($out) {
                    foreach ($chunk as $log) {
                        fputcsv($out, [
                            $log->id,
                            $log->user->username ?? '',
                            $log->log ?? '',
                            $log->service->name ?? '',
                            $log->campus->name ?? '',
                            optional($log->created_at)?->toDateTimeString() ?? '',
                        ]);
                    }
                    // Force flush per chunk
                    fflush($out);
                });

                fclose($out);
            }, $name, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        } catch (\Throwable $e) {
            SysLog::error('CSV export failed', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // During debugging, return the error so you can see it in the app:
            if ($req->boolean('debug')) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            // Otherwise generic 500
            return response()->json(['error' => 'Export failed'], 500);
        }
    }

    private function computeWindow(Request $req): array
    {
        $mode = $req->query('mode', 'today');
        $from = $req->query('from');
        $to   = $req->query('to');

        $parse = function ($v) {
            try {
                return $v ? Carbon::parse($v) : null;
            } catch (\Throwable) {
                return null;
            }
        };

        switch ($mode) {
            case 'yesterday':
                $start = now()->yesterday()->startOfDay();
                $end   = now()->yesterday()->endOfDay();
                break;

            case 'single':
                $d = $parse($from) ?? now();
                $start = $d->copy()->startOfDay();
                $end   = $d->copy()->endOfDay();
                break;

            case 'range':
                // Safe defaults if missing/bad
                $start = ($parse($from) ?? Carbon::createFromTimestamp(0))->startOfDay();
                $end   = ($parse($to)   ?? now())->endOfDay();
                break;

            default: // today
                $start = now()->startOfDay();
                $end   = now()->endOfDay();
        }
        return [$start, $end];
    }
}
