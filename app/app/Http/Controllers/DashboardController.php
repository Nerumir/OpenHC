<?php

namespace App\Http\Controllers;

use App\Models\AdminPortal;
use App\Models\Service;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'services' => Service::with('latestCheck')->orderBy('display_name')->get(),
            'portals' => AdminPortal::orderBy('display_name')->get(),
        ]);
    }
}
