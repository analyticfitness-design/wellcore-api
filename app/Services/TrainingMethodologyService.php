<?php

namespace App\Services;

class TrainingMethodologyService
{
    /**
     * Catálogo completo de metodologías de entrenamiento.
     * Basado en evidencia científica y popularidad en LATAM fitness.
     */
    public static function getCatalog(): array
    {
        return [
            // ═══════════════════════════════════════
            // BODYBUILDING & HIPERTROFIA
            // ═══════════════════════════════════════
            [
                'id'          => 'bro_split',
                'name'        => 'Bro Split (Un músculo por día)',
                'category'    => 'bodybuilding',
                'description' => 'Entrenamiento de un grupo muscular por sesión. Alta frecuencia de volumen por músculo con recuperación completa de 7 días.',
                'frequency'   => '5-6 días/semana',
                'best_for'    => ['hipertrofia', 'definición', 'culturismo'],
                'level'       => ['intermedio', 'avanzado'],
                'split'       => ['Pecho', 'Espalda', 'Hombros', 'Piernas', 'Brazos'],
                'key_principles' => ['Alto volumen por músculo', 'Aislamiento + compuestos', 'Recuperación 7 días por grupo'],
                'famous_users' => ['Arnold Schwarzenegger', 'Ronnie Coleman'],
                'pros'        => ['Máximo volumen por músculo', 'Fácil de planificar', 'Muy popular'],
                'cons'        => ['Baja frecuencia por músculo', 'No óptimo según ciencia moderna'],
            ],
            [
                'id'          => 'ppl',
                'name'        => 'Push / Pull / Legs (PPL)',
                'category'    => 'bodybuilding',
                'description' => 'Divide el cuerpo en empuje (pecho, hombros, tríceps), jalón (espalda, bíceps) y piernas. Alta frecuencia con 2x por semana por músculo.',
                'frequency'   => '6 días/semana (2 ciclos PPL)',
                'best_for'    => ['hipertrofia', 'fuerza funcional', 'natty bodybuilding'],
                'level'       => ['intermedio', 'avanzado'],
                'split'       => ['Push', 'Pull', 'Legs', 'Push', 'Pull', 'Legs'],
                'key_principles' => ['2x frecuencia por músculo', 'Progresión doble', 'Balance push/pull'],
                'famous_users' => ['Jeff Nippard', 'Mike Israetel'],
                'pros'        => ['Alta frecuencia', 'Científicamente respaldado', 'Muy flexible'],
                'cons'        => ['Requiere 6 días', 'Fatiga acumulada'],
            ],
            [
                'id'          => 'upper_lower',
                'name'        => 'Upper / Lower Split',
                'category'    => 'bodybuilding',
                'description' => 'Divide el cuerpo en tren superior e inferior. 4 sesiones semanales con 2x frecuencia por músculo. Ideal para balance fuerza-hipertrofia.',
                'frequency'   => '4 días/semana',
                'best_for'    => ['hipertrofia', 'fuerza', 'composición corporal'],
                'level'       => ['principiante', 'intermedio', 'avanzado'],
                'split'       => ['Upper A', 'Lower A', 'Upper B', 'Lower B'],
                'key_principles' => ['Alternancia superior/inferior', 'Sesión A pesada, B hipertrofia', 'Deload cada 4 semanas'],
                'famous_users' => ['Lyle McDonald', 'Eric Helms'],
                'pros'        => ['Muy versátil', '4 días es manejable', 'Excelente para natty'],
                'cons'        => ['Necesita planificación A/B'],
            ],
            [
                'id'          => 'phat',
                'name'        => 'PHAT — Power Hypertrophy Adaptive Training',
                'category'    => 'bodybuilding',
                'description' => 'Protocolo de Layne Norton que combina 2 días de fuerza (bajas reps, alta carga) con 3 días de hipertrofia (moderada carga, alto volumen).',
                'frequency'   => '5 días/semana',
                'best_for'    => ['hipertrofia máxima', 'fuerza simultánea', 'powerbuilding'],
                'level'       => ['avanzado'],
                'split'       => ['Upper Power', 'Lower Power', 'Rest/Cardio', 'Back/Shoulders Hyp', 'Lower Hyp', 'Chest/Arms Hyp'],
                'key_principles' => ['Fuerza + volumen', 'Velocidad en fuerza', 'Periodización ondulada'],
                'famous_users' => ['Layne Norton'],
                'pros'        => ['Lo mejor de fuerza e hipertrofia', 'Muy efectivo en intermedios/avanzados'],
                'cons'        => ['Muy exigente', 'No apto para principiantes'],
            ],
            [
                'id'          => 'gvt',
                'name'        => 'GVT — German Volume Training (10x10)',
                'category'    => 'bodybuilding',
                'description' => 'Protocolo de volumen extremo: 10 series de 10 repeticiones con 60% del 1RM. Diseñado para máximo estímulo de hipertrofia en tiempo limitado.',
                'frequency'   => '4-5 días/semana',
                'best_for'    => ['hipertrofia de choque', 'rompimiento de mesetas', 'aumento de volumen muscular'],
                'level'       => ['intermedio', 'avanzado'],
                'split'       => ['Pecho/Espalda', 'Piernas/Abdomen', 'Rest', 'Hombros/Brazos', 'Rest'],
                'key_principles' => ['10x10 en ejercicio principal', '60% del 1RM', 'Descanso 60-90s'],
                'famous_users' => ['Charles Poliquin', 'Vince Gironda'],
                'pros'        => ['Shock muscular garantizado', 'Sesiones cortas pero intensas'],
                'cons'        => ['Altamente fatigante', 'Solo para ciclos cortos (6 sem)'],
            ],

            // ═══════════════════════════════════════
            // POWERLIFTING & FUERZA
            // ═══════════════════════════════════════
            [
                'id'          => 'stronglifts_5x5',
                'name'        => 'StrongLifts 5x5',
                'category'    => 'fuerza',
                'description' => 'Programa de fuerza lineal con 5 series de 5 reps en los 3 grandes levantamientos. Progresión de 2.5kg por sesión. El programa de fuerza más probado para principiantes.',
                'frequency'   => '3 días/semana (A/B alternados)',
                'best_for'    => ['fuerza básica', 'principiantes', 'composición corporal'],
                'level'       => ['principiante', 'intermedio'],
                'split'       => ['Sentadilla/Press/Remo', 'Sentadilla/Banca/Peso Muerto'],
                'key_principles' => ['Progresión lineal', 'Compuestos únicamente', '+2.5kg cada sesión'],
                'famous_users' => ['Mehdi Hadim'],
                'pros'        => ['Simple y efectivo', 'Rápido progreso inicial', 'Solo 45min/sesión'],
                'cons'        => ['Se estanca rápido en intermedios', 'Poco volumen'],
            ],
            [
                'id'          => 'starting_strength',
                'name'        => 'Starting Strength (Mark Rippetoe)',
                'category'    => 'fuerza',
                'description' => 'El programa de fuerza más influyente de la historia. 3 levantamientos compuestos, progresión lineal, enfoque en técnica perfecta.',
                'frequency'   => '3 días/semana',
                'best_for'    => ['principiantes absolutos', 'fuerza base', 'técnica'],
                'level'       => ['principiante'],
                'split'       => ['Día A: Sentadilla/Banca/Peso Muerto', 'Día B: Sentadilla/Press/Remo'],
                'key_principles' => ['Sentadilla de barra baja', 'Progresión sesión a sesión', 'Comer para crecer'],
                'famous_users' => ['Mark Rippetoe'],
                'pros'        => ['Mejor para empezar desde cero', 'Técnica impecable', 'Resultados rápidos'],
                'cons'        => ['Solo funciona en principiantes', 'Muy básico'],
            ],
            [
                'id'          => 'westside_conjugate',
                'name'        => 'Westside Barbell (Método Conjugado)',
                'category'    => 'fuerza',
                'description' => 'Sistema avanzado que combina días de esfuerzo máximo (90%+ 1RM) con días de esfuerzo dinámico (55-65% 1RM a velocidad máxima). GPP incluido.',
                'frequency'   => '4 días/semana',
                'best_for'    => ['fuerza máxima', 'powerlifting', 'atletas avanzados'],
                'level'       => ['avanzado'],
                'split'       => ['Max Effort Upper', 'Max Effort Lower', 'Dynamic Effort Upper', 'Dynamic Effort Lower'],
                'key_principles' => ['Especificidad de fuerza máxima', 'Velocidad de barra', 'Ejercicios especiales GPP'],
                'famous_users' => ['Louie Simmons', 'Dave Tate', 'Matt Wenning'],
                'pros'        => ['El sistema más efectivo para fuerza máxima', 'Auto-regulación'],
                'cons'        => ['Extremadamente complejo', 'Solo avanzados', 'Requiere equipo'],
            ],
            [
                'id'          => 'nsuns',
                'name'        => 'nSuns 531 LP',
                'category'    => 'fuerza',
                'description' => 'Variante avanzada del 5/3/1 de Jim Wendler. Alto volumen de accesorios, progresión diaria de los 4 grandes levantamientos. Balance óptimo fuerza-volumen.',
                'frequency'   => '4-6 días/semana',
                'best_for'    => ['fuerza + masa', 'powerbuilding', 'atletas con experiencia'],
                'level'       => ['intermedio', 'avanzado'],
                'split'       => ['Sentadilla', 'Banca', 'Peso Muerto', 'Press Hombro + Variante'],
                'key_principles' => ['Progresión diaria en los 4 grandes', 'AMRAP en última serie', 'Accesorios high rep'],
                'famous_users' => ['Jim Wendler', 'nSuns (Reddit)'],
                'pros'        => ['Fuerza y tamaño simultáneos', 'Progresión garantizada'],
                'cons'        => ['Alto volumen puede ser abrumador', 'Necesita descanso suficiente'],
            ],

            // ═══════════════════════════════════════
            // FITNESS & RECOMPOSICIÓN
            // ═══════════════════════════════════════
            [
                'id'          => 'full_body',
                'name'        => 'Full Body (Cuerpo Completo)',
                'category'    => 'fitness',
                'description' => 'Entrena todo el cuerpo en cada sesión. Máxima frecuencia de estímulo muscular. Ideal para principiantes y personas con poco tiempo.',
                'frequency'   => '3 días/semana',
                'best_for'    => ['principiantes', 'mantenimiento', 'recomposición', 'poco tiempo disponible'],
                'level'       => ['principiante', 'intermedio'],
                'split'       => ['Full Body A', 'Full Body B', 'Full Body C'],
                'key_principles' => ['Un compuesto por patrón de movimiento', 'Alta frecuencia = más señal muscular', 'Progresión continua'],
                'famous_users' => ['Pavel Tsatsouline', 'Dan John'],
                'pros'        => ['Muy eficiente', 'Funciona con 3 días', 'Ideal para principiantes'],
                'cons'        => ['Limitado para avanzados', 'Difícil maximizar volumen'],
            ],
            [
                'id'          => 'hiit_resistance',
                'name'        => 'HIIT + Resistencia (Recomposición Corporal)',
                'category'    => 'fitness',
                'description' => 'Combina entrenamiento de fuerza con intervalos de alta intensidad. Diseñado para perder grasa mientras se mantiene o gana músculo.',
                'frequency'   => '4-5 días/semana',
                'best_for'    => ['pérdida de grasa', 'recomposición', 'resistencia cardiovascular'],
                'level'       => ['principiante', 'intermedio'],
                'split'       => ['Resistencia superior', 'HIIT cardio', 'Resistencia inferior', 'HIIT/Funcional', 'Full Body'],
                'key_principles' => ['Deficit calórico moderado', 'Preservar músculo con pesas', 'EPOC con HIIT'],
                'famous_users' => ['Eric Trexler', 'Alan Aragon'],
                'pros'        => ['Quema grasa + preserva músculo', 'Acondicionamiento cardiovascular'],
                'cons'        => ['Alta fatiga', 'Difícil periodizar', 'Puede ser catabólico en exceso'],
            ],
            [
                'id'          => 'functional_training',
                'name'        => 'Entrenamiento Funcional',
                'category'    => 'fitness',
                'description' => 'Basado en patrones de movimiento primal: empuje, jalón, bisagra de cadera, sentadilla, carry, rotación. Desarrolla fuerza aplicable a la vida real.',
                'frequency'   => '3-5 días/semana',
                'best_for'    => ['rendimiento atlético', 'prevención de lesiones', 'longevidad', 'deportistas'],
                'level'       => ['principiante', 'intermedio', 'avanzado'],
                'split'       => ['Patrones de movimiento rotativos'],
                'key_principles' => ['6 patrones primarios', 'Estabilidad antes de movilidad', 'Progresión: posición → carga → velocidad'],
                'famous_users' => ['Gray Cook', 'Dan John', 'Pavel Tsatsouline'],
                'pros'        => ['Prevención de lesiones', 'Transferencia atlética', 'Muy completo'],
                'cons'        => ['No maximiza hipertrofia o fuerza pura'],
            ],

            // ═══════════════════════════════════════
            // METODOLOGÍAS CIENCIA DEL EJERCICIO
            // ═══════════════════════════════════════
            [
                'id'          => 'mrd_approach',
                'name'        => 'MRD — Mínimo Volumen de Mantenimiento → Máximo Volumen Recuperable',
                'category'    => 'ciencia_ejercicio',
                'description' => 'Metodología de Mike Israetel (Renaissance Periodization). Progresa gradualmente desde el volumen mínimo efectivo hasta el máximo recuperable antes del deload.',
                'frequency'   => '4-6 días/semana',
                'best_for'    => ['maximizar hipertrofia científicamente', 'atletas con experiencia en periodización'],
                'level'       => ['intermedio', 'avanzado'],
                'split'       => ['Variable según grupo muscular y MEV/MAV/MRV'],
                'key_principles' => ['MEV → MAV → MRV progression', 'Deload cuando se alcanza MRV', 'Series Efectivas (E-reps)'],
                'famous_users' => ['Mike Israetel', 'Jared Feather'],
                'pros'        => ['Máximo respaldo científico', 'Auto-regulable', 'Minimiza sobre-entrenamiento'],
                'cons'        => ['Complejo de implementar', 'Requiere auto-conocimiento del atleta'],
            ],
            [
                'id'          => 'rir_based',
                'name'        => 'RIR — Reps In Reserve (Periodización por Esfuerzo)',
                'category'    => 'ciencia_ejercicio',
                'description' => 'Regula la intensidad por las repeticiones que quedan en reserva. Semana 1: RIR 3, Semana 2: RIR 2, Semana 3: RIR 1, Semana 4: Deload (RIR 4+).',
                'frequency'   => '4-5 días/semana',
                'best_for'    => ['periodización objetiva', 'auto-regulación', 'evitar sobre-entrenamiento'],
                'level'       => ['intermedio', 'avanzado'],
                'split'       => ['Variable — aplica a cualquier split'],
                'key_principles' => ['Esfuerzo medido por RIR, no % 1RM', 'Progresión de esfuerzo semana a semana', 'Deload obligatorio'],
                'famous_users' => ['Eric Helms', 'Mike Israetel'],
                'pros'        => ['Muy preciso y científico', 'Reduce riesgo de lesión'],
                'cons'        => ['Requiere experiencia para calibrar el RIR'],
            ],
        ];
    }

    public static function getById(string $id): ?array
    {
        return collect(self::getCatalog())
            ->firstWhere('id', $id);
    }

    public static function getByCategory(string $category): array
    {
        return collect(self::getCatalog())
            ->where('category', $category)
            ->values()
            ->toArray();
    }

    public static function getCategories(): array
    {
        return [
            'bodybuilding'      => 'Bodybuilding & Hipertrofia',
            'fuerza'            => 'Powerlifting & Fuerza',
            'fitness'           => 'Fitness & Recomposición',
            'ciencia_ejercicio' => 'Ciencia del Ejercicio',
        ];
    }
}
