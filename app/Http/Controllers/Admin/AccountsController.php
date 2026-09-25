<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Affiliate\Models\Affiliate;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountsController extends Controller
{
    public function clients(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        return view('admin.accounts.clients', [
            'users' => User::query()->when($q, fn ($s) => $s->where(fn ($s) => $s->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))->latest('id')->paginate(30)->withQueryString(),
            'q' => $q,
        ]);
    }

    public function affiliates(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        return view('admin.accounts.affiliates', [
            'affiliates' => Affiliate::query()->when($q, fn ($s) => $s->where(fn ($s) => $s->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")->orWhere('username', 'like', "%$q%")))->latest('id')->paginate(30)->withQueryString(),
            'q' => $q,
        ]);
    }
}
