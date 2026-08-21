<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('country')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('brand_id')
                ->nullable()
                ->after('category_id')
                ->constrained()
                ->nullOnDelete();
            $table->string('seo_title')->nullable()->after('dimensions');
            $table->string('seo_description', 500)->nullable()->after('seo_title');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('brand_id');
            $table->dropColumn(['seo_title', 'seo_description']);
        });

        Schema::dropIfExists('brands');
    }
};
