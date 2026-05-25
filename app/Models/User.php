<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\ChickenGoal;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tier',
        'is_admin',
        'yearly_egg_goal',
        'egg_price',
        'chicken_goal',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'egg_price' => 'decimal:2',
            'chicken_goal' => ChickenGoal::class,
        ];
    }

    public function eggEntries(): HasMany
    {
        return $this->hasMany(EggEntry::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function feedInventory(): HasMany
    {
        return $this->hasMany(FeedInventory::class);
    }

    public function flockProfile(): HasOne
    {
        return $this->hasOne(FlockProfile::class);
    }

    public function flockBatches(): HasMany
    {
        return $this->hasMany(FlockBatch::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function isPremium(): bool
    {
        return $this->tier === 'premium' || $this->is_admin;
    }

    public function isFree(): bool
    {
        return $this->tier === 'free' && ! $this->is_admin;
    }
}
