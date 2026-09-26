<?php

namespace Tests\Feature;

use App\Enums\LiteratureStatus;
use App\Models\Literature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Class-based, so the Pest RefreshDatabase binding in tests/Pest.php does not
 * reach it: without the trait its rows were committed into mamias_test.
 */
class LiteratureAlertBoxTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_alert_box_lists_references_with_submitter_comments_awaiting_a_reply()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $submitter = User::factory()->create();

        $reference = Literature::factory()->create(['status' => LiteratureStatus::APPROVED, 'created_by' => $submitter->id]);
        $reference->comment('Is the year right?', $submitter);

        $this->actingAs($admin)
            ->get('/mamias')
            ->assertSee('New comments on references')
            ->assertSee($reference->code);
    }
}
