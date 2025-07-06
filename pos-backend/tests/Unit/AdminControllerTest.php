<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;
use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private $adminController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminController = new AdminController();
    }

    public function test_store_creates_new_admin_successfully()
    {
        // Create a test user
        $user = User::factory()->create();

        // Call the store method
        $response = $this->adminController->store($user->id);

        // Assert response is correct
        $this->assertEquals(200, $response->status());
        $this->assertEquals('User promoted to admin', $response->getData()->message);

        // Assert admin was created in database
        $this->assertDatabaseHas('admins', ['user_id' => $user->id]);
    }

    public function test_store_returns_404_for_non_existent_user()
    {
        // Call store with non-existent user ID
        $response = $this->adminController->store(999);

        // Assert response is correct
        $this->assertEquals(404, $response->status());
        $this->assertEquals('User not found', $response->getData()->message);
    }

    public function test_store_prevents_duplicate_admin_creation()
    {
        // Create a test user and make them admin
        $user = User::factory()->create();
        Admin::create(['user_id' => $user->id]);

        // Try to make them admin again
        $response = $this->adminController->store($user->id);

        // Assert response is correct
        $this->assertEquals(400, $response->status());
        $this->assertEquals('User is already an admin', $response->getData()->message);
    }

    public function test_remove_admin_succeeds()
    {
        // Create a test user and make them admin
        $user = User::factory()->create();
        Admin::create(['user_id' => $user->id]);

        // Remove admin status
        $response = $this->adminController->removeAdmin($user->id);

        // Assert response is correct
        $this->assertEquals(200, $response->status());
        $this->assertEquals('User removed from admin', $response->getData()->message);

        // Assert admin was removed from database
        $this->assertDatabaseMissing('admins', ['user_id' => $user->id]);
    }

    public function test_remove_admin_returns_400_for_non_admin_user()
    {
        // Create a test user (not an admin)
        $user = User::factory()->create();

        // Try to remove admin status
        $response = $this->adminController->removeAdmin($user->id);

        // Assert response is correct
        $this->assertEquals(400, $response->status());
        $this->assertEquals('User is not an admin', $response->getData()->message);
    }
}
