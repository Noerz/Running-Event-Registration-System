<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Participant extends Model
{
    protected $fillable = [
        'event_id',
        'race_category_id',
        'registration_code',
        'bib_number',
        'full_name',
        'phone',
        'email',
        'gender',
        'birth_place',
        'birth_date',
        'age',
        'identity_number',
        'address',
        'city',
        'province',
        'jersey_size',
        'running_club',
        'emergency_contact_name',
        'emergency_contact_phone',
        'registration_status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
            'registration_status' => RegistrationStatus::class,
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function raceCategory(): BelongsTo
    {
        return $this->belongsTo(RaceCategory::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function isConfirmed(): bool
    {
        return $this->registration_status === RegistrationStatus::Confirmed;
    }

    /**
     * Nama peserta dengan nama akhirnya disingkat untuk halaman publik.
     */
    public function maskedName(): string
    {
        $parts = preg_split('/\s+/', trim($this->full_name));

        if (count($parts) < 2) {
            return $this->full_name;
        }

        return $parts[0].' '.strtoupper(substr($parts[count($parts) - 1], 0, 1)).'.';
    }
}
