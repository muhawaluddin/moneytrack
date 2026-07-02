<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('personal');
            $table->string('color', 20)->default('#087f70');
            $table->timestamps();
        });
        Schema::create('space_user', function (Blueprint $table) {
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('contributor');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->primary(['space_id', 'user_id']);
        });
        Schema::create('space_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 20)->default('contributor');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->index(['email', 'accepted_at']);
        });
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('space_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('visibility', 20)->default('personal')->after('space_id');
            $table->index(['space_id', 'is_active']);
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('space_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('space_id')->constrained('users')->nullOnDelete();
            $table->index(['space_id', 'transacted_at']);
        });
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'category_id', 'month']);
            $table->foreignId('space_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->unique(['space_id', 'category_id', 'month']);
        });

        $now = now();
        foreach (DB::table('users')->orderBy('id')->get() as $user) {
            $spaceId = DB::table('spaces')->insertGetId(['owner_id' => $user->id, 'name' => 'Pribadi '.$user->name, 'type' => 'personal', 'color' => '#087f70', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('space_user')->insert(['space_id' => $spaceId, 'user_id' => $user->id, 'role' => 'owner', 'joined_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('accounts')->where('user_id', $user->id)->update(['space_id' => $spaceId]);
            DB::table('transactions')->where('user_id', $user->id)->update(['space_id' => $spaceId, 'created_by' => $user->id]);
            DB::table('budgets')->where('user_id', $user->id)->update(['space_id' => $spaceId]);
        }
    }

    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->dropUnique(['space_id', 'category_id', 'month']);
            $table->dropConstrainedForeignId('space_id');
            $table->unique(['user_id', 'category_id', 'month']);
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('space_id');
        });
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('visibility');
            $table->dropConstrainedForeignId('space_id');
        });
        Schema::dropIfExists('space_invitations');
        Schema::dropIfExists('space_user');
        Schema::dropIfExists('spaces');
    }
};
