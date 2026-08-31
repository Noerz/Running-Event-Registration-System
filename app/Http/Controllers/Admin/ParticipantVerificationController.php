<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectPaymentRequest;
use App\Models\Participant;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;

class ParticipantVerificationController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function approve(Participant $participant): RedirectResponse
    {
        $processed = $this->paymentService->approve($participant, request()->user());

        if (! $processed) {
            return back()->with(
                'info',
                "Pembayaran {$participant->registration_code} sudah diverifikasi sebelumnya."
            );
        }

        return back()->with(
            'success',
            "Pembayaran disetujui. BIB {$participant->refresh()->bib_number} diterbitkan dan email notifikasi dikirim ke peserta."
        );
    }

    public function reject(RejectPaymentRequest $request, Participant $participant): RedirectResponse
    {
        $processed = $this->paymentService->reject(
            $participant,
            trim((string) $request->validated('rejection_reason')),
            $request->user()
        );

        if (! $processed) {
            return back()->with('info', 'Status pembayaran sudah final dan tidak dapat diubah.');
        }

        return back()->with('success', 'Pembayaran ditolak. Alasan penolakan dapat dilihat peserta pada halaman cek status.');
    }
}
