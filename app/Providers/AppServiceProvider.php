<?php

namespace App\Providers;

use App\Domain\QuestionSelector;
use App\Models\PlayerPreference;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QuestionSelector::class, fn () => new QuestionSelector);
    }

    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');

        // Dados comuns a todas as telas: usuário, preferências de acessibilidade e token da API.
        View::composer('*', function ($view) {
            $attrs = request()->attributes;
            if (! $attrs->has('view_shared')) {
                $me = Auth::user();
                $attrs->set('view_shared', [
                    'me' => $me,
                    'prefs' => $me ? $me->prefs() : new PlayerPreference(PlayerPreference::DEFAULTS),
                    'apiToken' => session('api_token'),
                ]);
            }
            $view->with($attrs->get('view_shared'));
        });
    }
}
