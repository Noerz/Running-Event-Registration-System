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
                ->withCount(['raceCategories'])
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
            ->with('success', "Event \"{$event->name}\" berhasil dibuat.");
    }

    public function edit(Event $event): View
    {
        return view('admin.events.edit', ['event' => $event]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->validated());

        return redirect()
            ->route('admin.events.index')
            ->with('success', "Event \"{$event->name}\" berhasil diperbarui.");
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
