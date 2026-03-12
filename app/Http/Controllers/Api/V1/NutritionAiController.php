<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NutritionAiController extends Controller
{
    /**
     * POST /api/v1/nutrition/analyze
     * Analyzes a food description and returns estimated macros.
     *
     * For MVP: uses a simple lookup table.
     * Future: integrate OpenAI Vision API for image-based food analysis.
     */
    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'description' => 'required_without:image|string|max:500',
            'image'       => 'required_without:description|string', // base64 or URL
        ]);

        $description = $request->input('description', '');

        // MVP: keyword-based macro estimation
        $estimate = $this->estimateFromDescription($description);

        Log::info('Nutrition AI analyze', [
            'user_id'     => $request->user()->id,
            'description' => $description,
            'estimate'    => $estimate,
        ]);

        return response()->json([
            'data' => [
                'description'    => $description,
                'estimated'      => true,
                'calories'       => $estimate['calories'],
                'protein_g'      => $estimate['protein'],
                'carbs_g'        => $estimate['carbs'],
                'fat_g'          => $estimate['fat'],
                'confidence'     => $estimate['confidence'],
                'suggestion'     => $estimate['suggestion'],
            ],
        ]);
    }

    /**
     * Simple keyword-based macro estimation.
     * Returns approximate macros based on common food keywords.
     */
    private function estimateFromDescription(string $description): array
    {
        $text = mb_strtolower($description);

        // Common food patterns (per serving estimates)
        $foods = [
            'pollo'     => ['calories' => 250, 'protein' => 35, 'carbs' => 0, 'fat' => 10],
            'arroz'     => ['calories' => 200, 'protein' => 4, 'carbs' => 45, 'fat' => 1],
            'huevo'     => ['calories' => 155, 'protein' => 13, 'carbs' => 1, 'fat' => 11],
            'avena'     => ['calories' => 150, 'protein' => 5, 'carbs' => 27, 'fat' => 3],
            'carne'     => ['calories' => 280, 'protein' => 30, 'carbs' => 0, 'fat' => 18],
            'pescado'   => ['calories' => 200, 'protein' => 28, 'carbs' => 0, 'fat' => 8],
            'ensalada'  => ['calories' => 100, 'protein' => 3, 'carbs' => 10, 'fat' => 5],
            'pasta'     => ['calories' => 350, 'protein' => 12, 'carbs' => 65, 'fat' => 5],
            'pan'       => ['calories' => 180, 'protein' => 6, 'carbs' => 35, 'fat' => 3],
            'proteina'  => ['calories' => 120, 'protein' => 25, 'carbs' => 3, 'fat' => 1],
            'whey'      => ['calories' => 120, 'protein' => 25, 'carbs' => 3, 'fat' => 1],
            'batido'    => ['calories' => 250, 'protein' => 30, 'carbs' => 20, 'fat' => 5],
            'platano'   => ['calories' => 105, 'protein' => 1, 'carbs' => 27, 'fat' => 0],
            'banana'    => ['calories' => 105, 'protein' => 1, 'carbs' => 27, 'fat' => 0],
            'yogurt'    => ['calories' => 100, 'protein' => 10, 'carbs' => 12, 'fat' => 3],
            'aguacate'  => ['calories' => 160, 'protein' => 2, 'carbs' => 9, 'fat' => 15],
            'almendras' => ['calories' => 170, 'protein' => 6, 'carbs' => 6, 'fat' => 15],
            'fruta'     => ['calories' => 80, 'protein' => 1, 'carbs' => 20, 'fat' => 0],
        ];

        $totalCalories = 0;
        $totalProtein = 0;
        $totalCarbs = 0;
        $totalFat = 0;
        $matchCount = 0;

        foreach ($foods as $keyword => $macros) {
            if (str_contains($text, $keyword)) {
                $totalCalories += $macros['calories'];
                $totalProtein += $macros['protein'];
                $totalCarbs += $macros['carbs'];
                $totalFat += $macros['fat'];
                $matchCount++;
            }
        }

        // If no match, estimate a generic meal
        if ($matchCount === 0) {
            return [
                'calories'   => 400,
                'protein'    => 20,
                'carbs'      => 50,
                'fat'        => 15,
                'confidence' => 'low',
                'suggestion' => 'No pudimos identificar alimentos específicos. Estimación genérica de una comida promedio. Intenta describir los ingredientes.',
            ];
        }

        $confidence = $matchCount >= 3 ? 'high' : ($matchCount >= 2 ? 'medium' : 'low');

        return [
            'calories'   => $totalCalories,
            'protein'    => $totalProtein,
            'carbs'      => $totalCarbs,
            'fat'        => $totalFat,
            'confidence' => $confidence,
            'suggestion' => "Detectamos $matchCount alimento(s). Esta es una estimación aproximada por porción estándar.",
        ];
    }
}
