<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_code_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_voucher_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('status', 20)->default('reserved')->index();
            $table->decimal('discount_amount', 12, 2);
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->string('release_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['promotion_code_id', 'status', 'expires_at']);
            $table->index(['user_voucher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_reservations');
    }
};
