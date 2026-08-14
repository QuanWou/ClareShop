<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('provider_reference')->nullable()->unique();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3);
            $table->string('status', 30)->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
