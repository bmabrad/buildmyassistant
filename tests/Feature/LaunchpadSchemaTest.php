<?php

use App\Models\ChatSession;
use App\Models\Delivery;
use App\Models\Generation;
use App\Models\Lead;
use App\Models\PromptVersion;
use App\Models\User;

it('creates a lead with chat answers', function () {
    $lead = Lead::factory()->create([
        'buyer_email' => 'sarah@example.com',
        'tools_used' => 'Claude CoWork',
    ]);

    expect($lead->buyer_email)->toBe('sarah@example.com');
    expect($lead->status)->toBe('partial');
    expect($lead->source)->toBe('launchpad');
});

it('casts lead timestamps when generated', function () {
    $lead = Lead::factory()->generated()->create();

    expect($lead->generated_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    expect($lead->business_type)->toBe('Coach');
    expect($lead->assistant_pick)->toBe('Exec');
});

it('links a lead to a user when the buyer upgrades', function () {
    $user = User::factory()->create();
    $lead = Lead::factory()->create(['user_id' => $user->id]);

    expect($lead->user->id)->toBe($user->id);
});

it('saves a chat session with json answers', function () {
    $lead = Lead::factory()->create();
    $session = ChatSession::factory()->create([
        'lead_id' => $lead->id,
        'current_step' => 4,
        'answers' => ['buyer_name' => 'Sarah', 'tools_used' => 'Claude'],
    ]);

    expect($session->answers)->toBeArray();
    expect($session->answers['buyer_name'])->toBe('Sarah');
    expect($session->current_step)->toBe(4);
    expect($session->lead->id)->toBe($lead->id);
});

it('requires unique session tokens', function () {
    $token = (string) \Illuminate\Support\Str::uuid();
    ChatSession::factory()->create(['session_token' => $token]);

    expect(fn () => ChatSession::factory()->create(['session_token' => $token]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('stores and retrieves an active prompt version per product', function () {
    PromptVersion::factory()->create(['product' => 'launchpad', 'name' => 'v1', 'is_active' => false]);
    $active = PromptVersion::factory()->active()->create(['product' => 'launchpad', 'name' => 'v2']);
    PromptVersion::factory()->active()->create(['product' => 'builder', 'name' => 'v1']);

    expect(PromptVersion::activeFor('launchpad')->id)->toBe($active->id);
    expect(PromptVersion::activeFor('builder')->name)->toBe('v1');
});

it('records a generation linked to lead and prompt version', function () {
    $lead = Lead::factory()->completed()->create();
    $version = PromptVersion::factory()->active()->create();
    $gen = Generation::factory()->success()->create([
        'lead_id' => $lead->id,
        'prompt_version_id' => $version->id,
    ]);

    expect($gen->status)->toBe('success');
    expect($gen->output)->toBeArray();
    expect($gen->output['assistant_pick']['role'])->toBe('Exec');
    expect($gen->lead->id)->toBe($lead->id);
    expect($gen->promptVersion->id)->toBe($version->id);
});

it('tracks a retry chain on generations', function () {
    $lead = Lead::factory()->create();
    $first = Generation::factory()->failed()->create(['lead_id' => $lead->id]);
    $retry = Generation::factory()->success()->create([
        'lead_id' => $lead->id,
        'retry_of_id' => $first->id,
    ]);

    expect($retry->retryOf->id)->toBe($first->id);
});

it('logs a delivery with attachments', function () {
    $lead = Lead::factory()->generated()->create();
    $delivery = Delivery::factory()->sent()->create([
        'lead_id' => $lead->id,
        'to_email' => $lead->buyer_email,
        'attachments' => [
            ['filename' => 'launchpad.pdf', 'path' => 'storage/deliveries/1.pdf'],
        ],
    ]);

    expect($delivery->status)->toBe('sent');
    expect($delivery->attachments)->toHaveCount(1);
    expect($delivery->attachments[0]['filename'])->toBe('launchpad.pdf');
    expect($delivery->sent_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('cascades generations and deliveries when a lead is hard deleted', function () {
    $lead = Lead::factory()->create();
    Generation::factory()->count(2)->create(['lead_id' => $lead->id]);
    Delivery::factory()->count(1)->create(['lead_id' => $lead->id]);

    $lead->forceDelete();

    expect(Generation::where('lead_id', $lead->id)->count())->toBe(0);
    expect(Delivery::where('lead_id', $lead->id)->count())->toBe(0);
});

it('keeps generations visible when a lead is soft deleted', function () {
    $lead = Lead::factory()->create();
    $gen = Generation::factory()->create(['lead_id' => $lead->id]);

    $lead->delete();

    expect(Lead::find($lead->id))->toBeNull();
    expect(Lead::withTrashed()->find($lead->id)?->id)->toBe($lead->id);
    expect(Generation::find($gen->id)?->id)->toBe($gen->id);
});
