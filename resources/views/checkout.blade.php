<x-app-layout> {{-- Menggunakan layout app.blade.php --}}

    {{-- Judul Halaman Spesifik --}}
    <x-slot name="title">
        Checkout - PenjahitKu
    </x-slot>

    {{-- Slot CSS Tambahan (Gaya checkout) --}}
    <x-slot name="styles">
        <style>
            .checkout-section { padding: 3rem 0; }
            .checkout-section h1 {
                font-size: 2.5rem;
                color: var(--dark-color);
                margin-bottom: 2rem;
                border-bottom: 2px solid var(--grey-color);
                padding-bottom: 1rem;
            }
            .checkout-layout { display: grid; grid-template-columns: 1fr; gap: 2rem; }
            @media (min-width: 768px) { .checkout-layout { grid-template-columns: 2fr 1fr; } }
            .checkout-form { background: var(--white-color); padding: 2rem; border-radius: 8px; border: 1px solid var(--grey-color); }
            .checkout-form h2 { font-size: 1.5rem; color: var(--primary-color); margin-bottom: 1.5rem; }
            .form-group { margin-bottom: 1rem; }
            .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; }
            /* Style input/select/textarea pakai class Tailwind */
            .order-summary { background: var(--white-color); border-radius: 8px; padding: 2rem; border: 1px solid var(--grey-color); position: sticky; top: 100px; }
            .order-summary h2 { font-size: 1.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid var(--grey-color); padding-bottom: 1rem; }
            .summary-item { display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem; }
            .summary-item-name { color: #555; }
            .summary-item-price { color: #333; }
            .summary-total { display: flex; justify-content: space-between; font-size: 1.3rem; font-weight: 700; color: var(--primary-color); border-top: 2px solid var(--grey-color); padding-top: 1rem; margin-top: 1.5rem; }
             /* Kelas untuk pesan error validasi */
            .text-red-500 { color: #ef4444; }
            .text-sm { font-size: 0.875rem; }

        </style>
    </x-slot>

    {{-- Konten Utama Halaman Checkout --}}
    <div class="checkout-section">
        <div class="container">
            <h1>Checkout</h1>

            @if(isset($cartItems) && count($cartItems) > 0)
                <form class="checkout-layout" id="checkout-form" method="POST" action="{{ route('checkout.store') }}">
                    @csrf 

                    {{-- Kolom 1: Form Alamat --}}
                    <div class="checkout-form">
                        <h2>Detail Pengiriman</h2>
                        <div class="form-group">
                            <label for="name">Nama Lengkap</label>
                            <input type="text" id="name" name="name" required class="border-gray-300 rounded-md shadow-sm w-full" value="{{ old('name', Auth::user()->name ?? '') }}">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required class="border-gray-300 rounded-md shadow-sm w-full" value="{{ old('email', Auth::user()->email ?? '') }}">
                             @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="phone">Nomor Telepon</label>
                            <input type="tel" id="phone" name="phone" required class="border-gray-300 rounded-md shadow-sm w-full" value="{{ old('phone') }}" placeholder="Contoh: 08123456789">
                             @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label for="address">Alamat Lengkap</label>
                            <textarea id="address" name="address" rows="3" required class="border-gray-300 rounded-md shadow-sm w-full">{{ old('address') }}</textarea>
                             @error('address') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <h2>Opsi Pengiriman (Simulasi)</h2>
                        <div class="form-group">
                            <label for="courier">Kurir Pengiriman</label>
                            <select id="courier" name="courier" class="border-gray-300 rounded-md shadow-sm w-full">
                                <option value="jne" @selected(old('courier') == 'jne')>JNE Reguler</option>
                                <option value="sicepat" @selected(old('courier') == 'sicepat')>Sicepat BEST</option>
                                <option value="grab" @selected(old('courier') == 'grab')>Grab/Gojek Same Day</option>
                            </select>
                             @error('courier') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        {{-- === BLOK METODE PEMBAYARAN DIHAPUS === --}}
                        {{-- 
                        <div class="form-group">
                            <label for="payment_method">Metode Pembayaran</label>
                            <select id="payment_method" name="payment_method" class="border-gray-300 rounded-md shadow-sm w-full">
                                <option value="bca">Transfer Bank (BCA)</option>
                                <option value="gopay">GoPay / OVO</option>
                            </select>
                             @error('payment_method') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        --}}
                        {{-- === AKHIR PENGHAPUSAN === --}}
                        
                         {{-- Input tersembunyi untuk payment_method (agar validasi backend lolos) --}}
                        <input type="hidden" name="payment_method" value="xendit">
                    </div>

                    {{-- Kolom 2: Ringkasan Pesanan --}}
                    <div class="order-summary" id="order-summary">
                        <h2>Ringkasan Pesanan</h2>

                        <div id="summary-items-list">
                            @foreach ($cartItems as $item)
                                <div class="summary-item">
                                    <span class="summary-item-name">{{ $item['name'] ?? 'N/A' }} (x{{ $item['quantity'] ?? 0 }})</span>
                                    <span class="summary-item-price">Rp {{ number_format(($item['priceRaw'] ?? 0) * ($item['quantity'] ?? 0), 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="summary-total">
                            <span>Total</span>
                            <span id="summary-total-price">Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 2rem;">
                            Lanjut ke Pembayaran
                        </button>
                    </div>

                </form>
            @else
                 <div class="empty-cart-message">
                    <p>Keranjang Anda kosong.</p>
                    <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke Katalog</a>
                 </div>
            @endif
        </div>
    </div>

    {{-- Slot JavaScript Tambahan --}}
    <x-slot name="scripts">
        <script>
             document.addEventListener('DOMContentLoaded', function() {
                  const user = @json(Auth::user());
                   if(user) {
                       const nameInput = document.getElementById('name');
                       const emailInput = document.getElementById('email');
                       if (nameInput && !nameInput.value) nameInput.value = user.name || '';
                       if (emailInput && !emailInput.value) emailInput.value = user.email || '';
                   }
             });
        </script>
    </x-slot>

</x-app-layout>