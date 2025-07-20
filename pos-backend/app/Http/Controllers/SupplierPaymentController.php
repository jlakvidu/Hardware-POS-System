<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupplierPaymentGroup;
use App\Models\SupplierPaymentTransaction;
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
            'amount_paid' => 'required|numeric|min:0',
            'total_cost' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,check',
            'check_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($validated['payment_method'] === 'check' && empty($validated['check_number'])) {
            throw ValidationException::withMessages(['check_number' => 'Check number is required for check payments.']);
        }

        // Find or create the payment group
        $group = SupplierPaymentGroup::where([
            'supplier_id' => $validated['supplier_id'],
            'product_id' => $validated['product_id'],
            'inventory_id' => $validated['inventory_id'],
        ])->first();

        if (!$group) {
            // Use total_cost from request or calculate
            if (isset($validated['total_cost'])) {
                $total_cost = $validated['total_cost'];
            } else {
                $product = Product::find($validated['product_id']);
                $inventory = Inventory::find($validated['inventory_id']);
                $total_cost = 0;
                if ($product && $inventory) {
                    $total_cost = $product->price * $inventory->quantity;
                }
            }
            $group = SupplierPaymentGroup::create([
                'supplier_id' => $validated['supplier_id'],
                'product_id' => $validated['product_id'],
                'inventory_id' => $validated['inventory_id'],
                'admin_id' => $validated['admin_id'],
                'total_cost' => $total_cost,
                'remaining_balance' => $total_cost,
                'payment_status' => 'advance',
                'notes' => $validated['notes'] ?? null,
            ]);
        }

        // Calculate new remaining balance and status
        $amount_paid = $validated['amount_paid'];
        $new_remaining = max($group->remaining_balance - $amount_paid, 0);
        $new_status = $new_remaining > 0 ? 'advance' : 'full';

        // Create the payment transaction
        $transaction = SupplierPaymentTransaction::create([
            'group_id' => $group->id,
            'amount_paid' => $amount_paid,
            'payment_method' => $validated['payment_method'],
            'check_number' => $validated['payment_method'] === 'check' ? $validated['check_number'] : null,
            'bank_name' => $validated['payment_method'] === 'check' ? ($validated['bank_name'] ?? null) : null,
            'notes' => $validated['notes'] ?? null,
            'paid_at' => now(),
        ]);

        // Update group
        $group->remaining_balance = $new_remaining;
        $group->payment_status = $new_status;
        $group->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Supplier payment recorded successfully.',
            'data' => [
                'group' => $group,
                'transaction' => $transaction
            ]
        ]);
    }

    public function index(Request $request)
    {
        // Optionally filter by supplier/product/inventory
        $query = SupplierPaymentGroup::with(['transactions', 'supplier', 'product', 'inventory', 'admin']);

        if ($request->supplier_id) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->inventory_id) {
            $query->where('inventory_id', $request->inventory_id);
        }

        $groups = $query->orderByDesc('updated_at')->get();

        return response()->json($groups);
    }

    public function update(Request $request, $id)
    {
        $group = SupplierPaymentGroup::findOrFail($id);

        $validated = $request->validate([
            'total_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
            'payment_status' => 'required|in:advance,full',
        ]);

        // Only update allowed fields
        if (isset($validated['total_cost'])) {
            $group->total_cost = $validated['total_cost'];
        }
        if (isset($validated['notes'])) {
            $group->notes = $validated['notes'];
        }
        if (isset($validated['payment_status'])) {
            $group->payment_status = $validated['payment_status'];
        }

        $group->save();

        // Optionally, recalculate remaining_balance if total_cost changed
        if (isset($validated['total_cost'])) {
            $paid = $group->transactions()->sum('amount_paid');
            $group->remaining_balance = max($group->total_cost - $paid, 0);
            // If remaining_balance is 0, set status to full
            if ($group->remaining_balance == 0) {
                $group->payment_status = 'full';
            }
            $group->save();
        }

        // Return updated group with relations
        $group->load(['transactions', 'supplier', 'product', 'inventory', 'admin']);

        return response()->json($group);
    }

    public function destroy($id)
    {
        $group = SupplierPaymentGroup::findOrFail($id);

        // Delete all transactions first
        $group->transactions()->delete();

        // Delete the group
        $group->delete();

        return response()->json(['status' => 'success', 'message' => 'Supplier payment group deleted.']);
    }
}
