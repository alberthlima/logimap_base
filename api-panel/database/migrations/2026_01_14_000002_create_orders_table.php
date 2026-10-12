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
            $table->string('code', 30)->unique(); // PED-1001
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->decimal('total_amount', 10, 2)->default(0.00); // Monto en Bs
            $table->string('payment_method', 30)->default('Efectivo'); // Efectivo, QR, Crédito
            $table->enum('status', ['REGISTRADO', 'EN_CAMINO', 'ENTREGADO', 'PAGADO'])->default('REGISTRADO');
            $table->string('city', 50)->default('El Alto');
            $table->string('district', 50)->default('Distrito 1');
            $table->string('delivery_address')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('delivery_driver')->nullable(); // Repartidor asignado
            $table->text('items_summary')->nullable(); // Resumen de productos
            $table->text('notes')->nullable();
            $table->date('delivery_date')->nullable();
            $table->timestamps();
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
