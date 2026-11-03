<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateParticipantRequest;
use App\Models\ActivityLog;
use App\Models\Participant;
use App\Models\RaceCategory;
use App\Services\ObjectStorage\Exceptions\FileNotFoundException;
use App\Services\PaymentProof\PaymentProofService;
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

        $filterCategories = RaceCategory::query()->with('event')->orderBy('event_id')->orderBy('distance')->get();

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
    public function paymentProof(Request $request, Participant $participant, PaymentProofService $proofs): StreamedResponse
    {
        abort_unless($participant->payment?->payment_proof, 404);

        $path = $participant->payment->payment_proof;

        try {
            return $request->boolean('inline')
                ? $proofs->response($path)
                : $proofs->download($path);
        } catch (FileNotFoundException) {
            abort(404, 'File bukti pembayaran tidak ditemukan pada storage.');
        }
    }

    public function destroy(Request $request, Participant $participant, PaymentProofService $proofs): RedirectResponse
    {
        $code = $participant->registration_code;
        $name = $participant->full_name;

        if ($participant->payment) {
            $proofs->delete($participant->payment);
        }

        $participant->delete();

        ActivityLog::record(
            action: 'participant_deleted',
            subject: null,
            adminId: $request->user()?->getKey(),
            description: "Data peserta {$code} ({$name}) dihapus oleh {$request->user()?->name}.",
        );

        return redirect()
            ->route('admin.participants.index')
            ->with('success', "Peserta {$name} ({$code}) berhasil dihapus.");
    }
}
