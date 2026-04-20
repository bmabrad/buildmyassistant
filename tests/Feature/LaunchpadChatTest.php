<?php

use App\Jobs\GenerateLaunchpadOutputJob;
use App\Livewire\LaunchpadChat;
use App\Models\ChatSession;
use App\Models\Lead;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

beforeEach(function () {
    Bus::fake([GenerateLaunchpadOutputJob::class]);
});

it('loads the launchpad page', function () {
    $this->get('/launchpad')->assertStatus(200)->assertSee('What would you do with an extra ten hours a week');
});

it('creates a chat session on mount', function () {
    expect(ChatSession::count())->toBe(0);

    Livewire::test(LaunchpadChat::class);

    expect(ChatSession::count())->toBe(1);
    expect(ChatSession::first()->current_step)->toBe(0);
});

it('advances through the full flow and creates a completed lead', function () {
    Livewire::test(LaunchpadChat::class)
        ->call('start')
        ->set('buyerName', 'Sarah')->call('submitStep')
        ->set('businessDescription', 'Leadership coaching for mid-career executives in finance and tech')->call('submitStep')
        ->call('chooseTool', 'Claude CoWork')
        ->set('businessRole', 'Executive coach')->call('submitStep')
        ->set('whoTheyServe', 'Directors and VPs navigating a step up')->call('submitStep')
        ->set('timeDrainChecks', ['Client emails and messages', 'Scheduling and rescheduling'])
        ->set('timeDrainOther', 'Rescheduling admin')->call('submitStep')
        ->set('tediousWork', 'Writing session notes and chasing invoices')->call('submitStep')
        ->set('oneHandoffToday', 'Drafting the session follow-up emails')->call('submitStep')
        ->call('chooseAiUsage', 'Regular')
        ->set('buyerEmail', 'sarah@example.com')->call('submitStep')
        ->assertSet('step', 11)
        ->assertSee('Your starter prompt is being generated');

    $lead = Lead::first();
    expect($lead)->not->toBeNull();
    expect($lead->buyer_name)->toBe('Sarah');
    expect($lead->buyer_email)->toBe('sarah@example.com');
    expect($lead->tools_used)->toBe('Claude CoWork');
    expect($lead->business_role)->toBe('Executive coach');
    expect($lead->ai_usage_level)->toBe('Regular');
    expect($lead->status)->toBe('completed');
    expect($lead->time_drains)->toContain('Client emails and messages');
    expect($lead->time_drains)->toContain('Rescheduling admin');
});

it('skips Q9 and defaults ai_usage_level to Light when tools_used is None yet', function () {
    Livewire::test(LaunchpadChat::class)
        ->call('start')
        ->set('buyerName', 'Ben')->call('submitStep')
        ->set('businessDescription', 'Brand design studio for small consumer goods brands')->call('submitStep')
        ->call('chooseTool', 'None yet')
        ->set('businessRole', 'Brand designer')->call('submitStep')
        ->set('whoTheyServe', 'Indie consumer goods founders')->call('submitStep')
        ->set('timeDrainChecks', ['Proposals and quotes'])->call('submitStep')
        ->set('tediousWork', 'Writing proposals from scratch every time')->call('submitStep')
        ->set('oneHandoffToday', 'Drafting initial proposal sections')->call('submitStep')
        ->assertSet('step', 10)
        ->assertSet('aiUsageLevel', 'Light');
});

it('rejects empty buyer name with the right message', function () {
    Livewire::test(LaunchpadChat::class)
        ->call('start')
        ->set('buyerName', '   ')
        ->call('submitStep')
        ->assertHasErrors(['buyerName'])
        ->assertSee('Just a first name is fine');
});

it('rejects short business description', function () {
    Livewire::test(LaunchpadChat::class)
        ->call('start')
        ->set('buyerName', 'Sarah')->call('submitStep')
        ->set('businessDescription', 'Coaching.')
        ->call('submitStep')
        ->assertHasErrors(['businessDescription'])
        ->assertSee('Give me a little more');
});

