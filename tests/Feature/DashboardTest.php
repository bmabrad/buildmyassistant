<?php

use App\Models\Assistant;
use App\Models\User;

it('shows the dashboard for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Welcome back');
});

it('redirects unauthenticated users to login', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

it('shows all builds for the logged-in user', function () {
    $user = User::factory()->create();

    Assistant::factory()->create([
        'user_id' => $user->id,
        'assistant_name' => 'Email Drafter',
        'status' => 'completed',
    ]);

    Assistant::factory()->create([
        'user_id' => $user->id,
        'assistant_name' => 'Meeting Prep',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Email Drafter')
        ->assertSee('Meeting Prep');
});

it('builds are ordered by most recent first', function () {
    $user = User::factory()->create();

    $older = Assistant::factory()->create([
        'user_id' => $user->id,
        'assistant_name' => 'Older Build',
        'created_at' => now()->subDays(2),
    ]);

    $newer = Assistant::factory()->create([
        'user_id' => $user->id,
        'assistant_name' => 'Newer Build',
        'created_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSeeInOrder(['Newer Build', 'Older Build']);
});

it('each build card links to the correct chat url', function () {
    $user = User::factory()->create();

    $task = Assistant::factory()->create([
        'user_id' => $user->id,
        'token' => 'test-token-123',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('/launchpad/test-token-123');
});

it('does not show other users builds', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Assistant::factory()->create([
        'user_id' => $other->id,
        'assistant_name' => 'Other Users Build',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Other Users Build');
});

it('shows empty state when no builds', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('You do not have any builds yet');
});

it('does not show account section on dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Set a password')
        ->assertDontSee('Change password');
});

it('shows build another assistant button', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Build another assistant');
});

it('shows Your assistants heading', function () {
    $user = User::factory()->create();

    Assistant::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Your assistants');
});

it('build another assistant links to new-build page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('/dashboard/new-build');
});

it('empty state build button links to new-build page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Build your first assistant')
        ->assertSee('/dashboard/new-build');
});
