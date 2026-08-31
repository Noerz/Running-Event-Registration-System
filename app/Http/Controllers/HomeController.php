<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\RaceCategory;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $event = Event::query()->openForRegistration()->first()
            ?? Event::query()->whereIn('status', ['open', 'closed', 'completed'])->orderByDesc('event_date')->first();

        $categories = $event?->activeRaceCategories()->orderBy('distance')->get() ?? collect();

        return view('home', [
            'event' => $event,
            'categories' => $categories,
            'isRegistrationOpen' => $event?->isRegistrationOpen() ?? false,
        ]);
    }
}
