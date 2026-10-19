<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * All accounts have the same access level: every user acts as a Duty Office Watch
 * and can use every part of the system.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'rank', 'name', 'serial_number', 'designation', 'username', 'email',
        'is_active', 'password', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /** Rank and name as written on registers. */
    public function displayName(): string
    {
        return trim(($this->rank ? $this->rank.' ' : '').$this->name);
    }

    public function dutyPeriods(): HasMany
    {
        return $this->hasMany(DutyPeriod::class);
    }

    public function activeDutyPeriod(): ?DutyPeriod
    {
        return $this->dutyPeriods()->where('status', DutyPeriod::ACTIVE)->latest('started_at')->first();
    }
}
