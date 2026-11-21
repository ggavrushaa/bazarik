<?php

namespace App\Providers;

use App\Models\Category;
use App\Support\Cart;
use App\Support\Sms\LogSmsSender;
use App\Support\Sms\SmsSender;
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

        // SMS: поки без провайдера (TurboSMS / eSputnik — на погодженні), локально — у лог
        $this->app->bind(SmsSender::class, LogSmsSender::class);
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
