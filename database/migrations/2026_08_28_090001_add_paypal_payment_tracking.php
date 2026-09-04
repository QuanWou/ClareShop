<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('provider_transaction_id', 100)->nullable()->index()->after('provider_reference');
            $table->decimal('gateway_amount', 12, 2)->nullable()->after('currency');
            $table->char('gateway_currency', 3)->nullable()->after('gateway_amount');
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('gateway_currency');
            $table->text('approval_url')->nullable()->after('failure_reason');
            $table->timestamp('expires_at')->nullable()->index()->after('paid_at');
            $table->timestamp('webhook_confirmed_at')->nullable()->after('expires_at');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('confirmation_email_sent_at')->nullable()->after('cancelled_at');
        });

        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 30);
            $table->string('event_id', 150);
            $table->string('event_type', 100)->index();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 30)->default('received')->index();
            $table->json('payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('confirmation_email_sent_at');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['provider_transaction_id']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn([
                'provider_transaction_id',
                'gateway_amount',
                'gateway_currency',
                'exchange_rate',
                'approval_url',
                'expires_at',
                'webhook_confirmed_at',
            ]);
        });
    }
};
