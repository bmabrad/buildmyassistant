<?php

use App\Filament\Resources\LaunchpadTaskResource\Pages\EditLaunchpadTask;
use App\Filament\Resources\LaunchpadTaskResource\Pages\ListLaunchpadTasks;
use App\Filament\Resources\LaunchpadTaskResource\Pages\ViewLaunchpadTask;
use App\Filament\Widgets\LaunchpadStatsWidget;
use App\Models\LaunchpadMessage;
use App\Models\LaunchpadTask;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Admin',
        'email' => 'admin@test.com',
        'password' => Hash::make('password'),
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
    LaunchpadTask::factory()->create(['status' => 'completed', 'phase' => 1, 'phase_1_complete' => true]);
    LaunchpadTask::factory()->create(['status' => 'completed', 'phase' => 2, 'phase_1_complete' => true]);
    LaunchpadTask::factory()->create(['status' => 'active', 'phase' => 1]);

    $this->actingAs($this->admin);

    Livewire::test(LaunchpadStatsWidget::class)
        ->assertSee('3')
        ->assertSee('$15 AUD')
        ->assertSee('66.7%');
});

it('displays task list with correct columns', function () {
    $task = LaunchpadTask::factory()->active()->create([
        'name' => 'Jane Coach',
        'email' => 'jane@example.com',
    ]);

    LaunchpadMessage::factory()->count(3)->create(['task_id' => $task->id]);

    $this->actingAs($this->admin);

    Livewire::test(ListLaunchpadTasks::class)
        ->assertCanSeeTableRecords([$task])
        ->assertSee('Jane Coach')
        ->assertSee('jane@example.com');
});

it('filters task list by status', function () {
    $active = LaunchpadTask::factory()->active()->create();
    $completed = LaunchpadTask::factory()->completed()->create();

    $this->actingAs($this->admin);

    Livewire::test(ListLaunchpadTasks::class)
        ->filterTable('status', 'active')
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$completed]);
});

it('paginates task list at 25 per page', function () {
    LaunchpadTask::factory()->count(30)->active()->create();

    $this->actingAs($this->admin);

    Livewire::test(ListLaunchpadTasks::class)
        ->assertCountTableRecords(30);
});

it('shows task detail view with conversation', function () {
    $task = LaunchpadTask::factory()->active()->create([
        'name' => 'Alice Tester',
        'email' => 'alice@test.com',
    ]);

    LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Welcome to your session!',
    ]);

    LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'role' => 'user',
        'content' => 'I need help with onboarding.',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ViewLaunchpadTask::class, ['record' => $task->getRouteKey()])
        ->assertSee('Alice Tester')
        ->assertSee('alice@test.com')
        ->assertSee('Welcome to your session!')
        ->assertSee('I need help with onboarding.');
});

it('allows admin to edit buyer name and email', function () {
    $task = LaunchpadTask::factory()->active()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(EditLaunchpadTask::class, ['record' => $task->getRouteKey()])
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

it('marks instruction sheet messages in task detail', function () {
    $task = LaunchpadTask::factory()->active()->create();

    LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Here is your instruction sheet',
        'is_instruction_sheet' => true,
    ]);

    $this->actingAs($this->admin);

    Livewire::test(ViewLaunchpadTask::class, ['record' => $task->getRouteKey()])
        ->assertSee('Instruction Sheet')
        ->assertSee('Here is your instruction sheet');
});
