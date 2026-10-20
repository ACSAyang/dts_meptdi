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
        'rank', 'first_name', 'middle_initial', 'last_name', 'name', 'serial_number', 'designation',
        'username', 'email', 'is_active', 'password', 'last_login_at',
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

    protected static function booted(): void
    {
        // Keep the stored full name in step with its parts (used for sorting and searching).
        static::saving(function (User $user) {
            if ($user->middle_initial !== null) {
                $user->middle_initial = strtoupper(rtrim(trim($user->middle_initial), '.')) ?: null;
            }
            if ($user->first_name || $user->last_name) {
                $user->name = self::joinName($user->first_name, $user->middle_initial, $user->last_name);
            }
        });
    }

    /** @return array<string, string> Rank abbreviation => title, ASN to ADM. */
    public static function ranks(): array
    {
        return array_merge(...array_values(config('dts.ranks')));
    }

    public static function joinName(?string $first, ?string $middleInitial, ?string $last): string
    {
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([$first, $middleInitial, $last]))));
    }

    /**
     * Name as written on registers, e.g. "ASN Ian Russel U Pascual PCG".
     * The PCG suffix is added for uniformed personnel (those with a rank).
     */
    public function displayName(): string
    {
        $name = $this->first_name || $this->last_name
            ? self::joinName($this->first_name, $this->middle_initial, $this->last_name)
            : (string) $this->name;

        return trim(implode(' ', array_filter([
            $this->rank,
            $name,
            $this->rank ? config('dts.name_suffix') : null,
        ])));
    }

    public function rankTitle(): ?string
    {
        return self::ranks()[$this->rank] ?? null;
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
