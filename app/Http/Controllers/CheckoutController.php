<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Xendit\Xendit;
use Exception;

class CheckoutController extends Controller
{
    /**
     * Menampilkan halaman checkout dengan ringkasan pesanan.
     */
    public function index(): View|RedirectResponse
    {
        $cartItems = session('cart', []);

        if (empty($cartItems)) {
            return redirect()->route('keranjang.index')->with('error', 'Keranjang Anda kosong.');
        }

        $totalPrice = collect($cartItems)->sum(fn($item) =>
            ((int)($item['quantity'] ?? 0)) * ((float)($item['priceRaw'] ?? 0))
        );

        return view('checkout', [
            'cartItems' => $cartItems,
            'totalPrice' => $totalPrice,
        ]);
    }

    /**
     * Menyimpan data pesanan dan membuat invoice Xendit.
     */
    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'courier' => 'required|string',
            'payment_method' => 'required|string',
        ]);

        $cartItems = session('cart', []);
        if (empty($cartItems)) {
            return redirect()->route('keranjang.index')->with('error', 'Keranjang Anda kosong.');
        }

        $totalPrice = collect($cartItems)->sum(fn($item) =>
            ((int)($item['quantity'] ?? 0)) * ((float)($item['priceRaw'] ?? 0))
        );

        // Simpan pesanan ke database
        $order = Order::create([
            'user_id' => Auth::id(),
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'address' => $validatedData['address'],
            'courier' => $validatedData['courier'],
            'payment_method' => $validatedData['payment_method'],
            'total_price' => $totalPrice,
            'status' => 'pending',
        ]);

        foreach ($cartItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id'] ?? null,
                'product_name' => $item['name'] ?? 'Produk Tidak Diketahui',
                'quantity' => $item['quantity'] ?? 0,
                'price' => $item['priceRaw'] ?? 0,
                'custom_size' => $item['custom']['ukuran'] ?? null,
                'custom_chest' => $item['custom']['lingkarDada'] ?? null,
                'custom_notes' => $item['custom']['catatan'] ?? null,
            ]);
        }

        session()->forget('cart');

        // 🔑 Gunakan API Key dari .env
        Xendit::setApiKey(config('services.xendit.secret_key'));

        // 📦 Buat invoice baru
        $params = [
            'external_id' => 'order-' . $order->id,
            'payer_email' => $order->email,
            'description' => 'Pembayaran Pesanan #' . $order->id,
            'amount' => $order->total_price,
            'success_redirect_url' => route('checkout.success'),
            'failure_redirect_url' => route('checkout.failed'),
        ];

        try {
            $invoice = \Xendit\Invoice::create($params);

            // Update pesanan dengan URL pembayaran
            $order->update([
                'payment_url' => $invoice['invoice_url'],
                'status' => 'waiting_payment',
            ]);

            session()->flash('lastOrder', [
                'orderId' => $order->id,
                'customerName' => $order->name,
                'totalPrice' => $order->total_price,
            ]);

            return redirect()->away($invoice['invoice_url']);

        } catch (Exception $e) {
            return redirect()->route('checkout.index')->with('error', 'Gagal membuat invoice Xendit: ' . $e->getMessage());
        }
    }

    /**
     * Mengecek saldo akun Xendit (opsional untuk testing koneksi API).
     */
    public function checkBalance()
    {
        Xendit::setApiKey(config('services.xendit.secret_key'));

        try {
            $balance = \Xendit\Balance::getBalance('CASH');
            return response()->json($balance);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
