<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductReturn;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Validation\ValidationException;
use Exception;

class ProductReturnController extends Controller
{
    public function index()
    {
        $returns = ProductReturn::with(['product', 'inventory'])->orderBy('return_date', 'desc')->get();
        return response()->json($returns);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'inventory_id' => 'required|exists:inventories,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'returned_by' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
        ]);

        try {
            $inventory = Inventory::findOrFail($validated['inventory_id']);
            $product = \App\Models\Product::findOrFail($validated['product_id']);
            $supplier_id = $validated['supplier_id'] ?? $product->supplier_id;

            if ($inventory->quantity < $validated['quantity']) {
                return response()->json(['error' => 'Not enough stock to return this quantity'], 400);
            }
            $inventory->quantity -= $validated['quantity'];
            // Set status to "Out Of Stock" if quantity is zero
            if ($inventory->quantity == 0) {
                $inventory->status = 'Out Of Stock';
            } elseif ($inventory->quantity < 20) {
                $inventory->status = 'Low Stock';
            } else {
                $inventory->status = 'In Stock';
            }
            $inventory->save();

            $productReturn = ProductReturn::create([
                'product_id' => $validated['product_id'],
                'inventory_id' => $validated['inventory_id'],
                'supplier_id' => $supplier_id,
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? null,
                'returned_by' => $validated['returned_by'] ?? null,
                'return_date' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $productReturn
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to process return', 'message' => $e->getMessage()], 500);
        }
    }
}
