<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class RaceCategory extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'code',
        'distance',
        'price',
        'quota',
        'bib_counter',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'distance' => 'decimal:2',
            'price' => 'integer',
            'quota' => 'integer',
            'bib_counter' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * Jumlah slot yang sudah terpakai (pendaftaran pending & confirmed).
     */
    public function usedQuota(): int
    {
        return $this->participants()
            ->whereIn('registration_status', ['pending', 'confirmed'])
            ->count();
    }

    public function remainingQuota(): int
    {
        return max(0, $this->quota - $this->usedQuota());
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function formattedPrice(): string
    {
        if ($this->price == 0) {
            return 'Gratis';
        }

        return 'Rp '.number_format($this->price, 0, ',', '.');
    }

    public function formattedDistance(): string
    {
        $distance = (float) $this->distance;

        return ($distance == floor($distance) ? floor($distance) : $distance).'K';
    }

    /**
     * Mencadangkan satu nomor BIB berikutnya untuk kategori ini secara aman
     * terhadap race condition dengan row locking.
     *
     * @return array{bib_number: string, sequence: int}
     */
    public function reserveBibNumber(): array
    {
        $category = static::query()
            ->whereKey($this->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $sequence = DB::table((new static)->getTable())
            ->where('id', $category->getKey())
            ->value('bib_counter') + 1;

        $category->forceFill(['bib_counter' => $sequence])->save();

        return [
            // BIB unik dalam satu event (constraint unique di level event_id).
            'bib_number' => sprintf('%s-%04d', $category->code, $sequence),
            'sequence' => $sequence,
        ];
    }
}