it('rejects empty time drains', function () {
    $chat = Livewire::test(LaunchpadChat::class)
        ->call('start')
        ->set('buyerName', 'Sarah')->call('submitStep')
        ->set('businessDescription', 'Leadership coaching for mid-career executives')->call('submitStep')
        ->call('chooseTool', 'Claude CoWork')
        ->set('businessRole', 'Executive coach')->call('submitStep')
        ->set('whoTheyServe', 'Directors and VPs navigating step ups')->call('submitStep')
        ->set('timeDrainChecks', [])
        ->set('timeDrainOther', '')
        ->call('submitStep');

    $chat->assertHasErrors(['timeDrainChecks']);
    expect($chat->get('step'))->toBe(6);
});

it('rejects invalid email on the final step', function () {
    Livewire::test(LaunchpadChat::class)
        ->call('start')
        ->set('buyerName', 'Sarah')->call('submitStep')
        ->set('businessDescription', 'Leadership coaching for mid-career executives')->call('submitStep')
        ->call('chooseTool', 'Claude CoWork')
        ->set('businessRole', 'Executive coach')->call('submitStep')
        ->set('whoTheyServe', 'Directors and VPs navigating step ups')->call('submitStep')
        ->set('timeDrainChecks', ['Client emails and messages'])->call('submitStep')
        ->set('tediousWork', 'Writing session notes and chasing invoices')->call('submitStep')
        ->set('oneHandoffToday', 'Drafting the session follow-up emails')->call('submitStep')
        ->call('chooseAiUsage', 'Regular')
        ->set('buyerEmail', 'not-an-email')
        ->call('submitStep')
        ->assertHasErrors(['buyerEmail'])
        ->assertSee('valid email');
});

it('saves partial progress after each answered question', function () {
    Livewire::test(LaunchpadChat::class)
        ->call('start')
        ->set('buyerName', 'Sarah')
        ->call('submitStep');

    $lead = Lead::first();
    expect($lead)->not->toBeNull();
    expect($lead->status)->toBe('partial');
    expect($lead->buyer_name)->toBe('Sarah');
    expect($lead->buyer_email)->toBeNull();
});

it('flags a duplicate email submission within 24 hours', function () {
    Lead::factory()->create([
        'buyer_email' => 'sarah@example.com',
        'status' => 'completed',
        'completed_at' => now()->subHour(),
    ]);

    $chat = Livewire::test(LaunchpadChat::class)
        ->call('start')
        ->set('buyerName', 'Sarah')->call('submitStep')
        ->set('businessDescription', 'Leadership coaching for mid-career executives')->call('submitStep')
        ->call('chooseTool', 'Claude CoWork')
        ->set('businessRole', 'Executive coach')->call('submitStep')
        ->set('whoTheyServe', 'Directors and VPs navigating step ups')->call('submitStep')
        ->set('timeDrainChecks', ['Client emails and messages'])->call('submitStep')
        ->set('tediousWork', 'Writing session notes and chasing invoices')->call('submitStep')
        ->set('oneHandoffToday', 'Drafting the session follow-up emails')->call('submitStep')
        ->call('chooseAiUsage', 'Regular')
        ->set('buyerEmail', 'sarah@example.com')->call('submitStep');

    $newLead = Lead::find($chat->get('leadId'));
    expect($newLead->status)->toBe('duplicate');
});

it('keeps chat session in sync with current step', function () {
    Livewire::test(LaunchpadChat::class)
        ->call('start')
        ->set('buyerName', 'Sarah')
        ->call('submitStep')
        ->set('businessDescription', 'Leadership coaching for mid-career executives')
        ->call('submitStep');

    $session = ChatSession::first();
    expect($session->current_step)->toBe(3);
    expect($session->answers['buyer_name'])->toBe('Sarah');
});
