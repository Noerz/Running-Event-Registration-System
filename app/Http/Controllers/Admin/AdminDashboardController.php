<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Models\Event;
use App\Models\Participant;
use App\Http\Controllers\Controller;

class AdminDashboardController extends Controller
{
    public function index(): \Illuminate\Contracts\View\View
    {
        $base = Participant::query();

        $stats = [
            'total' => (clone $base)->count(),
            'today' => (clone $base)->whereDate('created_at', today())->count(),
            'pending' => (clone $base)->where('registration_status', 'pending')->count(),
            'approved' => (clone $base)->where('registration_status', 'confirmed')->count(),
            'rejected' => (clone $base)->where('registration_status', 'rejected')->count(),
        ];

        $categories = Event::query()
            ->with(['raceCategories' => fn ($q) => $q->withCount([
                'participants as registered_count' => fn ($p) => $p->whereIn('registration_status', ['pending', 'confirmed']),
            ])->orderBy('distance')])
            ->orderByDesc('event_date')
            ->limit(3)
            ->get();

        $chartData = $categories
            ->flatMap(fn ($event) => $event->raceCategories)
            ->map(fn ($category) => [
                'label' => $category->code,
                'name' => $category->name,
                'total' => $category->registered_count,
            ]);

        $maxChart = max(1, $chartData->max('total') ?? 0);

        return view('admin.dashboard', [
            'stats' => $stats,
            'categories' => $categories,
            'chartData' => $chartData,
            'maxChart' => $maxChart,
        ]);
    }
}
