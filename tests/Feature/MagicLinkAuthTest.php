<?php

use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('sends a magic link email when requested', function () {
    Mail::fake();

    $user = User::factory()->magicLinkOnly()->create([
        'email' => 'alice@example.com',
        'name' => 'Alice',
    ]);

    $this->post('/login/magic', ['email' => 'alice@example.com'])
        ->assertRedirect();

    Mail::assertSent(MagicLinkMail::class, function ($mail) {
        return $mail->hasTo('alice@example.com');
    });

    expect(MagicLink::where('email', 'alice@example.com')->count())->toBe(1);
});

it('valid magic link logs the buyer in and redirects to dashboard', function () {
    $user = User::factory()->magicLinkOnly()->create([
        'email' => 'bob@example.com',
    ]);

    $magicLink = MagicLink::generateFor('bob@example.com');

    $this->get('/auth/magic/' . $magicLink->token)
        ->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);

    $magicLink->refresh();
    expect($magicLink->used_at)->not->toBeNull();
});

it('expired magic link shows an error', function () {
    User::factory()->magicLinkOnly()->create([
        'email' => 'carol@example.com',
    ]);

    $magicLink = MagicLink::generateFor('carol@example.com');
    $magicLink->update(['expires_at' => now()->subMinute()]);

    $this->get('/auth/magic/' . $magicLink->token)
        ->assertOk()
        ->assertSee('This link has expired');
});

it('used magic link cannot be reused', function () {
    User::factory()->magicLinkOnly()->create([
        'email' => 'dave@example.com',
    ]);

    $magicLink = MagicLink::generateFor('dave@example.com');
    $magicLink->markUsed();

    $this->get('/auth/magic/' . $magicLink->token)
        ->assertOk()
        ->assertSee('This link has expired');
});

it('magic link request for unknown email shows no account message', function () {
    Mail::fake();

    $this->from('/login')
        ->post('/login/magic', ['email' => 'nobody@example.com'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');

    Mail::assertNothingSent();
});

it('rate limits magic link requests to 5 per email per hour', function () {
    Mail::fake();

    $user = User::factory()->magicLinkOnly()->create([
        'email' => 'eve@example.com',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login/magic', ['email' => 'eve@example.com']);
    }

    $this->from('/login')
        ->post('/login/magic', ['email' => 'eve@example.com'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('email');
});

it('buyer with a password can log in with email and password', function () {
    $user = User::factory()->create([
        'email' => 'frank@example.com',
        'password' => bcrypt('secret123'),
    ]);

    $this->post('/login', [
        'email' => 'frank@example.com',
        'password' => 'secret123',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('buyer without a password sees a helpful message when trying password login', function () {
    User::factory()->magicLinkOnly()->create([
        'email' => 'grace@example.com',
    ]);

    $this->from('/login')
        ->post('/login', [
            'email' => 'grace@example.com',
            'password' => 'anything',
        ])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('password');
});

it('login page is accessible', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Log in');
});

it('authenticated user is redirected away from login page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/login')
        ->assertRedirect();
});

it('logout clears the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});
