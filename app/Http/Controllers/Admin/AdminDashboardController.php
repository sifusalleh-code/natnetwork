<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Billing\Models\BillingSetting;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', ['settings' => BillingSetting::current()]);
    }
}
