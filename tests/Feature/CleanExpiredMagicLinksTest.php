<?php

use App\Models\MagicLink;

it('deletes magic links older than 24 hours', function () {
    MagicLink::create([
        'email' => 'old@example.com',
        'token' => 'old-token-123',
        'expires_at' => now()->subHours(25),
        'created_at' => now()->subHours(25),
    ]);

    MagicLink::create([
        'email' => 'recent@example.com',
        'token' => 'recent-token-456',
        'expires_at' => now()->addMinutes(10),
        'created_at' => now(),
    ]);

    $this->artisan('magic-links:clean')
        ->assertSuccessful()
        ->expectsOutputToContain('Deleted 1 expired magic link(s)');

    expect(MagicLink::count())->toBe(1)
        ->and(MagicLink::first()->email)->toBe('recent@example.com');
});

it('handles no expired links gracefully', function () {
    $this->artisan('magic-links:clean')
        ->assertSuccessful()
        ->expectsOutputToContain('Deleted 0 expired magic link(s)');
});
