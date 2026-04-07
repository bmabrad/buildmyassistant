<?php

use App\Models\Assistant;
use App\Models\User;

it('allows an admin to impersonate a user and see their dashboard', function () {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create(['name' => 'Karen Whitfield']);

    Assistant::factory()->create([
        'user_id' => $buyer->id,
        'assistant_name' => 'Client Intake Navigator',
        'status' => 'completed',
    ]);

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$buyer->id}")
        ->assertRedirect('/dashboard');

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Welcome back, Karen Whitfield')
        ->assertSee('Client Intake Navigator');
});

it('shows the impersonation banner on all pages while impersonating', function () {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create(['name' => 'David Nguyen', 'email' => 'david@example.com']);

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$buyer->id}");

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('You are viewing as David Nguyen (david@example.com)')
        ->assertSee('Stop impersonating');

    $this->get('/')
        ->assertOk()
        ->assertSee('You are viewing as David Nguyen (david@example.com)');
});

it('returns the admin to the admin panel when they stop impersonating', function () {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$buyer->id}");

    $this->post('/admin/stop-impersonating')
        ->assertRedirect('/admin');

    // Should be logged in as admin again
    $this->assertAuthenticatedAs($admin);

    // Banner should be gone
    $this->get('/dashboard')
        ->assertDontSee('You are viewing as');
});

it('prevents non-admin users from impersonating', function () {
    $buyer = User::factory()->create();
    $otherBuyer = User::factory()->create();

    $this->actingAs($buyer)
        ->post("/admin/impersonate/{$otherBuyer->id}")
        ->assertForbidden();
});

it('prevents guests from accessing the impersonate route', function () {
    $buyer = User::factory()->create();

    $this->post("/admin/impersonate/{$buyer->id}")
        ->assertRedirect('/login');
});

it('prevents admins from impersonating other admins', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$otherAdmin->id}")
        ->assertForbidden();
});

it('blocks password changes while impersonating', function () {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$buyer->id}");

    $this->post('/dashboard/password', [
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertForbidden();
});

it('blocks billing changes while impersonating', function () {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$buyer->id}");

    $this->post('/dashboard/billing')
        ->assertForbidden();
});

it('hides password and billing controls in dashboard while impersonating', function () {
    $admin = User::factory()->admin()->create();
    $buyer = User::factory()->create();

    $this->actingAs($admin)
        ->post("/admin/impersonate/{$buyer->id}");

    $this->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Set a password')
        ->assertDontSee('Change password');
});
