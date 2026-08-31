<?php

namespace App\Models;

use App\Enums\EventStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'event_date',
        'location',
        'registration_start',
        'registration_end',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'registration_start' => 'date',
            'registration_end' => 'date',
            'status' => EventStatus::class,
        ];
    }

    public function raceCategories(): HasMany
    {
        return $this->hasMany(RaceCategory::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function activeRaceCategories(): HasMany
    {
        return $this->raceCategories()->where('status', 'active')->orderBy('distance');
    }

    public function isRegistrationOpen(?CarbonInterface $at = null): bool
    {
        $now = $at ?? now();

        return $this->status === EventStatus::Open
            && $now->between($this->registration_start->startOfDay(), $this->registration_end->endOfDay());
    }

    public function scopeOpenForRegistration($query): void
    {
        $query->where('status', EventStatus::Open->value)
            ->whereDate('registration_start', '<=', today())
            ->whereDate('registration_end', '>=', today())
            ->orderBy('event_date');
    }
}
