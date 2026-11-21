<?php

namespace App\Providers;

use App\Models\Category;
use App\Support\Cart;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Один кошик на запит
        $this->app->scoped(Cart::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Категорії для шапки, меню й підвалу — на кожній сторінці вітрини
        \Illuminate\Support\Facades\View::composer('components.layouts.app', function (View $view): void {
            $view->with('navCategories', Category::query()
                ->active()->root()->orderBy('sort')
                ->with(['children' => fn ($q) => $q->active()])
                ->get());
        });
    }
}
