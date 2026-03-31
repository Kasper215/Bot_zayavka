<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['lead'])->orderBy('created_at', 'desc');

        // Optional filtering by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $payments = $query->paginate(20)->appends($request->all());

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['status']),
        ]);
    }

    public function getSettings()
    {
        $settings = json_decode(\Illuminate\Support\Facades\Storage::disk('local')->get('payment_settings.json'), true) ?? [
            'card_number' => '0000 0000 0000 0000',
            'phone_number' => '+7 (000) 000-00-00',
            'recipient_name' => 'Иван И. (Сбербанк/Тинькофф)'
        ];

        return response()->json($settings);
    }

    public function saveSettings(Request $request)
    {
        if ((int) $request->user()->role !== 3) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'card_number' => 'required|string',
            'phone_number' => 'required|string',
            'recipient_name' => 'required|string',
        ]);

        \Illuminate\Support\Facades\Storage::disk('local')->put('payment_settings.json', json_encode($validated));

        return redirect()->back()->with('success', 'Реквизиты успешно обновлены.');
    }

    public function updateStatus(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $payment->update([
            'status' => $validated['status'],
        ]);

        // If approved, update lead status
        if ($validated['status'] === 'approved') {
            $payment->lead->update(['status' => 'in_progress']); // Or any suitable status
        }

        return redirect()->back()->with('success', 'Статус оплаты обновлен.');
    }
}
