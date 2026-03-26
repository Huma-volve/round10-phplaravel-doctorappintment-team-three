<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function update(Request $request, Payment $payment)
    {
        $payment->loadMissing('appointment.doctor', 'appointment.patient');

        $user = $request->user();

        $isDoctor = $user->isDoctor()
            && $user->doctor
            && (int) $user->doctor->id === (int) $payment->appointment->doctor_id;

        $isAdmin = $user->isAdmin();

        if (! $isDoctor && ! $isAdmin) {
            return ApiResponse::error('Forbidden.', 403);
        }

        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        $payment->fill([
            'payment_status' => $validated['payment_status'],
            'transaction_id' => $validated['transaction_id'] ?? $payment->transaction_id ?? (string) Str::uuid(),
        ]);
        $payment->save();

        return ApiResponse::success($payment->fresh(['appointment']));
    }
}
