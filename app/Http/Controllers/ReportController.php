<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ReportController extends Controller
{
    public function dailyActivity()
    {
        return Inertia::render('Report/DailyActivity/Index');
    }

    public function managerReport()
    {
        return Inertia::render('Report/ManagerReport/Index');
    }

    public function revenueReport()
    {
        return Inertia::render('Report/RevenueReport/Index');
    }
    public function paymentReport()
    {
        return Inertia::render('Report/PaymentReport/Index');
    }
}
