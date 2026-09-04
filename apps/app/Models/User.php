<?php

declare(strict_types=1);

namespace App\Models;

/**
 * User Model
 *
 * Represents a user in the MAMIAS system with authentication, profile, and Filament admin panel support.
 *
 * Features:
 * - Authentication with email verification required
 * - User profile attributes (name, location, contact info, bio)
 * - Avatar generation via UI Avatars API
 * - Filament admin panel integration with role-based access control
 * - Automatic name synchronization from first/last name fields
 *
 * @property int $id
 * @property string $name Full name (auto-generated from first_name + last_name)
 * @property string $email User email address (unique, verified required)
 * @property string $password Hashed password
 * @property string|null $first_name User first name
 * @property string|null $last_name User last name
 * @property string|null $phone Phone number
 * @property bool $has_whatsapp Whether user has WhatsApp
 * @property string|null $country Country of residence
 * @property string|null $taxonomic_area Taxonomic research area
 * @property string|null $subregions Geographic subregions (comma-separated or JSON)
 * @property string|null $countries Countries of focus (comma-separated or JSON)
 * @property string|null $title Job title or professional title
 * @property string|null $bio User biography
 * @property \DateTime|null $email_verified_at Timestamp when email was verified
 * @property \DateTime $created_at Timestamp when created
 * @property \DateTime $updated_at Timestamp when last updated
 * @property string|null $remember_token Token for "remember me" functionality
 *
 * @method HasMany literatures()
 * @method HasMany nisSuggestions()
 * @method HasMany introEventRecords()
 * @method HasMany occurrences()
 */

use App\Notifications\VerifyEmail;
use Database\Factories\UserFactory;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'title',
    'email',
    'password',
    'first_name',
    'last_name',
    'taxonomic_area',
    'subregions',
    'countries',
    'phone',
    'has_whatsapp',
    'bio',
    'country',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar, HasName, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * Bootstrap the model - run during model initialization.
     *
     * Automatically synchronizes the `name` attribute by concatenating first_name and last_name.
     * This observer runs whenever the user is being saved, ensuring the full name is always kept in sync.
     * Falls back to email local-part if both first and last names are empty.
     */
    protected static function booted(): void
    {
        static::saving(function ($user) {
            if ($user->isDirty(['first_name', 'last_name'])) {
                $user->name = $user->getComputedFullName();
            }
        });
    }

    /**
     * Compute the user's full name from first and last name fields.
     * Falls back to the email local-part if both name fields are empty.
     */
    protected function getComputedFullName(): string
    {
        $fullName = trim("{$this->first_name} {$this->last_name}");

        if ($fullName !== '') {
            return $fullName;
        }

        return str($this->email)->before('@')->toString();
    }

    /**
     * Get the user's full name with role labels appended in parentheses.
     * Returns null if both first and last names are empty.
     */
    public function getFormattedNameWithRoles(): ?string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        if ($name === '') {
            return null;
        }

        $roles = $this->getRoleNames()->all();

        if (! empty($roles)) {
            $name .= ' ('.implode(', ', $roles).')';
        }

        return $name;
    }

    /**
     * Get the user's avatar URL for Filament admin panel.
     *
     * Generates a dynamic avatar using UI Avatars API based on the user's full name.
     * The avatar is generated with white text (#FFFFFF) on a teal background (#018d9a).
     *
     * This implements the HasAvatar contract required by Filament.
     *
     * @return string|null Absolute URL to user's avatar image, or null if not available
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return 'https://ui-avatars.com/api/?name='.urlencode($this->getFilamentName()).'&color=FFFFFF&background=018d9a';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFilamentAvatarUrl();
    }

    /**
     * Get the display name for Filament admin panel.
     *
     * Returns the user's full name (first_name + last_name) as displayed in the Filament UI.
     * Falls back to email local-part if both first and last names are empty.
     *
     * This implements the HasName contract required by Filament.
     *
     * @return string User's full name formatted for display
     */
    public function getFilamentName(): string
    {
        return $this->getComputedFullName();
    }

    /**
     * Determine if user can access the Filament admin panel.
     *
     * Currently allows all authenticated users to access the admin panel.
     *
     * Uncomment and customize the admin panel check section to restrict access:
     *   - Require specific email domain (e.g., @yourdomain.com)
     *   - Require verified email address (MustVerifyEmail contract)
     *   - Implement role-based access control
     *
     * This implements the FilamentUser contract required by Filament.
     *
     * @param  Panel  $panel  The Filament panel to check access for
     * @return bool True if user is allowed to access the panel, false otherwise
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'mamias') {
            return false;
        }

        if (app()->environment('local')) {
            return true;
        }

        return $this->hasAnyRole(['super_admin', 'scientist']);
    }

    public function sendEmailVerificationNotification(): void
    {
        $notification = app(VerifyEmail::class);
        $notification->url = Filament::getVerifyEmailUrl($this);
        $this->notify($notification);
    }

    /**
     * Get the attributes that should be cast.
     *
     * Casts email_verified_at to datetime object and hashes password before storage.
     * Password is automatically hashed using Laravel's hashing algorithm on save.
     *
     * @return array<string, string> Type cast mapping for model attributes
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'title', 'email', 'phone', 'country', 'taxonomic_area', 'subregions', 'countries', 'bio', 'has_whatsapp'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'has_whatsapp' => 'boolean',
            'taxonomic_area' => 'array',
            'subregions' => 'array',
            'countries' => 'array',
        ];
    }

    /**
     * Coerce has_whatsapp to a real boolean on assignment.
     *
     * The column is NOT NULL DEFAULT false, but Eloquent's "boolean" cast lets
     * null through untouched. The user form only sets this field as a side
     * effect of the phone-number lookup, so saving a user without a phone sent
     * a literal null and the insert failed on the not-null constraint.
     */
    protected function hasWhatsapp(): Attribute
    {
        return Attribute::make(
            set: fn ($value): bool => (bool) $value,
        );
    }

    public function literatures(): HasMany
    {
        return $this->hasMany(Literature::class, 'created_by');
    }

    public function nisSuggestions(): HasMany
    {
        return $this->hasMany(NisSuggestion::class);
    }

    public function introEventRecords(): HasMany
    {
        return $this->hasMany(IntroEventRecord::class, 'created_by');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Occurrence::class);
    }
}
