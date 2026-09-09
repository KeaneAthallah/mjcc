<?php

use App\Models\Notification;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('creates notification on SOS creation for matching responders', function () {
    $responder = User::factory()->create(['responder_type' => 'medical']);

    Sanctum::actingAs(User::factory()->viewer()->create());

    $this->postJson('/api/v1/sos', [
        'latitude' => -2.0584032,
        'longitude' => 121.9005688,
        'category' => 'medical',
    ])->assertStatus(201);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $responder->id,
        'type' => 'sos',
    ]);
});

it('lists notifications for authenticated user', function () {
    $user = User::factory()->viewer()->create();

    $this->post('/api/v1/notifications', [
        'user_id' => $user->id,
        'title' => 'Test',
        'body' => 'Body',
        'type' => 'sos',
    ]);

    Notification::create([
        'user_id' => $user->id,
        'title' => 'Test Notification',
        'body' => 'This is a test',
        'type' => 'system',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'Test Notification')
        ->assertJsonPath('data.0.type', 'system');
});

it('marks notification as read', function () {
    $user = User::factory()->viewer()->create();

    $notification = Notification::create([
        'user_id' => $user->id,
        'title' => 'Unread',
        'body' => 'Body',
        'type' => 'sos',
    ]);

    Sanctum::actingAs($user);

    $this->postJson("/api/v1/notifications/{$notification->id}/read")
        ->assertOk();

    $notification->refresh();
    expect($notification->read_at)->not->toBeNull();
});

it('only shows own notifications', function () {
    $user1 = User::factory()->viewer()->create();
    $user2 = User::factory()->viewer()->create();

    Notification::create([
        'user_id' => $user1->id,
        'title' => 'User1 Notification',
        'body' => 'Body',
        'type' => 'system',
    ]);

    Notification::create([
        'user_id' => $user2->id,
        'title' => 'User2 Notification',
        'body' => 'Body',
        'type' => 'system',
    ]);

    Sanctum::actingAs($user1);

    $this->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.title', 'User1 Notification');
});
