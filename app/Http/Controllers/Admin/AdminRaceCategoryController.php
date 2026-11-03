<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRaceCategoryRequest;
use App\Http\Requests\UpdateRaceCategoryRequest;
use App\Models\Event;
use App\Models\RaceCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminRaceCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $eventId = $request->filled('event_id') ? $request->integer('event_id') : null;

        return view('admin.race-categories.index', [
            'categories' => RaceCategory::query()
                ->with(['event'])
                ->withCount([
                    'participants as registered_count' => fn ($q) => $q->whereIn('registration_status', ['pending', 'confirmed']),
                ])
                ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
                ->orderBy('event_id')
                ->orderBy('distance')
                ->paginate(15)
                ->withQueryString(),
            'events' => Event::query()->orderByDesc('event_date')->get(),
            'selectedEventId' => $eventId,
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.race-categories.create', [
            'events' => Event::query()->orderByDesc('event_date')->get(),
            'selectedEventId' => $request->filled('event_id') ? $request->integer('event_id') : null,
        ]);
    }

    public function store(StoreRaceCategoryRequest $request): RedirectResponse
    {
        $category = RaceCategory::create($request->validated());

        return redirect()
            ->route('admin.race-categories.index')
            ->with('success', "Kategori \"{$category->name}\" berhasil dibuat.");
    }

    public function edit(RaceCategory $raceCategory): View
    {
        return view('admin.race-categories.edit', [
            'category' => $raceCategory,
            'events' => Event::query()->orderByDesc('event_date')->get(),
        ]);
    }

    public function update(UpdateRaceCategoryRequest $request, RaceCategory $raceCategory): RedirectResponse
    {
        $raceCategory->update($request->validated());

        return redirect()
            ->route('admin.race-categories.index')
            ->with('success', "Kategori \"{$raceCategory->name}\" berhasil diperbarui.");
    }

    public function destroy(RaceCategory $raceCategory): RedirectResponse
    {
        if ($raceCategory->participants()->exists()) {
            return back()->with(
                'error',
                'Kategori tidak dapat dihapus karena masih memiliki peserta terdaftar.'
            );
        }

        $name = $raceCategory->name;
        $raceCategory->delete();

        return redirect()
            ->route('admin.race-categories.index')
            ->with('success', "Kategori \"{$name}\" berhasil dihapus.");
    }
}
