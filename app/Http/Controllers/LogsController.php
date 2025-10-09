<?php

namespace App\Http\Controllers;

use App\Models\Logs;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class LogsController extends Controller implements HasMiddleware
{

    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show'])
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return \App\Models\Logs::with(['user', 'campus', 'service'])->latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // New preferred shape
        $validated = $request->validate([
            'mat_no'       => 'nullable|string|max:50',
            'service_slug' => 'nullable|string|exists:services,slug',
            'log'          => 'nullable|string|max:255', // legacy support
        ]);

        // Backward compatibility: if only "log" was sent, treat it as mat_no
        $matNo = $validated['mat_no'] ?? $validated['log'] ?? null;
        if (!$matNo) {
            return response()->json(['message' => 'mat_no is required'], 422);
        }

        $service = Service::where('slug', $validated['service_slug'])->firstOrFail();
        if (!$service->active) return response()->json(['message' => 'Service inactive'], 403);

        $campusId = $request->user()->campus_id; // ✅ campus from the logged-in user

        $log = $request->user()->log()->create([
            'log'        => $request->input('log', $matNo),
            'mat_no'     => $matNo,
            'campus_id'  => $campusId,
            'service_id' => $service->id,
        ]);

        return response()->json([
            'log' => $log->load(['user', 'campus', 'service']),
            'message' => 'Log saved successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $logs = \App\Models\Logs::with(['user', 'campus', 'service'])->find($id);
        if (!$logs) return response()->json(['message' => 'Log not found'], 404);
        return response()->json($logs);
    }
    /**
     * Remove all logs from storage.
     */
    public function destroy()
    {
        // Check if the user is authorized to delete all logs
        // if (!Gate::allows('delete-logs')) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        try {
            // Temporarily disable foreign key checks to allow truncating the table
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Logs::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return response()->json(['message' => 'All logs deleted successfully'], 200);
        } catch (\Exception $e) {
            // Log the exception for debugging if needed
            return response()->json(['message' => 'Failed to delete all logs', 'error' => $e->getMessage()], 500);
        }
    }
}
