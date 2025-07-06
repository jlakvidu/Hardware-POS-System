<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\SupplierProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Models\Inventory;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $products = Product::with('inventory', 'admin')->get()->map(function ($product) {
                $product->calculate_length = $product->calculate_length; 
                $product->size = $product->size;
                return $product;
            });
            return $this->successResponse('Product retrieved successfully', $products);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    public function show(string $id): JsonResponse
    {
        try {
            $product = Product::with('inventory')->findOrFail($id);
            $product->quantity = $product->calculate_length && $product->inventory
                ? $product->size * $product->inventory->quantity
                : $product->inventory->quantity; 
            return $this->successResponse('Product retrieved successfully', $product);
        } catch (Exception $e) {
            return $this->errorResponse('Product not found', 404);
        }
    }
    public function store(Request $request): JsonResponse
    {
        Log::info('Incoming Request:', $request->all());

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'supplier_id' => 'required|exists:suppliers,id',
                'seller_price' => 'required|numeric|min:0',
                'discount' => 'required|numeric|min:0', 
                'selling_discount' => 'sometimes|numeric|min:0', 
                'price' => 'required|numeric|min:0',
                'brand_name' => 'required|string|max:255',
                'tax' => 'required|numeric|min:0',
                'size' => 'required|string',
                'color' => 'required|string',
                'description' => 'required|string',
                'bar_code' => 'unique:products',
                'inventory_id' => 'required|exists:inventories,id',
                'admin_id' => 'required|exists:admins,id',
                'calculate_length' => 'sometimes|boolean', 
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $inventory = Inventory::find($request->inventory_id);
            if (!$inventory) {
                return $this->errorResponse('Invalid inventory ID. Please enter a valid inventory ID.', 404);
            }

            if (Product::where('bar_code', $request->bar_code)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A product with this bar code already exists'
                ], 409);
            }

            DB::beginTransaction();

            $profit = $request->price - $request->seller_price;

            $product = Product::create([
                'name' => $request->name,
                'price' => $request->price,
                'seller_price' => $request->seller_price,
                'profit' => $profit,
                'discount' => $request->discount ?? 0, 
                'selling_discount' => $request->selling_discount ?? 0, 
                'tax' => $request->tax,
                'size' => $request->size,
                'color' => $request->color,
                'description' => $request->description,
                'bar_code' => $request->bar_code,
                'brand_name' => $request->brand_name,
                'inventory_id' => $request->inventory_id,
                'supplier_id' => $request->supplier_id,
                'admin_id' => $request->admin_id,
                'calculate_length' => $request->calculate_length ?? false, 
            ]);

            SupplierProduct::create([
                'product_id' => $product->id,
                'supplier_id' => $request->supplier_id
            ]);

            DB::commit();

            $product->load('inventory');

            return $this->successResponse('Product created successfully', $product, 201);

        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'supplier' => 'sometimes|string|max:255',
                'price' => 'sometimes|numeric|min:0',
                'seller_price' => 'sometimes|numeric|min:0',
                'discount' => 'sometimes|numeric|min:0',
                'selling_discount' => 'sometimes|numeric|min:0',
                'brand_name' => 'sometimes|string|max:255',
                'tax' => 'sometimes|numeric|min:0',
                'size' => 'sometimes|string',
                'color' => 'sometimes|string',
                'description' => 'sometimes|string',
                'bar_code' => 'sometimes|string|unique:products,bar_code,' . $product->id,
                'status' => 'sometimes|in:In Stock,Out Of Stock',
                'inventory_id' => 'sometimes|exists:inventories,id',
                'admin_id' => 'sometimes|exists:admins,id',
                'calculate_length' => 'sometimes|boolean', 
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $profit = $request->price - $request->seller_price;
            $product->update($validator->validated() + [
                'profit' => $profit,
                'discount' => $request->discount ?? $product->discount, 
                'selling_discount' => $request->selling_discount ?? $product->selling_discount, 
            ]);

            return $this->successResponse('Product updated successfully', $product);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update product', 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $product = Product::find($id);
            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }
            $product->delete();
            return $this->successResponse('Product deleted successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to delete product', 500);
        }
    }

    public function getByInventoryId($inventoryId)
    {
        try {
            $product = Product::with(['supplier' => function($query) {
                $query->select('id', 'name', 'email', 'contact'); 
            }])
            ->where('inventory_id', $inventoryId)
            ->firstOrFail();

            $formattedProduct = array_merge($product->toArray(), [
                'supplierDetails' => $product->supplier ? [
                    'name' => $product->supplier->name,
                    'email' => $product->supplier->email,
                    'contact' => $product->supplier->contact
                ] : null
            ]);

            return response()->json($formattedProduct);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'No product found for this inventory ID'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStock(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|numeric|min:0.1', 
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            DB::transaction(function () use ($request) {
                $product = Product::findOrFail($request->product_id);
                $inventory = $product->inventory;

                if (!$inventory || $inventory->quantity < $request->quantity) {
                    throw new Exception('Insufficient stock');
                }

                $inventory->lockForUpdate();

                Log::info("Updating stock for product ID {$request->product_id}. Deducting quantity: {$request->quantity}");

                $inventory->quantity = round($inventory->quantity - $request->quantity, 2); 
                $inventory->save();
            });

            return $this->successResponse('Stock updated successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update stock: ' . $e->getMessage(), 500);
        }
    }

    private function successResponse(string $message, mixed $data = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    private function errorResponse(mixed $error, int $code): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $error,
        ], $code);
    }
}
