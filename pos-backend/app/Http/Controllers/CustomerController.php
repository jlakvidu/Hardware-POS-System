<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Customer_contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use function Illuminate\Log\log;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with('customerContact')->paginate(10);

        // Map to include contact_number directly for frontend
        $data = array_map(function ($customer) {
            $contact = $customer['customerContact'][0]['contact_number'] ?? '';
            return [
                'id' => $customer['id'],
                'name' => $customer['name'],
                'email' => $customer['email'],
                'contact' => [['contact_number' => $contact]],
            ];
        }, $customers->items());

        return response()->json([
            'data' => $data,
            'current_page' => $customers->currentPage(),
            'per_page' => $customers->perPage(),
            'total' => $customers->total(),
            'last_page' => $customers->lastPage(),
            'next_page_url' => $customers->nextPageUrl(),
            'prev_page_url' => $customers->previousPageUrl(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validatedAttributes = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:customers',
                'contact_number' => 'required|string|max:20',
            ]);
        } catch (ValidationException $th) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $th->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $customer = Customer::create(['name' => $validatedAttributes['name'], 'email' => $validatedAttributes['email']]);
            $contact = Customer_contact::create(['contact_number' => $validatedAttributes['contact_number'], "customer_id" => $customer['id']]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while saving the customer.',
                'errors' => $th->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully Saved a new Customer',
            'id' => $customer['id'],
            'name' => $customer['name'],
            'email' => $customer['email'],
            'contact' => [['contact_number' => $contact['contact_number']]],
        ]);
    }

    public function show($id)
    {
        $existingCustomer = Customer::with('customerContact')->find($id);
        if (!$existingCustomer) {
            return response()->json([
                'error' => 'Customer not found',
            ], 404);
        }

        return response()->json($existingCustomer);
    }

    public function update(Request $request, $id)
    {
        $existingCustomer = Customer::find($id);
        if (!$existingCustomer) {
            return response()->json([
                'error' => 'Customer not found',
            ], 404);
        }

        try {
            $validatedAttributes = $request->validate([
                'name' => 'required|string|max:255',
                'email' => ["required", "string", "email", "max:255", Rule::unique('customers')->ignore($id)],
                'contact_number' => 'required|string|max:20',
            ]);
        } catch (ValidationException $th) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $th->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $existingCustomer->update(['name' => $validatedAttributes['name'], 'email' => $validatedAttributes['email']]);
            Customer_contact::where('customer_id', $existingCustomer['id'])->update(['contact_number' => $validatedAttributes['contact_number']]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while updating the customer.',
                'errors' => $th->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully Updated Customer',
            $existingCustomer['id']
        ]);
    }

    public function destroy($id)
    {
        $existingCustomer = Customer::find($id);
        if (!$existingCustomer) {
            return response()->json([
                'error' => 'Customer not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $existingCustomer->delete();
            Customer_contact::where('customer_id', $existingCustomer['id'])->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while deleting the customer.',
                'errors' => $th->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Successfully Deleted Customer',
            $existingCustomer['id']
        ]);
    }
}
