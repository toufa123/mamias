<?php

namespace App\Models;

use App\Enums\AssessmentScale;
use App\Enums\DataQuality;
use App\Enums\EicatCategory;
use App\Enums\EicatMechanism;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * EICAT (Environmental Impact Classification for Alien Taxa) assessment record.
 *
 * Links an impact category, mechanism, confidence level, and supporting rationale
 * to an introduction event record for a non-indigenous species.
 *
 * @property int $id
 * @property int $intro_event_record_id
 * @property int|null $literature_id
 * @property EicatCategory $category
 * @property EicatMechanism $mechanism
 * @property DataQuality $confidence
 * @property string|null $rationale
 * @property AssessmentScale $assessment_scale
 * @property Carbon|null $assessed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 *
 * @method BelongsTo introEventRecord()
 * @method BelongsTo literature()
 */
#[Fillable(['intro_event_record_id', 'category', 'mechanism', 'confidence', 'rationale', 'assessment_scale', 'assessed_at', 'literature_id'])]
class EicatAssessment extends Model
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
     * The introduction event record that this EICAT assessment belongs to.
     */
    public function introEventRecord(): BelongsTo
    {
        return $this->belongsTo(IntroEventRecord::class);
    }

    /**
     * The literature reference supporting this assessment.
     */
    public function literature(): BelongsTo
    {
        return $this->belongsTo(Literature::class);
    }

    protected function casts(): array
    {
        return [
            'category' => EicatCategory::class,
            'mechanism' => EicatMechanism::class,
            'confidence' => DataQuality::class,
            'assessment_scale' => AssessmentScale::class,
            'assessed_at' => 'date',
        ];
    }
}
