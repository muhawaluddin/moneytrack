<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('space_id')->nullable()->after('user_id')->constrained()->cascadeOnDelete();
            $table->index(['space_id', 'type', 'is_active']);
        });

        $personalSpaces = DB::table('spaces')->where('type', 'personal')->pluck('id', 'owner_id');
        foreach ($personalSpaces as $userId => $spaceId) {
            DB::table('categories')->where('user_id', $userId)->whereNull('space_id')->update(['space_id' => $spaceId]);
        }

        foreach (DB::table('spaces')->where('type', 'family')->orderBy('id')->get() as $space) {
            $source = DB::table('categories')->where('user_id', $space->owner_id)->where('space_id', $personalSpaces[$space->owner_id] ?? 0)->orderBy('id')->get();
            $referenced = DB::table('transactions')->where('space_id', $space->id)->whereNotNull('category_id')->pluck('category_id')
                ->merge(DB::table('budgets')->where('space_id', $space->id)->pluck('category_id'))->unique();
            $categories = $source->concat(DB::table('categories')->whereIn('id', $referenced)->get())->unique('id');
            $map = [];
            foreach ($categories as $category) {
                $map[$category->id] = DB::table('categories')->insertGetId([
                    'user_id' => $space->owner_id, 'space_id' => $space->id, 'parent_id' => null,
                    'name' => $category->name, 'type' => $category->type, 'icon' => $category->icon,
                    'color' => $category->color, 'sort_order' => $category->sort_order, 'is_active' => $category->is_active,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            foreach ($categories as $category) {
                if ($category->parent_id && isset($map[$category->parent_id])) {
                    DB::table('categories')->where('id', $map[$category->id])->update(['parent_id' => $map[$category->parent_id]]);
                }
                DB::table('transactions')->where('space_id', $space->id)->where('category_id', $category->id)->update(['category_id' => $map[$category->id]]);
                DB::table('budgets')->where('space_id', $space->id)->where('category_id', $category->id)->update(['category_id' => $map[$category->id]]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('space_id');
        });
    }
};
