<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->index();
            $table->string('status', 30)->default('pending')->index();

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30);
            $table->dateTime('preferred_starts_at');
            $table->dateTime('preferred_ends_at')->nullable();
            $table->dateTime('scheduled_starts_at')->nullable();
            $table->dateTime('scheduled_ends_at')->nullable();

            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('ward')->nullable();
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->char('country_code', 2)->default('VN');

            $table->text('customer_note')->nullable();
            $table->text('internal_note')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
