<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateParticipantRequest;
use App\Models\ActivityLog;
use App\Models\Participant;
use App\Models\RaceCategory;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminParticipantController extends Controller
{
    public function index(Request $request): View
    {
        $participants = Participant::query()
            ->with(['raceCategory', 'payment'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';

                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('phone', 'like', $search)
                        ->orWhere('registration_code', 'like', $search)
                        ->orWhere('bib_number', 'like', $search);
                });
            })
            ->when($request->filled('category'), fn ($query) => $query
                ->where('race_category_id', $request->integer('category')))
            ->when($request->filled('status'), fn ($query) => $query
                ->where('registration_status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $filterCategories = RaceCategory::query()->orderBy('distance')->get();

        return view('admin.participants.index', [
            'participants' => $participants,
            'filterCategories' => $filterCategories,
        ]);
    }

    public function show(Participant $participant): View
    {
        $participant->load(['event', 'raceCategory', 'payment.verifiedBy']);

        return view('admin.participants.show', ['participant' => $participant]);
    }

    public function edit(Participant $participant): View
    {
        $participant->load(['event', 'raceCategory']);

        return view('admin.participants.edit', [
            'participant' => $participant,
        ]);
    }

    public function update(UpdateParticipantRequest $request, Participant $participant): RedirectResponse
    {
        $data = $request->validated();
        $data['age'] = Carbon::parse($data['birth_date'])->age;

        $participant->update($data);

        ActivityLog::record(
            action: 'participant_updated',
            subject: $participant,
            adminId: $request->user()->getKey(),
            description: "Data peserta {$participant->registration_code} diperbarui oleh {$request->user()->name}.",
        );

        return redirect()
            ->route('admin.participants.show', $participant)
            ->with('success', 'Data peserta berhasil diperbarui.');
    }

    /**
     * Menampilkan/mengunduh bukti pembayaran dari storage privat (bukan public).
     */
    public function paymentProof(Request $request, Participant $participant): StreamedResponse
    {
        abort_unless($participant->payment?->payment_proof, 404);

        $storage = \Storage::disk(config('registration.payment_proof_disk'));

        if ($request->boolean('inline')) {
            return $storage->response($participant->payment->payment_proof);
        }

        return $storage->download($participant->payment->payment_proof);
    }
}
