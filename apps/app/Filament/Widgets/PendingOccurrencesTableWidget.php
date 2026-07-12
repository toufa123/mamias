<?php

namespace App\Filament\Widgets;

use App\Enums\OccurrenceStatus;
use App\Filament\Resources\Occurrences\Actions\OccurrenceActions;
use App\Filament\Resources\Occurrences\Tables\OccurrencesTable;
use App\Models\Occurrence;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Table widget listing the five most recent pending occurrence reports
 * awaiting moderation review, with inline approve/reject actions.
 */
class PendingOccurrencesTableWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    /**
     * @param  Table  $table  The Filament table instance to configure.
     */
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
