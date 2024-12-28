<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class SubscriptionCreationNotAllowedException extends Exception
{
    public function render()
    {
        return view('checkout.already-subscribed');
    }
}
