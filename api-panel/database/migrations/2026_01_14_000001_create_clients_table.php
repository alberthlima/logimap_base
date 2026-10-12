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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('business_name')->nullable(); // Nombre del Micromarket / Negocio / Cliente
            $table->string('phone', 30)->nullable();
            $table->string('city', 50)->default('El Alto');
            $table->string('district', 50)->default('Distrito 1');
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('zone')->nullable(); // Santiago II, Villa Adela, Río Seco, Sopocachi
            $table->text('notes')->nullable();
            $table->tinyInteger('state')->default(1); // 1 = activo, 0 = inactivo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
