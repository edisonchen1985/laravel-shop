<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->user_id !== null
            && (string) $order->user_id === (string) $user->getKey();
    }
}
