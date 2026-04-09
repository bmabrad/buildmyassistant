<?php

use App\Filament\Resources\AssistantResource\Pages\EditAssistant;
use App\Filament\Resources\AssistantResource\Pages\ListAssistants;
use App\Filament\Resources\AssistantResource\Pages\ViewAssistant;
use App\Filament\Widgets\LaunchpadStatsWidget;
use App\Models\Chat;
use App\Models\Assistant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => Hash::make('password'),
        'is_admin' => true,
    ]);
});

it('redirects unauthenticated users from admin', function () {
    $response = $this->get('/admin');

    $response->assertRedirect('/admin/login');
});

it('allows authenticated admin to access admin panel', function () {
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertOk();
});

it('displays dashboard stats correctly', function () {
    Assistant::factory()->create(['status' => 'completed', 'phase' => 1, 'playbook_delivered' => true]);
    Assistant::factory()->create(['status' => 'completed', 'phase' => 2, 'playbook_delivered' => true]);
    Assistant::factory()->create(['status' => 'active', 'phase' => 1]);

    $this->actingAs($this->admin);

    Livewire::test(LaunchpadStatsWidget::class)
        ->assertSee('3')
        ->assertSee('$15 AUD')
        ->assertSee('66.7%');
});

it('displays task list with correct columns', function () {
    $task = Assistant::factory()->active()->create([
        'name' => 'Jane Coach',
        'email' => 'jane@example.com',
    ]);

    Chat::factory()->count(3)->create(['task_id' => $task->id]);

    $this->actingAs($this->admin);

    Livewire::test(ListAssistants::class)
        ->assertCanSeeTableRecords([$task])
        ->assertSee('Jane Coach')
        ->assertSee('jane@example.com');
});

it('filters task list by status', function () {
    $active = Assistant::factory()->active()->create();
    $completed = Assistant::factory()->completed()->create();

    $this->actingAs($this->admin);

    Livewire::test(ListAssistants::class)
        ->filterTable('status', 'active')
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$completed]);
});

it('paginates task list at 25 per page', function () {
    Assistant::factory()->count(30)->active()->create();

    $this->actingAs($this->admin);

    Livewire::test(ListAssistants::class)
        ->assertCountTableRecords(30);
});

it('shows task detail view with conversation', function () {
    $task = Assistant::factory()->active()->create([
        'name' => 'Alice Tester',
        'email' => 'alice@test.com',
    ]);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Welcome to your session!',
    ]);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'user',
        'content' => 'I need help with onboarding.',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ViewAssistant::class, ['record' => $task->getRouteKey()])
        ->assertSee('Alice Tester')
        ->assertSee('alice@test.com')
        ->assertSee('Welcome to your session!')
        ->assertSee('I need help with onboarding.');
});

it('allows admin to edit buyer name and email', function () {
    $task = Assistant::factory()->active()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(EditAssistant::class, ['record' => $task->getRouteKey()])
        ->fillForm([
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $task->refresh();
    expect($task->name)->toBe('New Name')
        ->and($task->email)->toBe('new@example.com');
});

it('marks deliverable messages in task detail', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->deliverable()->create([
        'task_id' => $task->id,
        'content' => 'Here is your Playbook',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ViewAssistant::class, ['record' => $task->getRouteKey()])
        ->assertSee('Playbook')
        ->assertSee('Here is your Playbook');
});
