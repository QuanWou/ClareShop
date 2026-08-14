<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->index()->after('email');
            $table->string('role', 30)->default('customer')->index()->after('password');
            $table->boolean('is_active')->default(true)->index()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone']);
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['phone', 'role', 'is_active']);
        });
    }
};
