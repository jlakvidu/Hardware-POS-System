<?php

namespace App\Http\Controllers;

use App\Models\SalaryPayment;
use App\Models\Cashier;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // Add this import

class SalaryPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = SalaryPayment::with('cashier');
        // Optional filters
        if ($request->has('cashier_id')) {
            $query->where('cashier_id', $request->cashier_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('date_from')) {
            $query->where('payment_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('payment_date', '<=', $request->date_to);
        }
        return response()->json($query->orderBy('payment_date', 'desc')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cashier_id' => 'required|exists:cashier,id',
            'payment_period' => 'required|string',
            'payment_date' => 'required|date',
            'base_salary' => 'required|numeric',
            'additions' => 'nullable|numeric',
            'deductions' => 'nullable|numeric',
            'net_pay' => 'required|numeric',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string'
        ]);
        $data['invoice_number'] = 'SAL-' . time() . '-' . rand(1000,9999);
        $data['status'] = 'Paid';
        $salaryPayment = SalaryPayment::create($data);
        return response()->json($salaryPayment->load('cashier'), 201);
    }

    public function show($id)
    {
        $salaryPayment = SalaryPayment::with('cashier')->find($id);
        if (!$salaryPayment) {
            return response()->json(['error' => 'Salary payment not found'], 404);
        }
        return response()->json($salaryPayment);
    }

    public function update(Request $request, $id)
    {
        $salaryPayment = SalaryPayment::find($id);
        if (!$salaryPayment) {
            return response()->json(['error' => 'Salary payment not found'], 404);
        }
        $data = $request->validate([
            'cashier_id' => 'required|exists:cashier,id',
            'payment_period' => 'required|string',
            'payment_date' => 'required|date',
            'base_salary' => 'required|numeric',
            'additions' => 'nullable|numeric',
            'deductions' => 'nullable|numeric',
            'net_pay' => 'required|numeric',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string'
        ]);
        $salaryPayment->update($data);
        return response()->json($salaryPayment->load('cashier'));
    }

    public function destroy($id)
    {
        $salaryPayment = SalaryPayment::find($id);
        if (!$salaryPayment) {
            return response()->json(['error' => 'Salary payment not found'], 404);
        }
        $salaryPayment->delete();
        return response()->json(['message' => 'Salary payment deleted']);
    }

    public function exportInvoicePdf($id)
    {
        $salaryPayment = SalaryPayment::with('cashier')->find($id);
        if (!$salaryPayment) {
            return response()->json(['error' => 'Salary payment not found'], 404);
        }
        // Use a professional invoice template
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('salary_invoice', ['salaryPayment' => $salaryPayment]);
        $filename = 'Salary-Invoice-' . ($salaryPayment->invoice_number ?? $salaryPayment->id) . '.pdf';
        return $pdf->download($filename);
    }
}
