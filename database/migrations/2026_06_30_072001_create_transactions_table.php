<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->foreignId('destination_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 10);
            $table->decimal('amount', 18, 2);
            $table->dateTime('transacted_at');
            $table->string('description')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status', 10)->default('paid');
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_rule', 20)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'transacted_at']);
            $table->index(['user_id', 'type']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
