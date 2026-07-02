<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $accounts = DB::table('accounts')->join('spaces', 'accounts.space_id', '=', 'spaces.id')
            ->where('spaces.type', 'family')->where('accounts.visibility', 'personal')
            ->select('accounts.*')->orderBy('accounts.id')->get();

        foreach ($accounts as $account) {
            $personalSpaceId = DB::table('spaces')->where('owner_id', $account->user_id)->where('type', 'personal')->value('id');
            if (! $personalSpaceId) {
                continue;
            }
            foreach (DB::table('transactions')->where('account_id', $account->id)->where('space_id', $account->space_id)->get() as $transaction) {
                DB::table('transactions')->where('id', $transaction->id)->update([
                    'space_id' => $personalSpaceId,
                    'category_id' => $this->personalCategory($transaction->category_id, $account->user_id, $personalSpaceId),
                ]);
            }
            DB::table('accounts')->where('id', $account->id)->update(['space_id' => $personalSpaceId]);
        }
    }

    public function down(): void
    {
        // Data historis tidak dicampur kembali ke ruang keluarga.
    }

    private function personalCategory(?int $categoryId, int $userId, int $spaceId): ?int
    {
        if (! $categoryId) {
            return null;
        }
        $category = DB::table('categories')->where('id', $categoryId)->first();
        if (! $category) {
            return null;
        }
        $existing = DB::table('categories')->where('space_id', $spaceId)->where('name', $category->name)->where('type', $category->type)->value('id');
        if ($existing) {
            return $existing;
        }

        return DB::table('categories')->insertGetId([
            'user_id' => $userId, 'space_id' => $spaceId, 'parent_id' => null,
            'name' => $category->name, 'type' => $category->type, 'icon' => $category->icon,
            'color' => $category->color, 'sort_order' => $category->sort_order, 'is_active' => $category->is_active,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
};
