<?php

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
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

it('shows the users list for admins', function () {
    $buyer = User::factory()->create(['name' => 'Karen Whitfield', 'email' => 'karen@example.com']);

    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$buyer])
        ->assertSee('Karen Whitfield')
        ->assertSee('karen@example.com');
});

it('shows the assistant count for each user', function () {
    $buyer = User::factory()->create();

    Assistant::factory()->count(3)->create(['user_id' => $buyer->id]);

    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$buyer])
        ->assertSee('3');
});

it('has an impersonate action on non-admin users', function () {
    $buyer = User::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionVisible('impersonate', $buyer);
});

it('excludes admin users from the users list', function () {
    $buyer = User::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$buyer])
        ->assertCanNotSeeTableRecords([$this->admin]);
});

it('has an edit action on users', function () {
    $buyer = User::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionVisible('edit', $buyer);
});

it('allows editing a user name and email', function () {
    $buyer = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    $this->actingAs($this->admin);

    Livewire::test(EditUser::class, ['record' => $buyer->getRouteKey()])
        ->fillForm([
            'name' => 'New Name',
            'email' => 'new@example.com',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $buyer->refresh();
    expect($buyer->name)->toBe('New Name')
        ->and($buyer->email)->toBe('new@example.com');
});

it('has a delete action with confirmation', function () {
    $buyer = User::factory()->create();

    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionVisible('delete', $buyer);
});

it('can delete a user after confirmation', function () {
    $buyer = User::factory()->create();
    $buyerId = $buyer->id;

    $this->actingAs($this->admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('delete', $buyer);

    expect(User::find($buyerId))->toBeNull();
});

it('does not show the users list for non-admin users', function () {
    $buyer = User::factory()->create();

    $this->actingAs($buyer)
        ->get('/admin/users')
        ->assertForbidden();
});
