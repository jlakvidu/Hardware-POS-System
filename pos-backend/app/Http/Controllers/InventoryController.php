<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\SupplierProduct;
use Illuminate\Validation\ValidationException;
use Exception;
use Throwable;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index()
    {
        $inventory = Inventory::with(['product.supplier'])->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'status' => $item->status,
                'restock_date_time' => $item->restock_date_time,
                'added_stock_amount' => $item->added_stock_amount,
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'model' => $item->product->model,
                    'price' => $item->product->price,
                    'supplier_id' => $item->product->supplier_id,
                    'inventory_id' => $item->product->inventory_id,
                    'image_url' => $item->product->image_url ?? null, // Add image if exists
                    'supplier' => $item->product->supplier ? [
                        'id' => $item->product->supplier->id,
                        'name' => $item->product->supplier->name,
                        'email' => $item->product->supplier->email,
                        'contact' => $item->product->supplier->contact,
                    ] : null,
                ] : null,
            ];
        });

        return response()->json($inventory);
    }
    public function store(Request $request)
    {
        try {
            $request->validate([
                'quantity' => 'required|numeric',
                'status' => 'required|string|in:In Stock,Low Stock,Out Of Stock',
                'restock_date_time' => 'nullable|date',
                'added_stock_amount' => 'nullable|numeric',
            ]);

            $inventory = new Inventory();
            $inventory->quantity = $request->input('quantity');
            $inventory->restock_date_time = $request->input('restock_date_time') 
                ? date('Y-m-d H:i:s', strtotime($request->input('restock_date_time')))
                : now();
            $inventory->added_stock_amount = $request->input('added_stock_amount', $inventory->quantity);
            $inventory->status = $request->input('status');
            $inventory->save();
            $this->updateStatus();
            return response()->json($inventory);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'messages' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create inventory', 'message' => $e->getMessage()], 500);
        }
    }
    public function show($id)
    {
        try {
            $inventory = Inventory::findOrFail($id);
            return response()->json($inventory);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Inventory not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve inventory', 'message' => $e->getMessage()], 500);
        }
    }
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $inventory = Inventory::findOrFail($id);

            $validated = $request->validate([
                'quantity' => 'required|numeric|min:0',
                'status' => 'required|string|in:In Stock,Low Stock,Out Of Stock',
                'added_stock_amount' => 'numeric|min:0',
                'restock_date_time' => 'required|date',
            ]);

            $inventory->quantity = $validated['quantity'];
            $inventory->status = $validated['status'];

            if (!empty($validated['added_stock_amount']) && $validated['added_stock_amount'] > 0) {
                $inventory->restock_date_time = $validated['restock_date_time'];
                $inventory->added_stock_amount = $validated['added_stock_amount'];
            }

            $inventory->save();

            $quantity = $inventory->product && $inventory->product->calculate_length
            ? $inventory->quantity * (float) $inventory->product->size
                : $inventory->quantity;

            DB::commit();
            return response()->json([
                'id' => $inventory->id,
                'quantity' => $quantity,
                'status' => $inventory->status,
                'added_stock_amount' => $inventory->added_stock_amount,
                'restock_date_time' => $inventory->restock_date_time,
                'created_at' => $inventory->created_at,
                'updated_at' => $inventory->updated_at,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Inventory not found'], 404);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update inventory: ' . $e->getMessage()], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $inventory = Inventory::findOrFail($id);
            $inventory->delete();
            return response()->json(['message' => 'Inventory deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Inventory not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete inventory', 'message' => $e->getMessage()], 500);
        }
    }

    public function lowStock()
    {
        try {
            $supplierList = collect();
            $inventories = Inventory::where('quantity', '<', 20)->get();
            $inventoryData = $inventories->keyBy('id');
            $products = Product::whereIn('inventory_id', $inventories->pluck('id'))->get()->keyBy('id');
            if ($products->isNotEmpty()) {
                $supplierProduct = SupplierProduct::whereIn('product_id', $products->keys())
                    ->with('supplier', 'product')
                    ->get();
                foreach ($supplierProduct as $item) {
                    $inventory = $inventoryData->get($item->product->inventory_id);
                    $lowStockDetail = new LowStockDetail(
                        $item->supplier->id,
                        $item->product->id,
                        $item->supplier->name,
                        $item->supplier->email,
                        $item->product->name,
                        $inventory->quantity,
                        $inventory->status
                    );
                    $supplierList->push($lowStockDetail);
                }
            }
            return response()->json($supplierList);
        } catch (Exception | Throwable $e) {
            return response()->json([
                'error' => 'Failed to retrieve low stock inventory',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function outOfStock()
    {
        try {
            $inventories = Inventory::where('quantity', 0)->get();
            return response()->json($inventories);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve out of stock inventory', 'message' => $e->getMessage()], 500);
        }
    }

    public function inStock()
    {
        try {
            $inventories = Inventory::where('quantity', '>', 0)->get();
            return response()->json($inventories);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve in stock inventory', 'message' => $e->getMessage()], 500);
        }
    }
    public function updateStatus(){
        Inventory::chunk(100, function ($inventories) {
            foreach ($inventories as $inventory) {
                if ($inventory->quantity == 0) {
                    $inventory->status = 'Out Of Stock';
                } elseif ($inventory->quantity < 5) {
                    $inventory->status = 'Low Stock';
                } else {
                    $inventory->status = 'In Stock';
                }
                $inventory->save();
            }
        });
    }
    private function determineStatus($quantity)
    {
        if ($quantity == 0) {
            return 'Out Of Stock';
        } elseif ($quantity < 20) {
            return 'Low Stock';
        }
        return 'In Stock';
    }

    public function exportData()
    {
        try {
            $inventories = Inventory::with(['product' => function($query) {
                $query->select(
                    'id',
                    'inventory_id',
                    'name',
                    'model',
                    'price',
                    'supplier_id'
                );
            }])->get();

            $totalValue = 0;

            $formattedData = $inventories->map(function ($inventory) use (&$totalValue) {
                $status = $this->determineStatus($inventory->quantity);

                $product = $inventory->product;

                if ($product) {
                    $itemValue = ($product->price ?? 0) * $inventory->quantity;
                    $totalValue += $itemValue;
                } else {
                    $itemValue = 0;
                }

                return [
                    'id' => $inventory->id,
                    'product' => $product ? [
                        'id' => $product->id,
                        'name' => $product->name,
                        'model' => $product->model,
                        'price' => $product->price,
                        'supplier_id' => $product->supplier_id,
                        'inventory_id' => $product->inventory_id,
                    ] : null,
                    'quantity' => $inventory->quantity,
                    'status' => $status,
                    'added_stock_amount' => $inventory->added_stock_amount,
                    'restock_date_time' => $inventory->restock_date_time,
                    'created_at' => $inventory->created_at,
                    'updated_at' => $inventory->updated_at,
                    'total_value' => $itemValue
                ];
            });

            \Log::info('Export Data Summary', [
                'total_value' => $totalValue
            ]);

            return response()->json([
                'data' => $formattedData,
                'summary' => [
                    'total_value' => $totalValue
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to export inventory data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
class LowStockDetail
{
    public $supplier_id;
    public $product_id;
    public $supplier_name;
    public $supplier_email;
    public $product_name;
    public $quantity;
    public $status;
    public function __construct($supplier_id, $product_id, $supplier_name, $supplier_email, $product_name, $quantity, $status)
    {
        $this->supplier_id = $supplier_id;
        $this->product_id = $product_id;
        $this->supplier_name = $supplier_name;
        $this->supplier_email = $supplier_email;
        $this->product_name = $product_name;
        $this->quantity = $quantity;
        $this->status = $status;
    }
    
}