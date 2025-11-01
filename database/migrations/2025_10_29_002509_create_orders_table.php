<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Kolom user_id bisa null jika tamu boleh checkout
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('name'); // Nama pemesan
            $table->string('email');
            $table->string('phone');
            $table->text('address');
            $table->string('courier'); // Kurir yg dipilih
            $table->string('payment_method'); // Metode bayar
            $table->decimal('total_price', 15, 2); // Total harga akhir
            $table->string('status')->default('pending'); // Status awal: pending
            $table->timestamps(); // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
