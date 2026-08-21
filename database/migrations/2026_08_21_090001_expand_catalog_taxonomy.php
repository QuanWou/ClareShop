<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('categories')
                ->nullOnDelete();
            $table->string('seo_title')->nullable()->after('description');
            $table->string('seo_description', 500)->nullable()->after('seo_title');
        });

        Schema::create('category_product', function (Blueprint $table): void {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'product_id']);
        });

        DB::table('products')
            ->whereNotNull('category_id')
            ->orderBy('id')
            ->select(['id', 'category_id'])
            ->chunkById(200, function ($products): void {
                DB::table('category_product')->insertOrIgnore(
                    $products->map(fn ($product): array => [
                        'category_id' => $product->category_id,
                        'product_id' => $product->id,
                    ])->all(),
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['seo_title', 'seo_description']);
        });
    }
};
