<?php

namespace App\Http\Controllers\Api\V1\Rise;

use App\Http\Controllers\Controller;
use App\Models\RiseProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntakeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $program = RiseProgram::where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->latest()
            ->firstOrFail();

        $program->update([
            'intake_data' => $request->all(),
        ]);

        return response()->json(['saved' => true, 'program_id' => $program->id]);
    }
}
