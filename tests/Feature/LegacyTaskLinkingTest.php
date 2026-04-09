<?php

use App\Models\Assistant;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('links legacy tasks on password login', function () {
    $user = User::factory()->create([
        'email' => 'alice@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $task = Assistant::factory()->create([
        'email' => 'alice@example.com',
        'user_id' => null,
    ]);

    $this->post('/login', [
        'email' => 'alice@example.com',
        'password' => 'secret123',
    ]);

    $task->refresh();
    expect($task->user_id)->toBe($user->id);
});

it('links legacy tasks on magic link login', function () {
    $user = User::factory()->magicLinkOnly()->create([
        'email' => 'bob@example.com',
    ]);

    $task = Assistant::factory()->create([
        'email' => 'bob@example.com',
        'user_id' => null,
    ]);

    $magicLink = MagicLink::generateFor('bob@example.com');

    $this->get('/auth/magic/' . $magicLink->token);

    $task->refresh();
    expect($task->user_id)->toBe($user->id);
});

it('does not overwrite existing user_id on tasks', function () {
    $user = User::factory()->create([
        'email' => 'carol@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $otherUser = User::factory()->create();

    $task = Assistant::factory()->create([
        'email' => 'carol@example.com',
        'user_id' => $otherUser->id,
    ]);

    $this->post('/login', [
        'email' => 'carol@example.com',
        'password' => 'secret123',
    ]);

    $task->refresh();
    expect($task->user_id)->toBe($otherUser->id);
});

it('links multiple legacy tasks at once', function () {
    $user = User::factory()->create([
        'email' => 'dave@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $task1 = Assistant::factory()->create([
        'email' => 'dave@example.com',
        'user_id' => null,
    ]);

    $task2 = Assistant::factory()->create([
        'email' => 'dave@example.com',
        'user_id' => null,
    ]);

    $this->post('/login', [
        'email' => 'dave@example.com',
        'password' => 'secret123',
    ]);

    $task1->refresh();
    $task2->refresh();
    expect($task1->user_id)->toBe($user->id)
        ->and($task2->user_id)->toBe($user->id);
});

it('legacy tasks remain accessible via token url', function () {
    $task = Assistant::factory()->create([
        'user_id' => null,
        'status' => 'active',
    ]);

    $this->get('/launchpad/' . $task->token)
        ->assertOk();
});
