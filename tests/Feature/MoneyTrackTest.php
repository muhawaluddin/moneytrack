<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\FinancialGoal;
use App\Models\MonthlyClosing;
use App\Models\Space;
use App\Models\SpaceInvitation;
use App\Models\User;
use App\Notifications\MoneyTrackNotification;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MoneyTrackTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_render_core_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertOk()->assertSee('Ringkasan Pribadi');
        $this->actingAs($user)->get('/transactions')->assertOk()->assertSee('Transaksi Pribadi');
        $this->actingAs($user)->get('/reports')->assertOk()->assertSee('Laporan & Audit Keuangan');
        $this->assertFileExists(public_path('app.css'));
    }

    public function test_mobile_bottom_menu_renders_trigger_and_every_feature_link(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('aria-controls="mobile-menu"', false)
            ->assertSee('id="mobile-menu"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('Semua fitur MoneyTrack');

        foreach (['Beranda', 'Notifikasi', 'Transaksi', 'Anggaran', 'Target Keuangan', 'Laporan', 'Tutup Buku', 'Sumber Kas', 'Ruang & Anggota', 'Kategori', 'Pengaturan'] as $label) {
            $response->assertSee($label);
        }

        $mobileCss = file_get_contents(public_path('interface-polish.css'));
        $this->assertStringContainsString('visibility:hidden;pointer-events:none', $mobileCss);
        $this->assertStringContainsString('visibility:visible;pointer-events:auto', $mobileCss);
    }

    public function test_system_dark_theme_has_accessible_color_tokens_and_native_controls(): void
    {
        $user = User::factory()->create(['theme' => 'system']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-theme="system"', false)
            ->assertSee('/app.css?v=8', false);

        $css = file_get_contents(public_path('app.css'));
        $this->assertStringContainsString('html[data-theme="system"]', $css);
        $this->assertStringContainsString('color-scheme: dark', $css);
        $this->assertStringContainsString('--brand: #67e1cc', $css);
        $this->assertStringContainsString('--warning-text: #ffd089', $css);
    }

    public function test_registration_creates_default_categories(): void
    {
        $this->post('/register', [
            'name' => 'Wall', 'email' => 'wall@example.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect('/accounts/create');

        $this->assertAuthenticated();
        $this->assertDatabaseCount('categories', 25);
    }

    public function test_paid_transactions_keep_account_balances_consistent(): void
    {
        $user = User::factory()->create();
        $space = $this->spaceFor($user);
        $cash = Account::create(['user_id' => $user->id, 'space_id' => $space->id, 'name' => 'Cash', 'type' => 'cash', 'opening_balance' => 100000, 'current_balance' => 100000]);
        $bank = Account::create(['user_id' => $user->id, 'space_id' => $space->id, 'name' => 'Bank', 'type' => 'bank', 'opening_balance' => 500000, 'current_balance' => 500000]);
        $category = Category::create(['user_id' => $user->id, 'space_id' => $space->id, 'name' => 'Makan', 'type' => 'expense']);
        $service = app(TransactionService::class);

        $service->create($this->transaction($cash, 'income', 50000), $user->id, $space->id);
        $expense = $service->create($this->transaction($cash, 'expense', 25000, $category), $user->id, $space->id);
        $transfer = $service->create($this->transaction($cash, 'transfer', 30000, null, $bank), $user->id, $space->id);

        $this->assertSame('95000.00', $cash->refresh()->current_balance);
        $this->assertSame('530000.00', $bank->refresh()->current_balance);

        $service->delete($transfer);
        $service->update($expense, $this->transaction($cash, 'expense', 10000, $category), $user->id);
        $this->assertSame('140000.00', $cash->refresh()->current_balance);
    }

    public function test_cannot_use_another_users_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $space = $this->spaceFor($user);
        $otherSpace = $this->spaceFor($other);
        $foreign = Account::create(['user_id' => $other->id, 'space_id' => $otherSpace->id, 'name' => 'Private', 'type' => 'bank', 'opening_balance' => 0, 'current_balance' => 0]);

        $this->expectException(ValidationException::class);
        app(TransactionService::class)->create($this->transaction($foreign, 'income', 1000), $user->id, $space->id);
    }

    public function test_family_members_only_see_accounts_shared_with_them(): void
    {
        $husband = User::factory()->create();
        $wife = User::factory()->create();
        $space = Space::create(['owner_id' => $husband->id, 'name' => 'Keluarga', 'type' => 'family']);
        $space->members()->attach([$husband->id => ['role' => 'owner'], $wife->id => ['role' => 'manager']]);
        Account::create(['user_id' => $husband->id, 'space_id' => $space->id, 'visibility' => 'personal', 'name' => 'Rahasia', 'type' => 'bank']);
        Account::create(['user_id' => $husband->id, 'space_id' => $space->id, 'visibility' => 'shared', 'name' => 'Rumah Tangga', 'type' => 'cash']);

        $this->assertSame(['Rumah Tangga'], $space->visibleAccounts($wife)->pluck('name')->all());
        $this->assertSame(['Rumah Tangga'], $space->visibleAccounts($husband)->pluck('name')->all());
    }

    public function test_invited_partner_can_join_and_cannot_open_private_account(): void
    {
        $husband = User::factory()->create();
        $wife = User::factory()->create(['email' => 'wife@example.com']);
        $space = Space::create(['owner_id' => $husband->id, 'name' => 'Keluarga Wall', 'type' => 'family']);
        $space->members()->attach($husband->id, ['role' => 'owner']);
        $private = Account::create(['user_id' => $husband->id, 'space_id' => $space->id, 'visibility' => 'personal', 'name' => 'Tabungan Pribadi', 'type' => 'bank']);
        $token = 'secure-family-invitation-token';
        SpaceInvitation::create(['space_id' => $space->id, 'invited_by' => $husband->id, 'email' => $wife->email, 'role' => 'manager', 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDay()]);

        $this->actingAs($wife)->get(route('invitations.accept', $token))->assertRedirect('/');
        $this->assertDatabaseHas('space_user', ['space_id' => $space->id, 'user_id' => $wife->id, 'role' => 'manager']);
        $this->withSession(['space_id' => $space->id])->get(route('accounts.show', $private))->assertNotFound();
    }

    public function test_registered_user_receives_family_invitation_notification(): void
    {
        $owner = User::factory()->create(['name' => 'Ari']);
        $invitee = User::factory()->create(['email' => 'partner@example.com']);
        $space = Space::create(['owner_id' => $owner->id, 'name' => 'Keluarga Ari', 'type' => 'family']);
        $space->members()->attach($owner->id, ['role' => 'owner']);

        $this->actingAs($owner)->withSession(['space_id' => $space->id])->post(route('invitations.store'), [
            'email' => $invitee->email, 'role' => 'manager',
        ])->assertRedirect();

        $notification = $invitee->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('invitation', $notification->data['kind']);
        $this->assertStringContainsString('Keluarga Ari', $notification->data['message']);
    }

    public function test_family_member_is_notified_about_shared_transaction_but_not_private_transaction(): void
    {
        $owner = User::factory()->create(['name' => 'Ari']);
        $member = User::factory()->create(['name' => 'Dina']);
        $space = Space::create(['owner_id' => $owner->id, 'name' => 'Keluarga', 'type' => 'family']);
        $space->members()->attach([$owner->id => ['role' => 'owner'], $member->id => ['role' => 'contributor']]);
        $shared = Account::create(['user_id' => $owner->id, 'space_id' => $space->id, 'visibility' => 'shared', 'name' => 'Kas Keluarga', 'type' => 'cash', 'opening_balance' => 100000, 'current_balance' => 100000]);
        $private = Account::create(['user_id' => $owner->id, 'space_id' => $space->id, 'visibility' => 'personal', 'name' => 'Pribadi', 'type' => 'cash', 'opening_balance' => 100000, 'current_balance' => 100000]);

        $payload = ['type' => 'income', 'amount' => 50000, 'transacted_at' => now()->toDateString(), 'status' => 'paid'];
        $this->actingAs($owner)->withSession(['space_id' => $space->id])->post(route('transactions.store'), $payload + ['account_id' => $shared->id])->assertRedirect();
        $this->assertTrue($member->fresh()->notifications->contains(fn ($item) => $item->data['kind'] === 'transaction'));

        $member->notifications()->delete();
        $this->actingAs($owner)->withSession(['space_id' => $space->id])->post(route('transactions.store'), $payload + ['account_id' => $private->id])->assertRedirect();
        $this->assertCount(0, $member->fresh()->notifications);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->notify(new MoneyTrackNotification(['kind' => 'test', 'url' => route('dashboard')]));

        $this->actingAs($other)->post(route('notifications.read', $owner->notifications()->first()))->assertNotFound();
        $this->assertNull($owner->notifications()->first()->read_at);
    }

    public function test_unhealthy_cashflow_creates_alert_and_critical_dashboard_indicator(): void
    {
        $user = User::factory()->create();
        $space = $this->spaceFor($user);
        $account = Account::create(['user_id' => $user->id, 'space_id' => $space->id, 'visibility' => 'personal', 'name' => 'Kas', 'type' => 'cash', 'opening_balance' => 0, 'current_balance' => 0]);

        $this->actingAs($user)->withSession(['space_id' => $space->id])->post(route('transactions.store'), [
            'type' => 'expense', 'amount' => 50000, 'account_id' => $account->id,
            'transacted_at' => now()->toDateString(), 'status' => 'paid',
        ])->assertRedirect();

        $this->assertTrue($user->fresh()->notifications->contains(fn ($item) => $item->data['kind'] === 'financial_health'));
        $this->withSession(['space_id' => $space->id])->get(route('dashboard'))->assertOk()->assertSee('Perlu tindakan segera');
    }

    public function test_owner_can_edit_space_and_manage_member_role(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $space = Space::create(['owner_id' => $owner->id, 'name' => 'Keluarga Lama', 'type' => 'family']);
        $space->members()->attach([$owner->id => ['role' => 'owner'], $member->id => ['role' => 'contributor']]);

        $this->actingAs($owner)->withSession(['space_id' => $space->id])->put(route('spaces.update', $space), ['name' => 'Keluarga Baru', 'color' => '#123456'])->assertRedirect();
        $this->put(route('spaces.members.update', [$space, $member->id]), ['role' => 'manager'])->assertRedirect();

        $this->assertDatabaseHas('spaces', ['id' => $space->id, 'name' => 'Keluarga Baru', 'color' => '#123456']);
        $this->assertDatabaseHas('space_user', ['space_id' => $space->id, 'user_id' => $member->id, 'role' => 'manager']);
    }

    public function test_owner_can_delete_family_space_and_its_financial_data(): void
    {
        $owner = User::factory()->create();
        $space = Space::create(['owner_id' => $owner->id, 'name' => 'Ruang Dihapus', 'type' => 'family']);
        $space->members()->attach($owner->id, ['role' => 'owner']);
        $account = Account::create(['user_id' => $owner->id, 'space_id' => $space->id, 'name' => 'Kas', 'type' => 'cash']);

        $this->actingAs($owner)->withSession(['space_id' => $space->id])->delete(route('spaces.destroy', $space), ['space_name' => $space->name])->assertRedirect('/');

        $this->assertDatabaseMissing('spaces', ['id' => $space->id]);
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    public function test_formatted_money_is_normalized_before_validation(): void
    {
        $user = User::factory()->create();
        $space = $this->spaceFor($user);
        $account = Account::create(['user_id' => $user->id, 'space_id' => $space->id, 'name' => 'Kas', 'type' => 'cash']);

        $this->actingAs($user)->withSession(['space_id' => $space->id])->post(route('transactions.store'), [
            'type' => 'income', 'amount' => '1.250.000', 'account_id' => $account->id,
            'transacted_at' => now()->toDateString(), 'status' => 'paid',
        ])->assertRedirect();

        $this->assertDatabaseHas('transactions', ['space_id' => $space->id, 'amount' => 1250000]);
        $this->assertSame('1250000.00', $account->refresh()->current_balance);
    }

    public function test_opening_notification_switches_to_its_space_and_deep_link(): void
    {
        $user = User::factory()->create();
        $personal = $this->spaceFor($user);
        $family = Space::create(['owner_id' => $user->id, 'name' => 'Keluarga', 'type' => 'family']);
        $family->members()->attach($user->id, ['role' => 'owner']);
        $url = route('transactions.index', ['highlight' => 99]);
        $user->notify(new MoneyTrackNotification(['kind' => 'transaction', 'space_id' => $family->id, 'url' => $url]));

        $notification = $user->notifications()->first();
        $this->actingAs($user)->withSession(['space_id' => $personal->id])->post(route('notifications.read', $notification))->assertRedirect($url)->assertSessionHas('space_id', $family->id);
        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_family_data_changes_increment_shared_sync_version(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $space = Space::create(['owner_id' => $owner->id, 'name' => 'Keluarga', 'type' => 'family']);
        $space->members()->attach([$owner->id => ['role' => 'owner'], $member->id => ['role' => 'contributor']]);
        $account = Account::create(['user_id' => $owner->id, 'space_id' => $space->id, 'visibility' => 'shared', 'name' => 'Kas Bersama', 'type' => 'cash']);
        $versionAfterAccount = $space->fresh()->sync_version;

        app(TransactionService::class)->create($this->transaction($account, 'income', 250000), $owner->id, $space->id);

        $currentVersion = $space->fresh()->sync_version;
        $this->assertGreaterThan($versionAfterAccount, $currentVersion);
        $this->actingAs($member)->withSession(['space_id' => $space->id])->get(route('spaces.sync'))->assertOk()->assertJson([
            'space_id' => $space->id, 'version' => $currentVersion, 'family' => true,
        ]);
        $this->get(route('accounts.index'))->assertOk()->assertSee('Kas Bersama')->assertSee('250.000');
    }

    public function test_family_budget_created_by_owner_is_visible_on_member_dashboard(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $space = Space::create(['owner_id' => $owner->id, 'name' => 'Keluarga', 'type' => 'family']);
        $space->members()->attach([$owner->id => ['role' => 'owner'], $member->id => ['role' => 'contributor']]);
        Account::create(['user_id' => $owner->id, 'space_id' => $space->id, 'visibility' => 'shared', 'name' => 'Kas', 'type' => 'cash']);
        $category = Category::create(['user_id' => $owner->id, 'space_id' => $space->id, 'name' => 'Belanja Keluarga', 'type' => 'expense']);
        Budget::create(['user_id' => $owner->id, 'space_id' => $space->id, 'category_id' => $category->id, 'month' => now()->startOfMonth(), 'limit_amount' => 1000000]);

        $this->actingAs($member)->withSession(['space_id' => $space->id])->get(route('dashboard'))->assertOk()->assertSee('Belanja Keluarga')->assertSee('data-sync-version');
        $this->get(route('categories.index'))->assertOk()->assertSee('Belanja Keluarga')->assertSee('Kategori bersama');
        $this->post(route('categories.store'), ['name' => 'Tidak Diizinkan', 'type' => 'expense', 'color' => '#123456'])->assertForbidden();
    }

    public function test_personal_and_family_dashboards_and_transactions_are_strictly_separated(): void
    {
        $user = User::factory()->create();
        $personal = $this->spaceFor($user);
        $family = Space::create(['owner_id' => $user->id, 'name' => 'Keluarga', 'type' => 'family']);
        $family->members()->attach($user->id, ['role' => 'owner']);
        $personalAccount = Account::create(['user_id' => $user->id, 'space_id' => $personal->id, 'visibility' => 'personal', 'name' => 'Tabungan Pribadi', 'type' => 'bank']);
        $familyAccount = Account::create(['user_id' => $user->id, 'space_id' => $family->id, 'visibility' => 'shared', 'name' => 'Kas Keluarga', 'type' => 'cash']);
        app(TransactionService::class)->create($this->transaction($personalAccount, 'income', 100000) + ['description' => 'Pendapatan Pribadi'], $user->id, $personal->id);
        app(TransactionService::class)->create($this->transaction($familyAccount, 'income', 200000) + ['description' => 'Pendapatan Keluarga'], $user->id, $family->id);

        $this->actingAs($user)->withSession(['space_id' => $family->id])->get(route('dashboard'))->assertOk()->assertSee('Ringkasan Keluarga')->assertSee('Kas Keluarga')->assertDontSee('Tabungan Pribadi');
        $this->get(route('transactions.index'))->assertOk()->assertSee('Pendapatan Keluarga')->assertDontSee('Pendapatan Pribadi');

        $this->withSession(['space_id' => $personal->id])->get(route('dashboard'))->assertOk()->assertSee('Ringkasan Pribadi')->assertSee('Tabungan Pribadi')->assertDontSee('Kas Keluarga');
        $this->get(route('transactions.index'))->assertOk()->assertSee('Pendapatan Pribadi')->assertDontSee('Pendapatan Keluarga');
    }

    public function test_monthly_report_ranks_wasteful_and_frequent_categories_and_supports_custom_period(): void
    {
        $user = User::factory()->create();
        $space = $this->spaceFor($user);
        $account = Account::create(['user_id' => $user->id, 'space_id' => $space->id, 'visibility' => 'personal', 'name' => 'Kas', 'type' => 'cash']);
        $food = Category::create(['user_id' => $user->id, 'space_id' => $space->id, 'name' => 'Makan di Luar', 'type' => 'expense']);
        $other = Category::create(['user_id' => $user->id, 'space_id' => $space->id, 'name' => 'Belanja Lain', 'type' => 'expense']);
        $service = app(TransactionService::class);
        $service->create($this->transaction($account, 'income', 1000000) + ['description' => 'Gaji'], $user->id, $space->id);
        foreach (range(1, 5) as $day) {
            $data = $this->transaction($account, 'expense', 100000, $food);
            $data['transacted_at'] = now()->startOfMonth()->addDays($day);
            $data['description'] = 'Makan '.$day;
            $service->create($data, $user->id, $space->id);
        }
        foreach (range(1, 2) as $day) {
            $data = $this->transaction($account, 'expense', 50000, $other);
            $data['transacted_at'] = now()->startOfMonth()->addDays($day + 7);
            $service->create($data, $user->id, $space->id);
        }
        $old = $this->transaction($account, 'expense', 300000, $other);
        $old['transacted_at'] = now()->subMonthNoOverflow()->startOfMonth()->addDays(2);
        $old['description'] = 'Pengeluaran Lama';
        $service->create($old, $user->id, $space->id);

        $this->actingAs($user)->withSession(['space_id' => $space->id])->get(route('reports.index'))->assertOk()
            ->assertSee('Makan di Luar')->assertSee('Rp 500.000')->assertSee('5×')
            ->assertSee('Pengeluaran terkonsentrasi')->assertSee('Transaksi terlalu sering')->assertDontSee('Pengeluaran Lama');

        $this->get(route('reports.index', ['period' => 'custom', 'from' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(), 'to' => now()->subMonthNoOverflow()->endOfMonth()->toDateString()]))
            ->assertOk()->assertSee('Pengeluaran Lama')->assertSee('Rentang kustom');

        $pdf = $this->get(route('reports.pdf'))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
        $this->assertStringContainsString('laporan-moneytrack-', $pdf->headers->get('content-disposition'));
    }

    public function test_monthly_closing_saves_snapshot_and_locks_then_reopens_period(): void
    {
        $user = User::factory()->create();
        $space = $this->spaceFor($user);
        $account = Account::create(['user_id' => $user->id, 'space_id' => $space->id, 'visibility' => 'personal', 'name' => 'Kas', 'type' => 'cash']);

        $this->actingAs($user)->withSession(['space_id' => $space->id])->post(route('closings.store'), ['month' => now()->format('Y-m'), 'notes' => 'Final'])->assertRedirect();
        $closing = MonthlyClosing::first();
        $this->assertNotNull($closing);
        $this->assertArrayHasKey('balance', $closing->snapshot);

        $payload = ['type' => 'income', 'amount' => 100000, 'account_id' => $account->id, 'transacted_at' => now()->toDateString(), 'status' => 'paid'];
        $this->post(route('transactions.store'), $payload)->assertSessionHasErrors('transacted_at');
        $this->assertDatabaseCount('transactions', 0);

        $this->delete(route('closings.destroy', $closing))->assertRedirect();
        $this->post(route('transactions.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_family_goal_accepts_contributions_and_completes_for_all_members(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $space = Space::create(['owner_id' => $owner->id, 'name' => 'Keluarga', 'type' => 'family']);
        $space->members()->attach([$owner->id => ['role' => 'owner'], $member->id => ['role' => 'contributor']]);

        $this->actingAs($owner)->withSession(['space_id' => $space->id])->post(route('goals.store'), ['name' => 'Dana Darurat', 'target_amount' => '1.000.000', 'deadline' => now()->addMonth()->toDateString(), 'color' => '#087f70'])->assertRedirect();
        $goal = FinancialGoal::firstOrFail();
        $this->actingAs($member)->withSession(['space_id' => $space->id])->post(route('goals.contribute', $goal), ['amount' => '1.000.000', 'contributed_at' => now()->toDateString()])->assertRedirect();

        $this->assertSame('completed', $goal->refresh()->status);
        $this->assertSame('1000000.00', $goal->current_amount);
        $this->assertDatabaseHas('goal_contributions', ['financial_goal_id' => $goal->id, 'contributed_by' => $member->id, 'amount' => 1000000]);
        $this->assertTrue($owner->fresh()->notifications->contains(fn ($item) => $item->data['kind'] === 'goal'));
    }

    public function test_notification_preferences_suppress_disabled_family_activity(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $space = Space::create(['owner_id' => $owner->id, 'name' => 'Keluarga', 'type' => 'family']);
        $space->members()->attach([$owner->id => ['role' => 'owner'], $member->id => ['role' => 'contributor']]);
        $account = Account::create(['user_id' => $owner->id, 'space_id' => $space->id, 'visibility' => 'shared', 'name' => 'Kas', 'type' => 'cash']);

        $this->actingAs($member)->put(route('settings.notifications'), ['invitations' => '1'])->assertRedirect();
        $this->assertFalse($member->fresh()->wantsNotification('transactions'));

        $this->actingAs($owner)->withSession(['space_id' => $space->id])->post(route('transactions.store'), ['type' => 'income', 'amount' => 100000, 'account_id' => $account->id, 'transacted_at' => now()->toDateString(), 'status' => 'paid'])->assertRedirect();
        $this->assertFalse($member->fresh()->notifications->contains(fn ($item) => $item->data['kind'] === 'transaction'));
    }

    private function transaction(Account $account, string $type, int $amount, ?Category $category = null, ?Account $destination = null): array
    {
        return ['account_id' => $account->id, 'destination_account_id' => $destination?->id, 'category_id' => $category?->id, 'type' => $type, 'amount' => $amount, 'transacted_at' => now(), 'status' => 'paid', 'is_recurring' => false];
    }

    private function spaceFor(User $user): Space
    {
        $space = Space::create(['owner_id' => $user->id, 'name' => 'Pribadi', 'type' => 'personal']);
        $space->members()->attach($user->id, ['role' => 'owner']);

        return $space;
    }
}
