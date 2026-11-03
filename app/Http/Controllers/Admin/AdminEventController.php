<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminEventController extends Controller
{
    public function index(): View
    {
        return view('admin.events.index', [
            'events' => Event::query()
                ->withCount([
                    'raceCategories',
                    'raceCategories as active_race_categories_count' => fn ($q) => $q->where('status', 'active'),
                ])
                ->orderByDesc('event_date')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.events.create');
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = Event::create($request->validated());

        return redirect()
            ->route('admin.events.index')
            ->with('success', "Event \"{$event->name}\" berhasil dibuat. Silakan tambahkan kategori perlombaan untuk event ini.");
    }

    public function edit(Event $event): View
    {
        $event->load([
            'raceCategories' => fn ($q) => $q->withCount([
                'participants as registered_count' => fn ($p) => $p->whereIn('registration_status', ['pending', 'confirmed']),
            ])->orderBy('distance'),
        ]);

        return view('admin.events.edit', ['event' => $event]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->validated());

        $warning = $event->status === \App\Enums\EventStatus::Open && $event->activeRaceCategories()->count() === 0
            ? ' Perhatian: Event dibuka tetapi belum memiliki kategori aktif.'
            : '';

        return redirect()
            ->route('admin.events.index')
            ->with('success', "Event \"{$event->name}\" berhasil diperbarui.{$warning}");
    }

    public function destroy(Event $event): RedirectResponse
    {
        if ($event->participants()->exists()) {
            return back()->with(
                'error',
                'Event tidak dapat dihapus karena masih memiliki data peserta. Nonaktifkan event melalui status "Ditutup".'
            );
        }

        $name = $event->name;
        $event->delete();

        return redirect()
            ->route('admin.events.index')
            ->with('success', "Event \"{$name}\" berhasil dihapus.");
    }
}
