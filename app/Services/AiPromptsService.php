<?php

namespace App\Services;

class AiPromptsService
{
    public static function getRiseSystemPrompt(): string
    {
        return <<<'PROMPT'
Eres un coach fitness certificado especializado en el RISE Challenge de 30 días de WellCore Fitness.
REGLAS ESTRICTAS:
- Responde ÚNICAMENTE con JSON válido, cero texto fuera del JSON
- 4 semanas de progresión con sobrecarga progresiva real (2-5% por semana)
- Adapta al lugar de entrenamiento: gym usa máquinas y barras; home usa peso corporal y mancuernas; hybrid combina
- Incluye cardio si el objetivo incluye pérdida de grasa
- Tips de nutrición generales sin gramajes exactos (no eres nutricionista)
- Al final incluye un mensaje motivacional y recomienda Asesoría Nutricional WellCore
PROMPT;
    }

    public static function getTrainingSystemPrompt(): string
    {
        return <<<'PROMPT'
Eres un especialista en ciencias del ejercicio con certificación NSCA.
PRINCIPIOS:
- Sobrecarga progresiva 2-5% semanal en cargas
- Volumen 10-20 series totales por grupo muscular por semana
- Periodización 4 semanas: Acumulación → Intensificación → Deload
- RIR (Reps In Reserve): semana 1=3, semana 2=2, semana 3=1, semana 4=4 (deload)
- Tempo 3-0-1 en ejercicios de aislamiento
- Responde SOLO con JSON válido
PROMPT;
    }

    public static function buildRiseEnrichedPrompt(array $client, array $intake): string
    {
        $dias  = implode(', ', $intake['days'] ?? []);
        $goals = implode(', ', $intake['goals'] ?? []);

        return "CLIENTE: {$client['name']}, " . ($intake['edad'] ?? 'N/A') . " años, " . ($client['gender'] ?? 'N/A') . "\n"
            . "MEDIDAS: Cintura " . ($intake['waist'] ?? 'N/A') . ", Caderas " . ($intake['hips'] ?? 'N/A') . "\n"
            . "EXPERIENCIA: " . ($intake['years'] ?? 0) . " años, lugar: " . ($intake['place'] ?? 'gym') . "\n"
            . "DISPONIBILIDAD: {$dias}\n"
            . "OBJETIVO: {$goals}\n"
            . "RESTRICCIONES: " . ($intake['exercisesToAvoid'] ?? 'ninguna') . "\n"
            . "Genera el plan RISE de 30 días completo en JSON.";
    }

    public static function buildTrainingPrompt(array $client, array $intake): string
    {
        $dias = implode(', ', $intake['days'] ?? ['lunes', 'miércoles', 'viernes']);

        return "ATLETA: {$client['name']}, nivel: " . ($intake['level'] ?? 'intermedio') . "\n"
            . "LUGAR: " . ($intake['place'] ?? 'gym') . "\n"
            . "DÍAS: {$dias}\n"
            . "OBJETIVO: " . ($intake['goal'] ?? 'hipertrofia') . "\n"
            . "Genera plan de entrenamiento periodizado 4 semanas en JSON.";
    }

    public static function getMethodologySystemPrompt(array $methodology): string
    {
        $principlesText = implode("\n", array_map(
            fn ($p) => "- {$p}",
            $methodology['key_principles'] ?? []
        ));
        $splitText = implode(' → ', $methodology['split'] ?? []);

        return <<<PROMPT
Eres un coach fitness certificado especializado en la metodología "{$methodology['name']}".

METODOLOGÍA SELECCIONADA: {$methodology['name']}
CATEGORÍA: {$methodology['category']}
DESCRIPCIÓN: {$methodology['description']}
FRECUENCIA TÍPICA: {$methodology['frequency']}

PRINCIPIOS CLAVE DE ESTA METODOLOGÍA:
{$principlesText}

REGLAS ABSOLUTAS:
- Responde ÚNICAMENTE con JSON válido, cero texto fuera del JSON
- El plan DEBE seguir fielmente los principios de {$methodology['name']}
- 4 semanas de progresión con sobrecarga progresiva real
- Adapta exactamente al split: {$splitText}
- Incluye RPE/RIR cuando aplique a la metodología
PROMPT;
    }

    public static function buildMethodologyPrompt(array $client, array $intake, array $methodology): string
    {
        $dias  = implode(', ', $intake['days'] ?? ['lunes', 'miércoles', 'viernes']);
        $goals = implode(', ', $intake['goals'] ?? ['hipertrofia']);

        return "METODOLOGÍA: {$methodology['name']}\n"
            . "ATLETA: {$client['name']}, nivel: " . ($intake['level'] ?? 'intermedio') . "\n"
            . "LUGAR: " . ($intake['place'] ?? 'gym') . "\n"
            . "DISPONIBILIDAD: {$dias}\n"
            . "OBJETIVO: {$goals}\n"
            . "RESTRICCIONES: " . ($intake['exercisesToAvoid'] ?? 'ninguna') . "\n\n"
            . "Genera un plan de entrenamiento completo de 4 semanas siguiendo estrictamente la "
            . "metodología {$methodology['name']} en formato JSON. "
            . "Incluye: semana_1, semana_2, semana_3, semana_4 con días, ejercicios, series, "
            . "reps, intensidad (% 1RM o RIR según la metodología), y notas de progresión.";
    }
}
