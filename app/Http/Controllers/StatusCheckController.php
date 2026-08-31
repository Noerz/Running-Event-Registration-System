<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckRegistrationRequest;
use App\Models\Participant;
use Illuminate\Contracts\View\View;

class StatusCheckController extends Controller
{
    public function show(): View
    {
        return view('check.index', ['participant' => null]);
    }

    public function check(CheckRegistrationRequest $request): View
    {
        $identifier = $request->validated('identifier');

        $participants = Participant::query()
            ->where(function ($query) use ($identifier) {
                $query->where('email', strtolower($identifier))
                    ->orWhere('phone', $identifier);
            })
            ->with(['raceCategory', 'payment'])
            ->latest()
            ->get();

        return view('check.index', [
            'searched' => true,
            'participants' => $participants,
            'identifier' => $identifier,
        ]);
    }
}
