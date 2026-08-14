<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shipping_tracking_number', 50)->nullable()->unique()->after('shipping_quote_id');
            $table->timestamp('estimated_delivery_at')->nullable()->index()->after('placed_at');
            $table->timestamp('preparing_at')->nullable()->after('confirmed_at');
            $table->timestamp('shipped_at')->nullable()->after('preparing_at');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['shipping_tracking_number']);
            $table->dropColumn([
                'shipping_tracking_number',
                'estimated_delivery_at',
                'preparing_at',
                'shipped_at',
                'delivered_at',
            ]);
        });
    }
};
