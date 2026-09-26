<?php

declare(strict_types=1);

use App\Models\User;
use Heyosseus\Vacuum\Vacuum;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

// Rendering the page is not possible here: Vacuum refuses to query inside the
// RefreshDatabase transaction, so the test covers the gate every page uses.
it('lets only a super admin into vacuum', function (string $role, bool $allowed) {
    Role::findOrCreate($role, 'web');
    $user = User::factory()->create()->assignRole($role);
    $request = Request::create('/mamias/vacuum');
    $request->setUserResolver(fn (): User => $user);

    expect(Vacuum::check($request))->toBe($allowed);
})->with([
    'super_admin' => ['super_admin', true],
    'scientist' => ['scientist', false],
    'user' => ['user', false],
]);
