<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function processPaymentCustomers($amount, $customerId) {

        $customer = Customer::find($customerId);
        if (!$customer) {
            return response()->json(['error' => 'Customer Tidak Ditemukan'], 404);
        }
        $balance = $customer->balance;
        if ($balance < $amount) {
            return response()->json(['error' => 'Saldo Tidak Mencukupi'], 400);
        }
        $customer->balance -= $amount;
        $customer->save();
    
        return response()->json(['message' => 'Pembayaran Berhasil'], 200);
    }


    public function processPayment(Request $request, $customerId)
    {
        // Validasi input
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);
        
        $amount = $request->input('amount');
        
        try {
            // Mencari customer
            $customer = Customer::findOrFail($customerId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Pelanggan Tidak Ditemukan'], 404);
        }

        // Cek saldo
        if ($customer->balance < $amount) {
            return response()->json(['error' => 'Saldo Tidak Mencukupi'], 400);
        }

        // Proses pembayaran dengan transaksi
        DB::beginTransaction();
        try {
            $customer->balance -= $amount;
            $customer->save();

            // Simpan riwayat pembayaran
            // Logika penyimpanan transaksi dapat ditambahkan di sini
            DB::commit();

            return response()->json(['message' => 'Pembayaran Berhasil'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Transaksi Batal'], 500);
        }
    }
    
    
}
