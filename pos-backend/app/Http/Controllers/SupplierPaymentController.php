<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupplierPayment;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Validation\ValidationException;
use Exception;

class SupplierPaymentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'product_id' => 'required|exists:products,id',
            'inventory_id' => 'required|exists:inventories,id',
            'admin_id' => 'required|exists:admins,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,check',
            'check_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validated['payment_method'] === 'check' && empty($validated['check_number'])) {
            throw ValidationException::withMessages(['check_number' => 'Check number is required for check payments.']);
        }

        $payment = SupplierPayment::create([
            ...$validated,
            'paid_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Supplier payment recorded successfully.',
            'data' => $payment
        ]);
    }

    public function index()
    {
        $payments = SupplierPayment::with(['supplier', 'product', 'inventory', 'admin'])->orderByDesc('paid_at')->get();
        return response()->json($payments);
    }
}
