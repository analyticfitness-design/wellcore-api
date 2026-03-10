<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\PersonalRecord;
use Illuminate\Http\Request;

class PersonalRecordController extends Controller
{
    public function index(Request $request)
    {
        // Get best PR per exercise
        $records = PersonalRecord::where('user_id', $request->user()->id)
            ->orderBy('exercise_name')
            ->orderBy('weight', 'desc')
            ->get()
            ->groupBy('exercise_name')
            ->map(fn($group) => $group->first())
            ->values();

        return response()->json(['data' => $records]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'exercise_name' => 'required|string|max:255',
            'weight'        => 'required|numeric|min:0',
            'reps'          => 'required|integer|min:1',
            'achieved_at'   => 'nullable|date',
            'notes'         => 'nullable|string|max:500',
        ]);

        $validated['user_id']      = $request->user()->id;
        $validated['achieved_at']  = $validated['achieved_at'] ?? now()->toDateString();

        $record = PersonalRecord::create($validated);

        return response()->json(['data' => $record], 201);
    }

    public function destroy(Request $request, $id)
    {
        $record = PersonalRecord::where('user_id', $request->user()->id)->findOrFail($id);
        $record->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
