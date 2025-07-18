<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Product_Sales;
use App\Models\Promotion;
use App\Models\ReturnItem;
use App\Models\Sales;
use App\Models\SalesReturnItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index()
    {
        $sales = Sales::with(['product_sales.product', 'customer'])->get();
        return response()->json([
            'status' => 'success',
            'data' => $sales
        ]);
    }

    public function show($id)
    {
        $sales = Sales::with(['product_sales.product'])->find($id);
        if (!$sales) {
            return response()->json([
                'error' => 'Sales record not found',
                'id' => $id
            ], 404);
        }
        return response()->json([
            'id' => $id,
            'data' => $sales
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'cashier_id' => 'required',
                'payment_type' => 'required|in:CASH,CARD,CHECK,ONLINE',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.product_discount' => 'nullable|numeric|min:0|max:100',
                'cart_discount' => 'nullable|numeric|min:0|max:100',
                'advance_amount' => 'nullable|numeric|min:0',
                'amount' => 'required|numeric|min:0',
                'remaining_balance' => 'nullable|numeric|min:0',
                'payment_status' => 'required|string',
                'customer_id' => 'nullable|exists:customers,id',
                // Only require check_details fields if payment_type is CHECK
                'check_details' => 'nullable|array',
                'check_details.bankName' => 'required_if:payment_type,CHECK',
                'check_details.checkNumber' => 'required_if:payment_type,CHECK',
                'check_details.checkDate' => 'required_if:payment_type,CHECK|date',
                'check_details.amount' => 'required_if:payment_type,CHECK|numeric|min:0',
                'check_details.remarks' => 'nullable|string',
                'items.*.width' => 'nullable|numeric|min:0',
                'items.*.height' => 'nullable|numeric|min:0',
                'items.*.totalAreaMeters' => 'nullable|numeric|min:0',
                'items.*.specialNote' => 'nullable|string|max:255', // <-- Add this line
            ]);

            $total = 0;
            $productDiscountsTotal = 0;
            foreach ($request->get('items') as $item) {
                $itemSubtotal = $item['price'] * $item['quantity'];
                $itemDiscountAmount = ($itemSubtotal * ($item['product_discount'] ?? 0)) / 100;
                $productDiscountsTotal += $itemDiscountAmount;
                $total += $itemSubtotal - $itemDiscountAmount;
            }

            $cartDiscount = $request->get('cart_discount', 0);
            $cartDiscountAmount = ($total * $cartDiscount) / 100;
            $finalTotal = $total - $cartDiscountAmount;

            $advanceAmount = $request->get('advance_amount', $finalTotal);
            $remainingBalance = $request->get('remaining_balance', $finalTotal - $advanceAmount);

            $paymentStatus = $request->get('payment_status', 'FULL_PAYMENT');
            $status = $paymentStatus === 'FULL_PAYMENT' || $paymentStatus === 'PAID' ? 1 : 2;

            $checkDetails = null;
            if ($request->payment_type === 'CHECK' && $request->has('check_details')) {
                $checkDetails = json_encode($request->check_details);
            }

            $salesRecord = [
                'customer_id' => $request->get('customer_id'),
                'cashier_id' => $request->get('cashier_id'),
                'payment_type' => $request->payment_type,
                'time' => now(),
                'status' => $status,
                'amount' => $finalTotal,
                'advance_amount' => $advanceAmount,
                'remaining_balance' => $remainingBalance,
                'payment_status' => $paymentStatus,
                'cart_discount' => $cartDiscount,
                'product_discounts_total' => $productDiscountsTotal,
                'total_discount_amount' => $productDiscountsTotal + $cartDiscountAmount,
                'check_details' => $checkDetails,
            ];

            DB::beginTransaction();
            try {
                $sales = Sales::create($salesRecord);

                $productIds = collect($request->get('items'))->pluck('product_id')->unique();
                $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

                foreach ($request->get('items') as $item) {
                    $product = $products->get($item['product_id']);
                    if (!$product) {
                        throw new \Exception("Product with ID {$item['product_id']} not found.");
                    }
                    $inventory = $product->inventory;
                    if (!$inventory) {
                        throw new \Exception("Inventory for Product with ID {$item['product_id']} not found.");
                    }
                    if ($inventory->quantity < $item['quantity']) {
                        throw new \Exception('Not enough stock for product ID ' . $item['product_id']);
                    }

                    // Map frontend fields to DB columns
                    $widthInch = isset($item['width']) ? $item['width'] : null;
                    $heightInch = isset($item['height']) ? $item['height'] : null;
                    $areaSqm = isset($item['totalAreaMeters']) ? $item['totalAreaMeters'] : null;
                    if ($areaSqm === null && $widthInch !== null && $heightInch !== null) {
                        $areaSqm = round($widthInch * $heightInch * 0.00064516, 4);
                    }

                    Product_Sales::create([
                        'sales_id' => $sales->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'width_inch' => $widthInch,
                        'height_inch' => $heightInch,
                        'area_sqm' => $areaSqm,
                        'special_note' => $item['specialNote'] ?? null, // <-- Add this line
                    ]);
                    $inventory->quantity = round($inventory->quantity - $item['quantity'], 2);
                    $inventory->save();
                    $this->updateInventoryStatus($inventory);
                }

                // Store check payment details if payment_type is CHECK
                if ($request->payment_type === 'CHECK' && $request->has('check_details')) {
                    \App\Models\CheckPayment::create([
                        'sales_id' => $sales->id,
                        'bank_name' => $request->check_details['bankName'],
                        'check_number' => $request->check_details['checkNumber'],
                        'check_date' => $request->check_details['checkDate'],
                        'amount' => $request->check_details['amount'],
                        'remarks' => $request->check_details['remarks'] ?? null,
                    ]);
                }

                DB::commit();

                return response()->json([
                    'message' => 'Sales record created successfully',
                    'data' => $sales, 
                ], 201);

            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process payment',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        $sales = Sales::find($id);
        if (!$sales) {
            return response()->json([
                'error' => 'Sales record not found',
                'id' => $id
            ], 404);
        }

        try {
            $request->validate([
                'cashier_id' => 'required',
                'payment_type' => 'required|in:CASH,CARD,CHECK,ONLINE',
                'items' => 'required',
                'status' => 'required',
                // Add validation for specialNote if present
                'items.*.specialNote' => 'nullable|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $th->errors()
            ], 422);
        }

        $total = 0;

        foreach ($request->get('items') as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $total -= $total*$request->get('discount')/100;

        $salesRecord = [
            'customer_id' => $request->get('customer_id'),
            'cashier_id' => $request->get('cashier_id'),
            'payment_type' => $request->get('payment_type'),
            'time' => new \DateTime('now'),
            'status' => $request->get('status'),
            'amount' => $total,
            'discount' => $request->get('discount'),
        ];

        DB::beginTransaction();
        try {
            // Update sales record
            $sales->update($salesRecord);

            $product_sales = Product_sales::where('sales_id', $sales['id'])->get()->keyBy('product_id');

            $updateData = [];
            $newItems = [];
            $deleteIds = [];
            $inventoryUpdates = [];
            $currentTimeStamp = now();

            $productIds = collect($request->get('items'))->pluck('product_id')->unique();
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            foreach ($request->get('items') as $newItem) {
                $product = $products->get($newItem['product_id']);

                if (!$product) {
                    throw new \Exception("Product with ID {$newItem['product_id']} not found.");
                }

                $inventory = $product->inventory;
                if (!$inventory) {
                    throw new \Exception("Inventory for Product with ID {$newItem['product_id']} not found.");
                }

                // Always recalculate area_sqm from width and height, round to 2 decimals
                $widthInch = isset($newItem['width']) ? $newItem['width'] : null;
                $heightInch = isset($newItem['height']) ? $newItem['height'] : null;
                $areaSqm = null;
                if ($widthInch !== null && $heightInch !== null) {
                    $areaSqm = round($widthInch * $heightInch * 0.00064516, 2);
                }

                $specialNote = isset($newItem['specialNote']) ? $newItem['specialNote'] : null; // <-- Add this line

                if (isset($product_sales[$newItem['product_id']])) {
                    $existingItem = $product_sales[$newItem['product_id']];
                    $quantityDiff = $newItem['quantity'] - $existingItem->quantity;

                    if ($quantityDiff !== 0) {
                        if ($quantityDiff > 0 && $inventory->quantity < $quantityDiff) {
                            throw new \Exception("Not enough stock for Product ID {$newItem['product_id']}");
                        }

                        $newQuantity = $inventory->quantity - $quantityDiff;
                        $inventoryUpdates[] = [
                            'inventory_id' => $inventory->id,
                            'new_quantity' => $newQuantity
                        ];
                    }

                    $updateData[] = [
                        'product_id' => $newItem['product_id'],
                        'sales_id' => $sales['id'],
                        'quantity' => $newItem['quantity'],
                        'price' => $newItem['price'],
                        'width_inch' => $widthInch,
                        'height_inch' => $heightInch,
                        'area_sqm' => $areaSqm,
                        'special_note' => $specialNote, // <-- Add this line
                    ];

                    unset($product_sales[$newItem['product_id']]);
                } else {
                    if ($inventory->quantity < $newItem['quantity']) {
                        throw new \Exception("Not enough stock for Product ID {$newItem['product_id']}");
                    }

                    $newItems[] = [
                        'product_id' => $newItem['product_id'],
                        'sales_id' => $sales['id'],
                        'quantity' => $newItem['quantity'],
                        'price' => $newItem['price'],
                        'width_inch' => $widthInch,
                        'height_inch' => $heightInch,
                        'area_sqm' => $areaSqm,
                        'special_note' => $specialNote, // <-- Add this line
                        'created_at' => $currentTimeStamp,
                        'updated_at' => $currentTimeStamp
                    ];

                    $inventoryUpdates[] = [
                        'inventory_id' => $inventory->id,
                        'new_quantity' => $inventory->quantity - $newItem['quantity']
                    ];
                }
            }

            $deleteIds = $product_sales->keys()->toArray();
            if (!empty($deleteIds)) {
                Product_sales::whereIn('product_id', $deleteIds)
                    ->where('sales_id', $sales['id'])
                    ->delete();

                foreach ($product_sales as $deletedItem) {
                    $product = Product::find($deletedItem->product_id);
                    if ($product && $product->inventory) {
                        $inventoryUpdates[] = [
                            'inventory_id' => $product->inventory->id,
                            'new_quantity' => $product->inventory->quantity + $deletedItem->quantity
                        ];
                    }
                }
            }

            foreach ($updateData as $data) {
                Product_sales::where('product_id', $data['product_id'])
                    ->where('sales_id', $data['sales_id'])
                    ->update([
                        'quantity' => $data['quantity'],
                        'price' => $data['price'],
                        'width_inch' => $data['width_inch'],
                        'height_inch' => $data['height_inch'],
                        'area_sqm' => $data['area_sqm'],
                        'special_note' => $data['special_note'], // <-- Add this line
                    ]);
            }

            if (!empty($newItems)) {
                Product_sales::insert($newItems);
            }

            foreach ($inventoryUpdates as $update) {
                $inventory = Inventory::find($update['inventory_id']);
                if ($inventory) {
                    $inventory->update(['quantity' => $update['new_quantity']]);
                    $this->updateInventoryStatus($inventory);
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'error' => 'An error occurred while updating the sales record',
                'details' => $th->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Sales record updated successfully',
            'data' => $sales
        ], 200);
    }

    public function return(Request $request, $id)
    {
        $sales = Sales::find($id);
        if (!$sales) {
            return response()->json([
                'error' => 'Sales record not found',
                'id' => $id
            ], 404);
        }

        $returnItems[] = [];

        try {
            $request->validate([
                'cashier_id' => 'required',
                'payment_type' => 'required',
                'items' => 'required',
                'status' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $th) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $th->errors()
            ], 422);
        }

        $date = now();
        $returnItems = [];
        $salesReturnItems = [];

        DB::beginTransaction();
        try {
            $productIds = collect($request->get('items'))->pluck('product_id');
            $products = Product::with('inventory')->whereIn('id', $productIds)->get()->keyBy('id');

            foreach ($request->get('items') as $item) {
                $product = $products->get($item['product_id']);

                if (!$product || !$product->inventory) {
                    throw new \Exception("Product or inventory not found for ID: {$item['product_id']}");
                }

                $returnItems[] = [
                    'reason' => $item['reason'],
                    'quantity' => $item['quantity'],
                    'product_id' => $item['product_id'],
                    'created_at' => $date,
                    'updated_at' => $date
                ];

                $product->inventory->increment('quantity', $item['quantity']);

                $this->updateInventoryStatus($product->inventory);
            }

            ReturnItem::insert($returnItems);

            $insertedReturnItems = ReturnItem::latest('created_at')
                ->take(count($returnItems))
                ->get();

            foreach ($insertedReturnItems as $insertedItem) {
                $salesReturnItems[] = [
                    'sales_id' => $sales['id'],
                    'return_item_id' => $insertedItem['id'],
                    'returned_at' => $date,
                    'created_at' => $date,
                    'updated_at' => $date
                ];
            }

            SalesReturnItem::insert($salesReturnItems);

            DB::commit();

            return response()->json([
                'message' => 'Items returned and inventory updated successfully',
                'data' => [
                    'returns' => $returnItems,
                    'updated_products' => $products->map(function($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'new_quantity' => $product->inventory->quantity
                        ];
                    })
                ]
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'error' => 'An error occurred while processing the return',
                'details' => $th->getMessage()
            ], 500);
        }
    }

    private function updateSales(Request $request, $sales, $salesRecord)
    {
        $sales->update($salesRecord);

        $product_sales = Product_sales::where('sales_id', $sales['id'])->get()->keyBy('product_id');

        $updateData = [];
        $newItems = [];
        $deleteIds = [];
        $inventoryUpdates = [];
        $currentTimeStamp = now();

        $productIds = collect($request->get('items'))->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        foreach ($request->get('items') as $newItem) {
            $product = $products->get($newItem['product_id']);

            if (!$product) {
                throw new \Exception("Product with ID {$newItem['product_id']} not found.");
            }

            $inventory = $product->inventory;
            if (!$inventory) {
                throw new \Exception("Inventory for Product with ID {$newItem['product_id']} not found.");
            }

            // Always recalculate area_sqm from width and height, round to 2 decimals
            $widthInch = isset($newItem['width']) ? $newItem['width'] : null;
            $heightInch = isset($newItem['height']) ? $newItem['height'] : null;
            $areaSqm = null;
            if ($widthInch !== null && $heightInch !== null) {
                $areaSqm = round($widthInch * $heightInch * 0.00064516, 2);
            }

            $specialNote = isset($newItem['specialNote']) ? $newItem['specialNote'] : null; // <-- Add this line

            if (isset($product_sales[$newItem['product_id']])) {
                $existingItem = $product_sales[$newItem['product_id']];
                $quantityDiff = $newItem['quantity'] - $existingItem->quantity;

                if ($quantityDiff !== 0) {
                    if ($quantityDiff > 0 && $inventory->quantity < $quantityDiff) {
                        throw new \Exception("Not enough stock for Product ID {$newItem['product_id']}");
                    }

                    $newQuantity = $inventory->quantity - $quantityDiff;
                    $inventoryUpdates[] = [
                        'inventory_id' => $inventory->id,
                        'new_quantity' => $newQuantity
                    ];
                }

                $updateData[] = [
                    'product_id' => $newItem['product_id'],
                    'sales_id' => $sales['id'],
                    'quantity' => $newItem['quantity'],
                    'price' => $newItem['price'],
                    'width_inch' => $widthInch,
                    'height_inch' => $heightInch,
                    'area_sqm' => $areaSqm,
                    'special_note' => $specialNote, // <-- Add this line
                ];

                unset($product_sales[$newItem['product_id']]);
            } else {
                if ($inventory->quantity < $newItem['quantity']) {
                    throw new \Exception("Not enough stock for Product ID {$newItem['product_id']}");
                }

                $newItems[] = [
                    'product_id' => $newItem['product_id'],
                    'sales_id' => $sales['id'],
                    'quantity' => $newItem['quantity'],
                    'price' => $newItem['price'],
                    'width_inch' => $widthInch,
                    'height_inch' => $heightInch,
                    'area_sqm' => $areaSqm,
                    'special_note' => $specialNote, // <-- Add this line
                    'created_at' => $currentTimeStamp,
                    'updated_at' => $currentTimeStamp
                ];

                $inventoryUpdates[] = [
                    'inventory_id' => $inventory->id,
                    'new_quantity' => $inventory->quantity - $newItem['quantity']
                ];
            }
        }

        $deleteIds = $product_sales->keys()->toArray();
        if (!empty($deleteIds)) {
            Product_sales::whereIn('product_id', $deleteIds)
                ->where('sales_id', $sales['id'])
                ->delete();

            foreach ($product_sales as $deletedItem) {
                $product = Product::find($deletedItem->product_id);
                if ($product && $product->inventory) {
                    $inventoryUpdates[] = [
                        'inventory_id' => $product->inventory->id,
                        'new_quantity' => $product->inventory->quantity + $deletedItem->quantity
                    ];
                }
            }
        }

        foreach ($updateData as $data) {
            Product_sales::where('product_id', $data['product_id'])
                ->where('sales_id', $data['sales_id'])
                ->update([
                    'quantity' => $data['quantity'],
                    'price' => $data['price'],
                    'width_inch' => $data['width_inch'],
                    'height_inch' => $data['height_inch'],
                    'area_sqm' => $data['area_sqm'],
                    'special_note' => $data['special_note'], // <-- Add this line
                ]);
        }

        if (!empty($newItems)) {
            Product_sales::insert($newItems);
        }

        foreach ($inventoryUpdates as $update) {
            $inventory = Inventory::find($update['inventory_id']);
            if ($inventory) {
                $inventory->update(['quantity' => $update['new_quantity']]);
                $this->updateInventoryStatus($inventory);
            }
        }
    }

    private function updateInventoryStatus($inventory)
    {
        $status = 'In Stock';
        if ($inventory->quantity == 0) {
            $status = 'Out Of Stock';
        } elseif ($inventory->quantity < 20) {
            $status = 'Low Stock';
        }

        $inventory->status = $status;
        $inventory->save();
    }

    public function salesReportToday()
    {
        $sales = Sales::whereDate('created_at', today())->get();
        $totalCustomers =count(Customer::all());
        $totalSuppiers=count(Supplier::all());
        $totalIncome = 0;
        if ($sales) {
            foreach ($sales as $sale) {
                $totalIncome += $sale['amount'];
            }
        }
        return response()->json([
            "sales_details"=>$sales,
            "total_sales" => count($sales),
            "total_income" => $totalIncome,
            "total_customers" => $totalCustomers,
            "total_suppliers" => $totalSuppiers
        ]);
    }

    public function salesReports(Request $request)
    {
        $startDate = $request->query('from');
        $endDate = $request->query('to');

        if (!$startDate || !$endDate) {
            return response()->json([
                'message' => 'Invalid date range. Please provide both start and end dates.'
            ], 400);
        }

    try {
        $startDate = new \DateTime($startDate);
        $endDate = new \DateTime($endDate);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Invalid date format. Please provide dates in YYYY-MM-DD format.'
        ], 400);
    }

    $startDateFormatted = $startDate->format('Y-m-d 00:00:00');
    $endDateFormatted = $endDate->format('Y-m-d 23:59:59');

    $sales = Sales::select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
    ->whereBetween('created_at', [$startDateFormatted, $endDateFormatted])
    ->groupBy(DB::raw('DATE(created_at)'))
    ->get();

        return response()->json([
            "total_sales" => $sales,
            "start"=>$startDate,
            "end"=>$endDate,
        ]);
    }

    public function bestSelling()
    {
        $bestSellingProducts = Product_Sales::select('product_id', DB::raw('count(*) as total'))
            ->groupBy('product_id')
            ->orderBy('total', 'desc')
            ->first();

        if (!$bestSellingProducts) {
            return response()->json([
                'message' => 'No sales data available'
            ], 404);
        }

        return response()->json([
            'products' => $bestSellingProducts,
            'total_sales' => $bestSellingProducts->total
        ]);
    }

    public function turnOverProducts()
    {
        $turnOverProducts = ReturnItem::select('product_id', DB::raw('count(*) as total'))
            ->groupBy('product_id')
            ->orderBy('total', 'desc')
            ->first();

        if (!$turnOverProducts) {
            return response()->json([
                'message' => 'No sales data available'
            ], 404);
        }

        return response()->json([
            'products' => $turnOverProducts,
            'total_returns' => $turnOverProducts->total
        ]);
    }

    public function paymentDistribution()
    {
        $paymentDistribution = Sales::select('payment_type', DB::raw('count(*) as total'))
            ->groupBy('payment_type')
            ->get();

        if ($paymentDistribution->isEmpty()) {
            return response()->json([
                'message' => 'No sales data available'
            ], 404);
        }

        return response()->json([
            'payment_distribution' => $paymentDistribution
        ]);
    }

    // Add this new method for completing pending payments
    public function completePayment(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'payment_amount' => 'required|numeric|min:0',
                'payment_method' => 'required|in:CASH,CARD,CHECK,ONLINE',
                'payment_info' => 'required_if:payment_method,CHECK,CARD,ONLINE|array'
            ]);

            $sale = Sales::findOrFail($id);
            
            if ($sale->payment_status === 'PAID') {
                return response()->json([
                    'message' => 'This sale is already fully paid'
                ], 400);
            }

            $newPaymentAmount = $request->payment_amount;
            if ($newPaymentAmount > $sale->remaining_balance) {
                return response()->json([
                    'message' => 'Payment amount cannot exceed remaining balance',
                    'remaining_balance' => $sale->remaining_balance
                ], 400);
            }

            DB::beginTransaction();

            // Process new payment info
            $paymentInfo = array_merge($request->get('payment_info', []), [
                'amount' => $request->payment_amount,
                'date' => now(),
                'type' => $request->payment_method
            ]);

            // Get existing payment info and append new payment
            $existingInfo = json_decode($sale->payment_info ?? '[]', true);
            if (!is_array($existingInfo)) {
                $existingInfo = [];
            }
            $existingInfo[] = $paymentInfo;

            // Update sale
            $sale->paid_amount += $request->payment_amount;
            $sale->balance_amount -= $request->payment_amount;
            $sale->payment_method = $request->payment_method;
            $sale->payment_info = json_encode($existingInfo);
            
            if ($sale->balance_amount <= 0) {
                $sale->payment_status = 'PAID';
                $sale->status = 1;
            } else {
                $sale->payment_status = 'PARTIALLY_PAID';
            }

            $sale->save();
            DB::commit();

            return response()->json([
                'message' => 'Payment updated successfully',
                'data' => $sale
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to process payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
