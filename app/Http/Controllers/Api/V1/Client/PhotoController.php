<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $photos = $request->user()
            ->photos()
            ->orderByDesc('photo_date')
            ->limit($request->integer('limit', 20))
            ->get();

        return response()->json(['data' => $photos]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|max:10240',
            'tipo' => 'required|in:frente,lado,espalda',
            'photo_date' => 'nullable|date',
        ]);

        $file = $request->file('photo');
        $filename = 'progress/' . $request->user()->id . '/' . now()->format('Y-m-d') . '-' . $request->tipo . '.' . $file->extension();
        Storage::disk('public')->put($filename, file_get_contents($file));

        $photo = $request->user()->photos()->create([
            'photo_date' => $request->input('photo_date', today()),
            'tipo' => $request->tipo,
            'filename' => basename($filename),
            'url' => Storage::disk('public')->url($filename),
        ]);

        return response()->json(['data' => $photo], 201);
    }
}
