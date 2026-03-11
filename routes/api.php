<?php

use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Client\CheckinController;
use App\Http\Controllers\Api\V1\Client\MetricController;
use App\Http\Controllers\Api\V1\Client\PhotoController;
use App\Http\Controllers\Api\V1\Client\ProfileController;
use App\Http\Controllers\Api\V1\Client\VideoCheckinController;
use App\Http\Controllers\Api\V1\Client\MyPlanController;
use App\Http\Controllers\Api\V1\Client\WorkoutLogController;
use App\Http\Controllers\Api\V1\Client\BodyMeasurementController;
use App\Http\Controllers\Api\V1\Client\ExerciseVideoController;
use App\Http\Controllers\Api\V1\Client\PersonalRecordController;
use App\Http\Controllers\Api\V1\Admin\ClientsController as AdminClientsController;
use App\Http\Controllers\Api\V1\Admin\KpiController;
use App\Http\Controllers\Api\V1\Coach\ClientsController as CoachClientsController;
use App\Http\Controllers\Api\V1\Coach\BroadcastController;
use App\Http\Controllers\Api\V1\Coach\NotesController as CoachNotesController;
use App\Http\Controllers\Api\V1\Coach\AnalyticsController as CoachAnalyticsController;
use App\Http\Controllers\Api\V1\Coach\CheckinReplyController as CoachCheckinReplyController;
use App\Http\Controllers\Api\V1\Coach\PodsController;
use App\Http\Controllers\Api\V1\Coach\ClientPlanController;
use App\Http\Controllers\Api\V1\Community\PostController as CommunityPostController;
use App\Http\Controllers\Api\V1\Challenges\ChallengeController;
use App\Http\Controllers\Api\V1\Payments\WompiController;
use App\Http\Controllers\Api\V1\Rise\EnrollController;
use App\Http\Controllers\Api\V1\Rise\StatusController;
use App\Http\Controllers\Api\V1\Rise\IntakeController;
use App\Http\Controllers\Api\V1\Training\MethodologyController;
use App\Http\Controllers\Api\V1\NutritionController;
use App\Http\Controllers\Api\V1\WellnessController;
use App\Http\Controllers\Api\V1\Client\BiometricController;
use App\Http\Controllers\Api\V1\GamificationController;
use App\Http\Controllers\Api\V1\ReferralController;
use App\Http\Controllers\Api\V1\Coach\AppointmentController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\AiCoachController;
use App\Http\Controllers\Api\V1\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WellCore API Routes — v1
|--------------------------------------------------------------------------
| All routes are prefixed with /api/v1 and protected by auth:sanctum
| unless marked as public.
*/

