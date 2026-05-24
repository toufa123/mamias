<?php

namespace Tests\Feature;

use App\Enums\LiteratureStatus;
use App\Models\Literature;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LiteratureAlertBoxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Literature::query()->delete();
        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('scientist', 'web');
        Role::findOrCreate('user', 'web');
    }

    public function test_alert_box_is_displayed_to_super_admin_when_pending_references_exist()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        Literature::factory()->create(['status' => LiteratureStatus::PENDING]);

        $response = $this->actingAs($admin)
            ->get('/mamias');

        $response->assertStatus(200);
        $response->assertSee('Pending References');
        $response->assertSee('pending review');
    }

    public function test_alert_box_is_not_displayed_to_regular_user()
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        Literature::factory()->create(['status' => LiteratureStatus::PENDING]);

        $this->actingAs($user)
            ->get('/mamias')
            ->assertDontSee('Review them now');
    }

    public function test_alert_box_is_not_displayed_when_no_pending_references()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        Literature::factory()->create(['status' => LiteratureStatus::APPROVED]);

        $this->actingAs($admin)
            ->get('/mamias')
            ->assertDontSee('Review them now');
    }
}
