<?php

use App\Models\User;
use Carbon\Carbon;

it('enrolls new client in RISE program', function () {
    $response = $this->postJson('/api/v1/rise/enroll', [
        'name'               => 'Test User',
        'email'              => 'test@example.com',
        'password'           => 'Password123!',
        'experience_level'   => 'intermedio',
        'training_location'  => 'gym',
        'gender'             => 'male',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['client', 'program' => ['id', 'start_date', 'end_date']]);

    $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'plan' => 'rise']);
    $this->assertDatabaseHas('rise_programs', ['status' => 'active']);
});

it('rejects duplicate email enrollment', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $this->postJson('/api/v1/rise/enroll', [
        'email'    => 'existing@example.com',
        'password' => 'Password123!',
        'name'     => 'Duplicate',
    ])->assertUnprocessable();
});

it('calculates correct 30-day program window', function () {
    $response = $this->postJson('/api/v1/rise/enroll', [
        'name'     => 'Test',
        'email'    => 'test2@example.com',
        'password' => 'Pass123!',
    ]);

    $response->assertCreated();

    $endDate = Carbon::parse($response->json('program.end_date'));
    expect((int) today()->diffInDays($endDate))->toBe(30);
});

it('authenticated client can get RISE program status', function () {
    $user = User::factory()->create(['plan' => 'rise']);
    \App\Models\RiseProgram::create([
        'user_id'    => $user->id,
        'start_date' => today()->subDays(5),
        'end_date'   => today()->addDays(25),
        'status'     => 'active',
    ]);

    \Laravel\Sanctum\Sanctum::actingAs($user);

    $this->getJson('/api/v1/rise/status')
        ->assertOk()
        ->assertJsonStructure(['active', 'days_elapsed', 'days_remaining', 'message']);
});

it('saves intake data for RISE program', function () {
    $user = User::factory()->create(['plan' => 'rise']);
    \App\Models\RiseProgram::create([
        'user_id'    => $user->id,
        'start_date' => today(),
        'end_date'   => today()->addDays(30),
        'status'     => 'active',
    ]);

    \Laravel\Sanctum\Sanctum::actingAs($user);

    $this->postJson('/api/v1/rise/intake', [
        'edad'             => 28,
        'waist'            => '80cm',
        'hips'             => '95cm',
        'years'            => 3,
        'place'            => 'gym',
        'days'             => ['lunes', 'miércoles', 'viernes'],
        'goals'            => ['perder grasa', 'ganar músculo'],
        'exercisesToAvoid' => 'ninguno',
    ])->assertOk()->assertJson(['saved' => true]);

    $this->assertDatabaseHas('rise_programs', ['user_id' => $user->id]);
});
