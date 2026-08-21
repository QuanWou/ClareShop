<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('unit', 30)->nullable();
            $table->string('filter_type', 20)->default('select');
            $table->boolean('is_filterable')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_attribute_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_attribute_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('slug');
            $table->decimal('numeric_value', 12, 2)->nullable();
            $table->char('color_hex', 7)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_attribute_id', 'slug'], 'attribute_values_attribute_slug_unique');
            $table->index(['product_attribute_id', 'sort_order'], 'attribute_values_sort_index');
        });

        Schema::create('attribute_value_product', function (Blueprint $table): void {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_attribute_value_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'product_attribute_value_id'], 'attribute_value_product_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_value_product');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('product_attributes');
    }
};
