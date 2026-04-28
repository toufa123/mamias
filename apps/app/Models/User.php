<?php
    
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
     */
    
    use Database\Factories\UserFactory;
    use Filament\Models\Contracts\FilamentUser;
    use Filament\Models\Contracts\HasAvatar;
    use Filament\Models\Contracts\HasName;
    use Filament\Panel;
    use Illuminate\Contracts\Auth\MustVerifyEmail;
    use Illuminate\Database\Eloquent\Attributes\Fillable;
    use Illuminate\Database\Eloquent\Attributes\Hidden;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Foundation\Auth\User as Authenticatable;
    use Illuminate\Notifications\Notifiable;
    use Spatie\Permission\Traits\HasRoles;
    
    #[Fillable([
        'title',
        'name',
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
        use HasFactory, Notifiable, HasRoles;
        
        /**
         * Bootstrap the model - run during model initialization.
         *
         * Automatically synchronizes the `name` attribute by concatenating first_name and last_name.
         * This observer runs whenever the user is being saved, ensuring the full name is always kept in sync.
         * Falls back to 'John Doe' if both first and last names are empty.
         *
         * @return void
         */
        protected static function booted(): void
        {
            static::saving(function ($user) {
                if ($user->isDirty(['first_name', 'last_name'])) {
                    $user->name = trim("{$user->first_name} {$user->last_name}") ? : 'John Doe';
                }
            });
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
        
        /**
         * Get the display name for Filament admin panel.
         *
         * Returns the user's full name (first_name + last_name) as displayed in the Filament UI.
         * Falls back to 'John Doe' if both first and last names are empty.
         *
         * This implements the HasName contract required by Filament.
         *
         * @return string User's full name formatted for display
         */
        public function getFilamentName(): string
        {
            return trim("{$this->first_name} {$this->last_name}") ? : 'John Doe';
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
         *
         * @return bool True if user is allowed to access the panel, false otherwise
         */
        public function canAccessPanel(Panel $panel): bool
        {
            // Example: Restrict admin panel to verified users with specific email domain
            //        if ($panel->getId() === 'admin') {
            //            return str_ends_with($this->email, '@yourdomain.com') && $this->hasVerifiedEmail();
            //        }
            
            return true;
        }
        
        /**
         * Get the attributes that should be cast.
         *
         * Casts email_verified_at to datetime object and hashes password before storage.
         * Password is automatically hashed using Laravel's hashing algorithm on save.
         *
         * @return array<string, string> Type cast mapping for model attributes
         */
        protected function casts(): array
        {
            return [
                'email_verified_at' => 'datetime',
                'password' => 'hashed',
                'taxonomic_area' => 'array',
                'subregions' => 'array',
            ];
        }
        
    }
