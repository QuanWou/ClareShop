<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clare_pay_wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('pay_later_purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('total_amount', 14, 2);
            $table->unsignedTinyInteger('term_months');
            $table->timestamp('starts_at');
            $table->timestamp('due_at')->index();
            $table->decimal('amount_due', 14, 2);
            $table->string('selected_payment_method', 30)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->text('latest_failure_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('clare_pay_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained('clare_pay_wallets')->cascadeOnDelete();
            $table->foreignId('pay_later_purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30)->index();
            $table->string('direction', 10);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->string('status', 30)->default('pending')->index();
            $table->string('provider', 30)->nullable();
            $table->string('provider_reference', 100)->nullable()->unique();
            $table->json('payload')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pay_later_purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('clare_pay_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose', 30)->index();
            $table->string('provider', 30)->index();
            $table->string('provider_reference', 100)->nullable()->unique();
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('VND');
            $table->string('status', 30)->default('pending')->index();
            $table->text('approval_url')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('pay_later_notification_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pay_later_purchase_id')->constrained()->cascadeOnDelete();
            $table->string('event_key', 40);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['pay_later_purchase_id', 'event_key'], 'pay_later_event_unique');
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('pay_later_notification_events');
        Schema::dropIfExists('billing_payment_attempts');
        Schema::dropIfExists('clare_pay_transactions');
        Schema::dropIfExists('pay_later_purchases');
        Schema::dropIfExists('clare_pay_wallets');
    }
};
