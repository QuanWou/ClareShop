<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete()->unique();
            $table->foreignId('promotion_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 12, 2);
            $table->decimal('discount_amount', 12, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_discounts');
    }
};
