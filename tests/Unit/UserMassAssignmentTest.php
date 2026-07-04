<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserMassAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_privilege_columns_are_not_mass_assignable(): void
    {
        $user = User::factory()->create();

        $user->update([
            'name' => 'Updated Name',
            'tier' => 'premium',
            'is_admin' => true,
        ]);

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('free', $user->tier);
        $this->assertFalse($user->is_admin);
    }
}
