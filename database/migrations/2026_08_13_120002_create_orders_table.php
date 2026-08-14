<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('pending')->index();
            $table->string('payment_method', 30);
            $table->string('payment_status', 30)->default('unpaid')->index();
            $table->char('currency', 3)->default('VND');

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30);

            $table->string('shipping_recipient_name');
            $table->string('shipping_phone', 30);
            $table->string('shipping_address_line_1');
            $table->string('shipping_address_line_2')->nullable();
            $table->string('shipping_ward');
            $table->string('shipping_district');
            $table->string('shipping_city');
            $table->string('shipping_postal_code', 20)->nullable();
            $table->char('shipping_country_code', 2)->default('VN');

            $table->string('shipping_provider', 50)->nullable();
            $table->string('shipping_service', 100)->nullable();
            $table->string('shipping_quote_id', 100)->nullable()->index();
            $table->json('shipping_quote_payload')->nullable();
            $table->unsignedInteger('shipping_total_weight_grams')->default(0);
            $table->unsignedSmallInteger('shipping_estimated_days')->nullable();
            $table->boolean('shipping_fee_is_estimated')->default(true);

            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_fee', 12, 2);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('placed_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
