<?php

namespace App\Models;

use App\Enums\CbdPathwayCategory;
use App\Enums\CbdPathwaySubcategory;
use App\Enums\DataQuality;
use App\Enums\PathwayType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Pathway record describing how a species was introduced into a region.
 *
 * Uses CBD pathway classification (category + subcategory) with
 * pathway type (primary/secondary) and uncertainty assessment.
 *
 * @property int $id
 * @property int $intro_event_id
 * @property CbdPathwayCategory $category
 * @property CbdPathwaySubcategory|null $subcategory
 * @property PathwayType $pathway_type
 * @property string|null $description
 * @property DataQuality $uncertainty
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @method BelongsTo introEvent()
 */
#[Fillable(['intro_event_id', 'category', 'subcategory', 'pathway_type', 'description', 'uncertainty'])]
class PathwayRecord extends Model
{
    use HasFactory, LogsActivity, Userstamps;

    /**
     * Configure activity logging to track all attribute changes.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * The introduction event this pathway record belongs to.
     */
    public function introEvent(): BelongsTo
    {
        return $this->belongsTo(IntroEventRecord::class, 'intro_event_id');
    }

    protected function casts(): array
    {
        return [
            'category' => CbdPathwayCategory::class,
            'subcategory' => CbdPathwaySubcategory::class,
            'pathway_type' => PathwayType::class,
            'uncertainty' => DataQuality::class,
        ];
    }
}
