<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log as SysLog;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;

class LogsController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            // Keep index/show public only if you truly intend that
            new Middleware('auth:sanctum', except: ['index', 'show']),
        ];
    }

    public function index(Request $request)
    {
        try {
            $q = Log::with(['user', 'campus', 'service'])->latest();

            // Authenticated, non-admin => auto-scope to their campus
            if ($request->user() && ($request->user()->user_type ?? null) !== 'admin') {
                $q->where('campus_id', $request->user()->campus_id);
            }

            // Unauthenticated users: either reject or require campus_id
            if (!$request->user()) {
                if (!$request->filled('campus_id')) {
                    return response()->json([
                        'message' => 'campus_id is required for public listing'
                    ], 422);
                }
                $q->where('campus_id', (int) $request->campus_id)
                    // include user_id so eager-loading "user" has its FK
                    ->select(['id', 'campus_id', 'service_id', 'user_id', 'log', 'created_at']);
            }

            if ($request->filled('campus_id')) {
                $q->where('campus_id', (int) $request->campus_id);
            }
            if ($request->filled('service_id')) {
                $q->where('service_id', (int) $request->service_id);
            }
            if ($request->filled('q')) {
                $term = trim($request->q);
                $q->where(function ($x) use ($term) {
                    $x->where('log', 'like', "%{$term}%")
                        ->orWhere('mat_no', 'like', "%{$term}%");
                });
            }

            $perPage = max(1, min(100, (int) $request->input('per_page', 25)));
            return $q->paginate($perPage);
        } catch (\Throwable $e) {
            SysLog::error('Logs index failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to fetch logs',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $actor    = $request->user(); // auth required by middleware
            $campusId = $actor->campus_id;

            $validated = $request->validate([
                'mat_no' => ['nullable', 'string', 'max:50'],
                'service_slug' => [
                    'required',
                    'string',
                    Rule::exists('services', 'slug')
                        ->where(fn($q) => $q->where('campus_id', $campusId)),
                ],
                'log' => ['nullable', 'string', 'max:255'],
            ]);

            $subjectMatNo = isset($validated['mat_no'])
                ? strtoupper(trim($validated['mat_no']))
                : null;

            $service = Service::where('slug', $validated['service_slug'])
                ->where('campus_id', $campusId)
                ->first();

            if (!$service) {
                return response()->json([
                    'message' => 'Service not found for your campus',
                ], 422);
            }

            if (!$service->active) {
                return response()->json(['message' => 'Service is inactive'], 403);
            }

            $payload = [
                'user_id'    => $actor->id,
                'service_id' => $service->id,
                'campus_id'  => $campusId,
                'log'        => $validated['log'] ?? ($subjectMatNo ?? ''),
                'mat_no'     => $subjectMatNo,
            ];

            $log = $actor->logs()->create($payload);

            return response()->json([
                'log'     => $log->load(['user', 'campus', 'service']),
                'message' => 'Log saved successfully',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            // Let Laravel format the validation errors
            throw $ve;
        } catch (\Throwable $e) {
            SysLog::error('Logs store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Failed to save log',
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $log = Log::with(['user', 'campus', 'service'])->find($id);
            if (!$log) {
                return response()->json(['message' => 'Log not found'], 404);
            }

            if ($request->user() && ($request->user()->user_type ?? null) !== 'admin') {
                if ((int) $log->campus_id !== (int) $request->user()->campus_id) {
                    return response()->json(['message' => 'Forbidden'], 403);
                }
            }

            return response()->json($log);
        } catch (\Throwable $e) {
            SysLog::error('Logs show failed', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to fetch log'], 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            // $this->authorize('deleteAll', Log::class);

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Log::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return response()->json(['message' => 'All logs deleted successfully']);
        } catch (\Throwable $e) {
            SysLog::error('Logs destroy failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to delete logs'], 500);
        }
    }

    public function destroyByDate(Request $request)
    {
        if (!Gate::allows('delete-logs')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = \Carbon\Carbon::parse($request->input('from'))->startOfDay();
        $to   = \Carbon\Carbon::parse($request->input('to'))->endOfDay();

        $q = Log::query();

        // Optional campus scoping for non-super-admins:
        if ($request->user()->user_type !== 'super_admin') {
            $q->where('campus_id', $request->user()->campus_id);
        }

        $deleted = $q->whereBetween('created_at', [$from, $to])->delete();

        return response()->json(['deleted' => $deleted]);
    }
}
