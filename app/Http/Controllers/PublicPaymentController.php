<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PublicPaymentController extends Controller
{
    public function submitPayment(Request $request)
    {
        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'method' => 'required|string|in:card,phone',
            'screenshot' => 'required|image|max:10240',
        ]);

        try {
            $lead = Lead::findOrFail($validated['lead_id']);
            
            $priceStr = $lead->calc_price ?? '';
            $leftPart = explode('до', $priceStr)[0];
            $numericPrice = (float) preg_replace('/[^0-9]/', '', $leftPart);
            $paymentAmount = $numericPrice * 0.5;

            // Handle screenshot
            if ($request->hasFile('screenshot')) {
                $file = $request->file('screenshot');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs("payments/{$lead->id}", $filename, 'public');

                $payment = Payment::create([
                    'lead_id' => $lead->id,
                    'amount' => $paymentAmount,
                    'method' => $validated['method'],
                    'screenshot_path' => $path,
                    'status' => 'pending',
                ]);

                // Update lead status if needed
                $lead->update(['status' => 'awaiting_confirmation']);

                try {
                    // Send notification to admins about the lead/payment
                    $staff = \App\Models\User::whereIn('role', [1, 2, 3])->get();
                    \Illuminate\Support\Facades\Notification::send($staff, new \App\Notifications\NewLeadNotification($lead));
                } catch (\Exception $e) {
                    Log::error("Notification Error: " . $e->getMessage());
                }

                return response()->json([
                    'status' => 'ok',
                    'message' => 'Оплата успешно отправлена на проверку.',
                    'payment_id' => $payment->id
                ]);
            }

            return response()->json(['message' => 'Файл не найден'], 400);

        } catch (\Exception $e) {
            Log::error("Payment submission error: " . $e->getMessage());
            return response()->json(['message' => 'Ошибка при сохранении оплаты'], 500);
        }
    }

    public function paymentStatus($id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['status' => 'not_found'], 404);
        }
        return response()->json(['status' => $payment->status]);
    }
}
