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
use Mattiverse\Userstamps\Traits\Userstamps;

#[Fillable(['intro_event_id', 'category', 'subcategory', 'pathway_type', 'description', 'uncertainty'])]
class PathwayRecord extends Model
{
    use HasFactory, Userstamps;
    
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
