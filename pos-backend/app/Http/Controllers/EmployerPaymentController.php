<?php

namespace App\Http\Controllers;

use App\Models\EmployerPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployerPaymentController extends Controller
{
    public function index()
    {
        try {
            $payments = EmployerPayment::with(['cashier:id,name,email,contact_number'])
                ->orderBy('payment_date', 'desc')
                ->get();

            // Return simple response first for debugging
            return response()->json([
                'status' => 'success',
                'data' => $payments
            ]);

        } catch (\Exception $e) {
            Log::error('Employer Payments Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch employer payments',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'cashier_id' => 'required|exists:cashier,id',
                'salary_amount' => 'required|numeric|min:0',
                'payment_duration' => 'required|in:Daily,Weekly,Monthly,Other', // <-- add this
                'payment_date' => 'required|date',
                'payment_method' => 'required|in:cash,bank_transfer,check',
                'notes' => 'nullable|string'
            ]);

            $payment = EmployerPayment::create($validated);

            // Load the cashier relationship with specific fields
            $payment->load('cashier:id,name,email,contact_number');

            return response()->json([
                'status' => 'success',
                'data' => $payment
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Employer Payment Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create employer payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $payment = EmployerPayment::with('cashier:id,name,email,contact_number')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $payment
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch employer payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $payment = EmployerPayment::findOrFail($id);

            $validated = $request->validate([
                'cashier_id' => 'required|exists:cashier,id',
                'salary_amount' => 'required|numeric|min:0',
                'payment_duration' => 'required|in:Daily,Weekly,Monthly,Other', // <-- add this
                'payment_date' => 'required|date',
                'payment_method' => 'required|in:cash,bank_transfer,check',
                'notes' => 'nullable|string'
            ]);

            $payment->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $payment->load('cashier:id,name,email,contact_number')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update employer payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $payment = EmployerPayment::findOrFail($id);
            $payment->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Payment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete employer payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
