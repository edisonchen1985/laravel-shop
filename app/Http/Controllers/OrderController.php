<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = request()->user();

        $orders = $user->orders()
            ->with('items')
            ->latest('created_at')
            ->latest('id')
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }
}
