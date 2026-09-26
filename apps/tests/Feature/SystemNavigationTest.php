<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;

function navigationGroupLabels(): array
{
    Filament::setCurrentPanel(Filament::getPanel('mamias'));

    return collect(Filament::getNavigation())
        ->map(fn (NavigationGroup $group): ?string => $group->getLabel())
        ->all();
}

it('shows the System group to a super admin', function () {
    $this->actingAs(User::factory()->create()->assignRole('super_admin'));

    expect(navigationGroupLabels())->toContain('System');
});

it('hides the System group from a scientist', function () {
    $this->actingAs(User::factory()->create()->assignRole('scientist'));

    expect(navigationGroupLabels())->not->toContain('System');
});
