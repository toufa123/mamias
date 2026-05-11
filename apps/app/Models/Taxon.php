<?php

namespace App\Models;

use App\Enums\Catalogue_Status;
use App\Enums\Worms_Status;
use App\Services\TaxonNormalizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;
use Illuminate\Database\Eloquent\Attributes\Table;

/**
 * Class Taxon
 *
 * @property int $id
 * @property int|null $aphia_id
 * @property string|null $url
 * @property string $scientificname
 * @property string|null $authority
 * @property Worms_Status $worms_status
 * @property Catalogue_Status $catalogue_status
 * @property string|null $unacceptreason
 * @property string|null $rank
 * @property string|null $kingdom
 * @property string|null $phylum
 * @property string|null $class
 * @property string|null $order
 * @property string|null $family
 * @property string|null $genus
 * @property string|null $lsid
 * @property string|null $proposed_accepted_name
 * @property bool $is_extinct
 * @property array|null $environments
 * @property array|null $synonyms_data
 * @property string|null $Easin_id
 * @property \Illuminate\Support\Carbon|null $fetched_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $created_by
 * @property int|null $updated_by
 */
#[Fillable([
    'aphia_id',
    'url',
    'scientificname',
    'authority',
    'worms_status',
    'catalogue_status',
    'unacceptreason',
    'rank',
    'kingdom',
    'phylum',
    'class',
    'order',
    'family',
    'genus',
    'lsid',
    'proposed_accepted_name',
    'is_extinct',
    'environments',
    'synonyms_data',
    'Easin_id',
    'fetched_at',
    'notes',
])]
#[Table('taxas')]
class Taxon extends Model
{
    use HasFactory, SoftDeletes, Userstamps;

    protected static function booted(): void
    {
        static::saving(function (Taxon $taxon): void {
            app(TaxonNormalizer::class)->normalize($taxon);
        });
    }

    protected function casts(): array
    {
        return [
            'aphia_id' => 'integer',
            'is_extinct' => 'boolean',
            'environments' => 'array',
            'worms_status' => Worms_Status::class,
            'catalogue_status' => Catalogue_Status::class,
            'synonyms_data' => 'array',
            'fetched_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
