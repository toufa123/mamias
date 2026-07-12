<?php

declare(strict_types=1);

namespace App\Filament\Resources\IntroEventRecords\Pages;

use App\Filament\Resources\IntroEventRecords\IntroEventRecordResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Page for creating intro event records.
 */
class CreateIntroEventRecord extends CreateRecord
{
    protected static string $resource = IntroEventRecordResource::class;
}
