<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('theme');
        });
        Schema::create('monthly_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('closed_by')->constrained('users')->restrictOnDelete();
            $table->date('month');
            $table->json('snapshot');
            $table->string('notes', 500)->nullable();
            $table->timestamp('closed_at');
            $table->timestamps();
            $table->unique(['space_id', 'month']);
        });
        Schema::create('financial_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('name', 100);
            $table->decimal('target_amount', 18, 2);
            $table->decimal('current_amount', 18, 2)->default(0);
            $table->date('deadline')->nullable();
            $table->string('color', 20)->default('#087f70');
            $table->string('status', 20)->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['space_id', 'status']);
        });
        Schema::create('goal_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_goal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contributed_by')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->date('contributed_at');
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_contributions');
        Schema::dropIfExists('financial_goals');
        Schema::dropIfExists('monthly_closings');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('notification_preferences'));
    }
};
