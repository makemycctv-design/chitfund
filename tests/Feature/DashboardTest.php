<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_are_routed_to_their_dashboard()
    {
        // Factory users are customers; /dashboard redirects them to the portal.
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')->assertRedirect(route('portal.dashboard', absolute: false));
    }

    public function test_staff_are_routed_to_the_admin_dashboard()
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard', absolute: false));
    }
}
