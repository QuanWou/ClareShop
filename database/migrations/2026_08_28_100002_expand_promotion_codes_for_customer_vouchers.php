<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_codes', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->string('banner_path')->nullable()->after('description');
            $table->decimal('maximum_order_amount', 12, 2)->nullable()->after('minimum_order_amount');
            $table->unsignedInteger('claim_limit')->nullable()->after('usage_limit');
            $table->unsignedInteger('claim_count')->default(0)->after('claim_limit');
            $table->unsignedInteger('per_user_usage_limit')->default(1)->after('claim_count');
            $table->boolean('is_public')->default(false)->index()->after('is_active');
            $table->boolean('requires_claim')->default(false)->after('is_public');
            $table->string('application_scope', 30)->default('order')->after('requires_claim');
        });
    }

    public function down(): void
    {
        Schema::table('promotion_codes', function (Blueprint $table) {
            $table->dropIndex(['is_public']);
            $table->dropColumn([
                'description',
                'banner_path',
                'maximum_order_amount',
                'claim_limit',
                'claim_count',
                'per_user_usage_limit',
                'is_public',
                'requires_claim',
                'application_scope',
            ]);
        });
    }
};
