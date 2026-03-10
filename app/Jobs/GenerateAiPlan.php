<?php

namespace App\Jobs;

use App\Models\AssignedPlan;
use App\Models\User;
use App\Services\AiPromptsService;
use App\Services\ClaudeAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAiPlan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(
        private readonly User   $user,
        private readonly string $planType,
        private readonly array  $intake,
        private readonly ?array $methodology = null
    ) {}

    public function handle(): void
    {
        try {
            $planJson = $this->generatePlan();

            AssignedPlan::updateOrCreate(
                [
                    'user_id'   => $this->user->id,
                    'plan_type' => $this->planType,
                    'active'    => true,
                ],
                [
                    'content'    => $planJson,
                    'valid_from' => today(),
                ]
            );

            Log::info("AI plan generado: {$this->planType} para user {$this->user->id}"
                . ($this->methodology ? " con metodología {$this->methodology['id']}" : ''));
        } catch (\Exception $e) {
            Log::error("GenerateAiPlan falló para user {$this->user->id}: " . $e->getMessage());
            throw $e;
        }
    }

    private function generatePlan(): string
    {
        // Si hay metodología específica elegida por el coach, usarla
        if ($this->methodology !== null) {
            return ClaudeAiService::generatePlanWithMethodology(
                $this->user->toArray(),
                $this->intake,
                $this->methodology
            );
        }

        // Sin metodología → plan genérico según tipo
        return match ($this->planType) {
            'entrenamiento' => ClaudeAiService::generateTrainingPlan(
                $this->user->toArray(), $this->intake
            ),
            'rise' => ClaudeAiService::generateRisePlan(
                $this->user->toArray(), $this->intake
            ),
            default => throw new \InvalidArgumentException(
                "Tipo de plan no soportado: {$this->planType}"
            ),
        };
    }
}
