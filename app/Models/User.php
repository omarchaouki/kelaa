<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'phone', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName
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

    public function hasVerifiedPhone(): bool
    {
        return $this->phone !== null && $this->phone_verified_at !== null;
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

    public function displayName(): string
    {
        return $this->name ?? __('Membre');
    }
}
