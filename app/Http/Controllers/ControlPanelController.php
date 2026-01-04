<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ControlPanelController extends Controller
{
    public function index()
    {
        return Inertia::render('ControlPanel/Index');
    }

    public function system()
    {
        return Inertia::render('ControlPanel/System', [
            'system' => [
                'app_env'         => config('app.env'),
                'app_debug'       => (bool) config('app.debug'),
                'php_version'     => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
        ]);
    }

    public function features()
    {
        return Inertia::render('ControlPanel/Features', [
            'flags' => [
                'feature.notifications' => ['enabled' => true],
                'feature.housekeeping'  => ['enabled' => true],
                'feature.api_logs'      => ['enabled' => true],
            ],
        ]);
    }

    public function links()
    {
        return Inertia::render('ControlPanel/Links', [
            'links' => [
                ['title' => 'API Logs', 'routeName' => 'api.logs.index'],
            ],
        ]);
    }
}
