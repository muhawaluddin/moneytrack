<?php

namespace App\Providers;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\FinancialGoal;
use App\Models\GoalContribution;
use App\Models\MonthlyClosing;
use App\Models\Transaction;
use App\Observers\SpaceDataObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Account::observe(SpaceDataObserver::class);
        Budget::observe(SpaceDataObserver::class);
        Category::observe(SpaceDataObserver::class);
        FinancialGoal::observe(SpaceDataObserver::class);
        GoalContribution::observe(SpaceDataObserver::class);
        MonthlyClosing::observe(SpaceDataObserver::class);
        Transaction::observe(SpaceDataObserver::class);
    }
}
