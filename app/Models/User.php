<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $phone
 * @property Carbon|null $phone_verified_at
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $google_id
 * @property string $locale
 * @property Carbon|null $banned_at
 * @property string|null $ban_reason
 * @property Carbon|null $last_login_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'phone', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasLocalePreference, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'banned_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<Business, $this>
     */
    public function ownedBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'owner_id');
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return BelongsToMany<Business, $this>
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'favorites')->withPivot('created_at');
    }

    /**
     * @return HasMany<BusinessClaim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(BusinessClaim::class);
    }

    /**
     * Has a usable mobile number. Without SMS verification (no provider yet)
     * the number given at sign-up is accepted as is.
     */
    public function hasVerifiedPhone(): bool
    {
        return $this->phone !== null
            && ($this->phone_verified_at !== null || ! config('auth_options.sms_verification'));
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole([Role::Admin->value, Role::Moderator->value]);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->isStaff() && ! $this->isBanned();
    }

    public function getFilamentName(): string
    {
        return $this->name ?? $this->phone ?? $this->email ?? "#{$this->id}";
    }

    public function preferredLocale(): string
    {
        return $this->locale;
    }

    public function displayName(): string
    {
        return $this->name ?? __('Membre');
    }
}
