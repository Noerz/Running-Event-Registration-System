<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Event;
use App\Services\RegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class RegistrationController extends Controller
{
    public function __construct(private readonly RegistrationService $registrationService)
    {
    }

    public function create(): View|RedirectResponse
    {
        $event = Event::query()->openForRegistration()->first();

        if (! $event) {
            return redirect()
                ->route('home')
                ->with('error', 'Saat ini tidak ada event yang sedang membuka pendaftaran.');
        }

        $categories = $event->activeRaceCategories()->orderBy('distance')->get();

        return view('register.create', [
            'event' => $event,
            'categories' => $categories,
        ]);
    }

    public function store(StoreRegistrationRequest $request): RedirectResponse
    {
        try {
            $participant = $this->registrationService->register($request->validated());
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->withErrors(['race_category_id' => $e->getMessage()]);
        }

        return redirect()->to(URL::temporarySignedRoute(
            'registration.success',
            now()->addMinutes(30),
            ['registrationCode' => $participant->registration_code],
        ));
    }

    public function success(string $registrationCode): View
    {
        $participant = \App\Models\Participant::query()
            ->where('registration_code', $registrationCode)
            ->with(['raceCategory', 'payment'])
            ->firstOrFail();

        return view('registration.success', ['participant' => $participant]);
    }
}
