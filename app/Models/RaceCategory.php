<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaceCategory extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'code',
        'bib_format',
        'bib_digits',
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
            'bib_digits' => 'integer',
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

    public function formatBibNumber(int $sequence = 1): string
    {
        $format = $this->bib_format ?: '{code}-{number}';
        $digits = max(3, min(8, (int) ($this->bib_digits ?: 4)));
        $num = str_pad((string) $sequence, $digits, '0', STR_PAD_LEFT);
        $year = $this->event?->event_date?->format('y') ?? date('y');

        return strtr($format, [
            '{code}' => $this->code,
            '{number}' => $num,
            '{year}' => $year,
        ]);
    }

    /**
     * Menghitung urutan nomor BIB terkecil yang belum terpakai (mendukung reuse nomor
     * peserta yang telah dihapus).
     */
    public function nextBibSequence(): int
    {
        $usedBibs = Participant::query()
            ->where('event_id', $this->event_id)
            ->whereNotNull('bib_number')
            ->pluck('bib_number')
            ->flip()
            ->all();

        $sequence = 1;
        while (isset($usedBibs[$this->formatBibNumber($sequence)])) {
            $sequence++;
        }

        return $sequence;
    }

    /**
     * Mencadangkan satu nomor BIB berikutnya untuk kategori ini secara aman
     * terhadap race condition dengan row locking. Mengisi celah urutan nomor BIB
     * yang kosong bila ada peserta yang telah dihapus.
     *
     * @return array{bib_number: string, sequence: int}
     */
    public function reserveBibNumber(): array
    {
        $category = static::query()
            ->with('event')
            ->whereKey($this->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $sequence = $category->nextBibSequence();

        $category->forceFill(['bib_counter' => $sequence])->save();

        return [
            // BIB unik dalam satu event (constraint unique di level event_id).
            'bib_number' => $category->formatBibNumber($sequence),
            'sequence' => $sequence,
        ];
    }
}
