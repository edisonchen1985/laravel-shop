<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeFirstAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_confirm_first_admin_setup_for_an_existing_user(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'first-admin@example.com']);

        // Act
        $this->artisan('app:make-first-admin', ['email' => $user->email])
            ->expectsConfirmation('Grant initial Admin access to first-admin@example.com?', 'yes')
            ->expectsOutput('first-admin@example.com is now an administrator.')
            ->assertExitCode(0);

        // Assert
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_admin' => true,
        ]);
    }

    public function test_first_admin_command_cannot_promote_another_user_after_admin_exists(): void
    {
        // Arrange
        $existingAdmin = User::factory()->create();
        $existingAdmin->forceFill(['is_admin' => true])->save();
        $regularUser = User::factory()->create(['email' => 'regular@example.com']);

        // Act
        $this->artisan('app:make-first-admin', ['email' => $regularUser->email])
            ->expectsConfirmation('Grant initial Admin access to regular@example.com?', 'yes')
            ->expectsOutput('An administrator already exists; first-admin setup is disabled.')
            ->assertExitCode(1);

        // Assert
        $this->assertDatabaseHas('users', [
            'id' => $regularUser->id,
            'is_admin' => false,
        ]);
    }

    public function test_canceling_first_admin_setup_makes_no_changes(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'cancelled-admin@example.com']);

        // Act
        $this->artisan('app:make-first-admin', ['email' => $user->email])
            ->expectsConfirmation('Grant initial Admin access to cancelled-admin@example.com?', 'no')
            ->expectsOutput('No changes made.')
            ->assertExitCode(1);

        // Assert
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_admin' => false,
        ]);
    }
}
