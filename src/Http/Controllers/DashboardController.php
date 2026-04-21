<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(): View
    {
        return view('ui-manager::dashboard', [
            'config' => [
                'title'       => config('ui-manager.dashboard.title', 'UI Manager'),
                'homeButton'  => config('ui-manager.dashboard.home_button'),
                'apiBase'     => url(config('ui-manager.routes.api_prefix', 'ui-manager/api')),
                'assetsUrl'   => rtrim(config('ui-manager.assets_url', asset('vendor/ui-manager')), '/'),
            ],
        ]);
    }
}
