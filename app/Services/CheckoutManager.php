<?php

namespace App\Services;

use App\Constants\OrderStatus;
use App\Dto\CartDto;

class CheckoutManager
{
    public function __construct(
        private SubscriptionManager $subscriptionManager,
        private OrderManager $orderManager,
    ) {

    }

    public function initSubscriptionCheckout(string $planSlug)
    {
        $subscription = $this->subscriptionManager->findNewByPlanSlugAndUser($planSlug, auth()->id());
        if ($subscription === null) {
            $subscription = $this->subscriptionManager->create($planSlug, auth()->id());
        }

        return $subscription;
    }

    public function initProductCheckout(CartDto $cartDto)
    {
        $user = auth()->user();

        $order = null;
        if ($cartDto->orderId !== null) {
            $order = $this->orderManager->findNewByIdForUser($cartDto->orderId, $user);
        }

        if ($order === null) {
            $order = $this->orderManager->create($user);
        }

        $this->orderManager->refreshOrder($cartDto, $order);

        $order->status = OrderStatus::PENDING->value;
        $order->save();

        return $order;
    }
}
