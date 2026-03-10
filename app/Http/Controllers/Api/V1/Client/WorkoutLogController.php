<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\WorkoutLog;
use Illuminate\Http\Request;

class WorkoutLogController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $logs = WorkoutLog::where('user_id', $request->user()->id)
            ->where('logged_at', $date)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json(['data' => $logs]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exercise_name' => 'required|string|max:255',
            'sets'          => 'required|array',
            'sets.*.reps'   => 'required|integer|min:0',
            'sets.*.weight' => 'nullable|numeric|min:0',
            'logged_at'     => 'nullable|date',
        ]);

        $validated['user_id']    = $request->user()->id;
        $validated['total_sets'] = count($validated['sets']);
        $validated['logged_at']  = $validated['logged_at'] ?? now()->toDateString();

        $log = WorkoutLog::create($validated);

        return response()->json(['data' => $log], 201);
    }

    public function destroy(Request $request, $id)
    {
        $log = WorkoutLog::where('user_id', $request->user()->id)->findOrFail($id);
        $log->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
