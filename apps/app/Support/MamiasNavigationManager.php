<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;

/**
 * Two sidebar rules no plugin can express on its own:
 *
 * - heyosseus/vacuum hardcodes a top-level "Vacuum" group in final classes, so
 *   its items are re-homed here: Overview becomes a "Vacuum" parent under
 *   System and the rest nest beneath it.
 * - The System group is shown to super_admin only. This hides menu entries;
 *   each page still authorizes itself.
 */
class MamiasNavigationManager extends NavigationManager
{
    private const SYSTEM = 'System';

    private const VACUUM = 'Vacuum';

    /**
     * @return array<NavigationItem>
     */
    public function getNavigationItems(): array
    {
        $isSuperAdmin = auth()->user()?->hasRole('super_admin') ?? false;

        foreach (parent::getNavigationItems() as $item) {
            if ($item->getGroup() === self::VACUUM) {
                $isOverview = $item->getParentItem() === null && $item->getLabel() === 'Overview';

                $item->group(self::SYSTEM);
                $isOverview ? $item->label(self::VACUUM) : $item->parentItem(self::VACUUM);
            }

            if ($item->getGroup() === self::SYSTEM && ! $isSuperAdmin) {
                $item->hidden();
            }
        }

        return parent::getNavigationItems();
    }
}
