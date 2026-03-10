<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\ExerciseVideo;
use Illuminate\Http\Request;

class ExerciseVideoController extends Controller
{
    public function index(Request $request)
    {
        $query = ExerciseVideo::where('is_active', true);

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($gender = $request->query('gender')) {
            $query->whereIn('gender', [$gender, 'both']);
        }

        $videos = $query->orderBy('category')->orderBy('title')->get();

        return response()->json(['data' => $videos]);
    }
}