Route::prefix('v1')->group(function () {
    // Public
    Route::get('/health', fn () => response()->json(['status' => 'ok', 'version' => 'v1']));
    Route::post('/auth/login', LoginController::class)->middleware('throttle:login');

    // RISE enroll — público (nuevo cliente se registra desde cero)
    Route::post('/rise/enroll', EnrollController::class);

    // Wompi webhook — público (firmado por Wompi, sin auth:sanctum)
    Route::post('/payments/wompi/webhook', [WompiController::class, 'webhook']);

    // Protected
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', MeController::class);
        Route::post('/auth/logout', LogoutController::class);

        // Admin routes
        Route::middleware('role:admin,superadmin')->prefix('admin')->group(function () {
            Route::get('/clients', [AdminClientsController::class, 'index']);
            Route::post('/impersonate/{client}', [AdminClientsController::class, 'impersonate']);
            Route::get('/kpis', [KpiController::class, 'index']);
        });

        // Coach routes
        Route::middleware('role:coach,coach_external,admin,superadmin')->prefix('coach')->group(function () {
            Route::get('/clients', [CoachClientsController::class, 'index']);
            Route::get('/clients/{client}', [CoachClientsController::class, 'show']);
            Route::get('/notes/{client}', [CoachNotesController::class, 'index']);
            Route::post('/notes/{client}', [CoachNotesController::class, 'store']);
            Route::post('/checkins/{checkin}/reply', [CoachCheckinReplyController::class, 'reply']);
            Route::get('/analytics', [CoachAnalyticsController::class, 'dashboard']);
            Route::get('/pods', [PodsController::class, 'index']);
            Route::post('/pods', [PodsController::class, 'store']);
            Route::prefix('broadcasts')->group(function () {
                Route::get('/', [BroadcastController::class, 'index']);
                Route::post('/', [BroadcastController::class, 'store']);
            });
        });

        // RISE routes — autenticadas
        Route::prefix('rise')->group(function () {
            Route::get('/status', StatusController::class);
            Route::post('/intake', IntakeController::class);
        });

        // Training methodologies — cualquier usuario autenticado puede ver el catálogo
        Route::prefix('training')->group(function () {
            Route::get('/methodologies', [MethodologyController::class, 'index']);
            Route::get('/methodologies/{id}', [MethodologyController::class, 'show']);
            // Solo coaches y admins pueden generar planes
            Route::middleware('role:coach,coach_external,admin,superadmin')->group(function () {
                Route::post('/generate', [MethodologyController::class, 'generateForClient']);
                Route::get('/clients/{clientId}/plan', [MethodologyController::class, 'getClientPlan']);
            });
        });

        // Client + staff routes
        Route::middleware('role:client,coach,admin,superadmin')->group(function () {
            Route::get('/profile', [ProfileController::class, 'show']);
            Route::put('/profile', [ProfileController::class, 'update']);
            Route::get('/metrics', [MetricController::class, 'index']);
            Route::post('/metrics', [MetricController::class, 'store']);
            Route::get('/checkins', [CheckinController::class, 'index']);
            Route::post('/checkins', [CheckinController::class, 'store']);
            Route::get('/photos', [PhotoController::class, 'index']);
            Route::post('/photos', [PhotoController::class, 'store']);
            Route::delete('/photos/{id}', [PhotoController::class, 'destroy']);

            // Video checkins con límites por plan
            Route::prefix('video-checkins')->group(function () {
                Route::get('/', [VideoCheckinController::class, 'index']);
                Route::post('/', [VideoCheckinController::class, 'store']);
                Route::get('/status', [VideoCheckinController::class, 'status']);
                Route::get('/{id}', [VideoCheckinController::class, 'show']);
                Route::delete('/{id}', [VideoCheckinController::class, 'destroy']);
            });

            // Community
            Route::prefix('community')->group(function () {
                Route::get('/posts', [CommunityPostController::class, 'index']);
                Route::post('/posts', [CommunityPostController::class, 'store']);
                Route::post('/posts/{post}/react', [CommunityPostController::class, 'react']);
                Route::delete('/posts/{post}/react', [CommunityPostController::class, 'unreact']);
            });

            // Challenges
            Route::prefix('challenges')->group(function () {
                Route::get('/', [ChallengeController::class, 'index']);
                Route::post('/{challenge}/join', [ChallengeController::class, 'join']);
                Route::get('/{challenge}/leaderboard', [ChallengeController::class, 'leaderboard']);
            });

            // Nutrition tracking — daily upsert per user
            Route::post('nutrition', [NutritionController::class, 'store']);
            Route::get('nutrition/today', [NutritionController::class, 'today']);

            // Mental wellness tracking — daily upsert per user
            Route::post('wellness', [WellnessController::class, 'store']);
            Route::get('wellness/today', [WellnessController::class, 'today']);

            // Biometric sync (Apple Health / Google Fit / manual)
            Route::post('metrics/biometric', [BiometricController::class, 'store']);
            Route::get('metrics/biometric/today', [BiometricController::class, 'today']);

            // Tickets de soporte
            Route::prefix('tickets')->group(function () {
                Route::get('/', [TicketController::class, 'index']);
                Route::post('/', [TicketController::class, 'store']);
                Route::get('/{ticket}', [TicketController::class, 'show']);
                Route::post('/{ticket}/messages', [TicketController::class, 'addMessage']);
            });
        });

        // Gamification — leaderboard por grupo de coach
        Route::get('gamification/leaderboard', [GamificationController::class, 'leaderboard']);
        Route::get('gamification/my-stats', [GamificationController::class, 'myStats']);
        Route::get('gamification/achievements', [GamificationController::class, 'achievements']);
        Route::post('gamification/earn-xp', [GamificationController::class, 'earnXp']);

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/read-all', [NotificationController::class, 'markAllRead']);
            Route::post('/{id}/read', [NotificationController::class, 'markRead']);
        });

        // Referral — cualquier usuario autenticado
        Route::get('referral/my-link', [ReferralController::class, 'myLink']);
        Route::get('referral/my-referrals', [ReferralController::class, 'myReferrals']);

        // AI Coach — cualquier usuario autenticado
        Route::prefix('ai')->group(function () {
            Route::get('conversations', [AiCoachController::class, 'index']);
            Route::get('conversations/{id}', [AiCoachController::class, 'show']);
            Route::post('send', [AiCoachController::class, 'send']);
            Route::delete('conversations/{id}', [AiCoachController::class, 'destroy']);
        });

        // Appointments — cliente ve las suyas, coach ve las suyas, admin ve todas
        Route::middleware('role:client,coach,admin,superadmin')->group(function () {
            Route::get('appointments', [AppointmentController::class, 'index']);
            Route::put('appointments/{appointment}', [AppointmentController::class, 'update']);
            Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy']);
        });
        Route::middleware('role:coach,admin,superadmin')->group(function () {
            Route::post('appointments', [AppointmentController::class, 'store']);
        });

        // === BLOQUE 3: Plans, Workout Logs, Body Measurements ===
        Route::get('/my-plan', [MyPlanController::class, 'show']);

        Route::prefix('workout-logs')->group(function () {
            Route::get('/', [WorkoutLogController::class, 'index']);
            Route::post('/', [WorkoutLogController::class, 'store']);
            Route::delete('/{id}', [WorkoutLogController::class, 'destroy']);
        });

        Route::prefix('body-measurements')->group(function () {
            Route::get('/', [BodyMeasurementController::class, 'index']);
            Route::post('/', [BodyMeasurementController::class, 'store']);
        });

        // === BLOQUE 4: Academy, Personal Records ===
        Route::get('/exercise-videos', [ExerciseVideoController::class, 'index']);

        Route::prefix('personal-records')->group(function () {
            Route::get('/', [PersonalRecordController::class, 'index']);
            Route::post('/', [PersonalRecordController::class, 'store']);
            Route::delete('/{id}', [PersonalRecordController::class, 'destroy']);
        });

        // Coach: client plans
        Route::middleware('role:coach,coach_external,admin,superadmin')->group(function () {
            Route::get('/clients/{clientId}/plan', [ClientPlanController::class, 'show']);
            Route::put('/clients/{clientId}/plan', [ClientPlanController::class, 'upsert']);
        });
    });
});

// TEMP: one-time password reset endpoint — remove after use
Route::post('/maintenance/reset-password', function (\Illuminate\Http\Request $r) {
    if ($r->input('secret') !== 'WC_MAINT_2026_RESET') {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    $user = \App\Models\User::where('email', $r->input('email'))->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    $user->password = bcrypt($r->input('password'));
    $user->save();
    return response()->json(['ok' => true, 'email' => $user->email]);
});
