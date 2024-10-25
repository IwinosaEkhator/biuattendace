<?php

namespace App\Http\Controllers;

use App\Models\Logs;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;

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
        return Logs::with('user')->latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate incoming request data
        $fields = $request->validate([
            'log' => 'required|string|max:255'
        ]);

        // Create the log and associate it with the authenticated user
        $log = $request->user()->log()->create($fields);

        // Return response with the created log and associated user
        return response()->json([
            'log' => $log,
            'user' => $log->user,
            'message' => 'Log saved successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Find a DeliveryRequest by its ID, including the associated user
        $logs = Logs::with('user')->find($id);

        // If the request is not found, return a 404 error
        if (!$logs) {
            return response()->json(['message' => 'Log not found'], 404);
        }

        return response()->json($logs);
    }

    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, $id)
    // {
    //     $fields = $request->validate([
    //         'log' => 'required|max:255',
    //     ]);

    //     // Find the existing delivery request by ID
    //     $logs = Logs::find($id);

    //     // Check if the delivery request exists
    //     if (!$logs) {
    //         return response()->json(['message' => 'Delivery request not found'], 404);
    //     }

    //     // Update the status field
    //     $logs->status = $fields['status'];

    //     // Save the updated delivery request
    //     if ($logs->save()) {
    //         return response()->json([
    //             'message' => 'Status updated successfully',
    //             'deliveryRequest' => $logs,
    //         ], 200);
    //     } else {
    //         return response()->json(['message' => 'Failed to update status'], 500);
    //     }
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Logs $logs)
    {
        Gate::authorize('modify', $logs);

        $logs->delete();

        return ['message' => 'The request was deleted'];
    }
}
