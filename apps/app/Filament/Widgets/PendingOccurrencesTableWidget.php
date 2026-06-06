<?php

namespace App\Filament\Widgets;

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\Occurrences\Actions\OccurrenceActions;
use App\Filament\Resources\Occurrences\Tables\OccurrencesTable;
use App\Models\Occurrence;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingOccurrencesTableWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return OccurrencesTable::configure($table)
            ->heading('Recent Pending Occurrences')
            ->description('The 5 most recent occurrence reports awaiting review')
            ->query(fn (): Builder => Occurrence::where('status', OccurrenceStatus::PENDING)
                ->with(['user', 'taxon', 'introEventRecord.taxon'])
                ->latest()
                ->limit(5))
            ->filters([])
            ->recordActions([
                OccurrenceActions::makeApproveAction(),
                OccurrenceActions::makeRejectAction(),
            ])
            ->paginated(false);
    }
}
