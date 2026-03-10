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

        // Auto-XP
        app(\App\Services\GamificationService::class)->earnXp($request->user(), 'photo_upload');

        return response()->json(['data' => $photo], 201);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $photo = Photo::where('user_id', $request->user()->id)->findOrFail($id);

        // Delete from storage if it exists
        if ($photo->filename) {
            $path = 'progress/' . $request->user()->id . '/' . $photo->filename;
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $photo->delete();

        return response()->json(['message' => 'Photo deleted successfully']);
    }
}
