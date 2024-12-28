<?php

namespace App\Providers;

use App\Models\User;
use App\Services\OrderManager;
use App\Services\SubscriptionManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Blade::if('subscribed', function (?string $productSlug = null) {
            /** @var User $user */
            $user = auth()->user();

            /** @var SubscriptionManager $subscriptionManager */
            $subscriptionManager = app(SubscriptionManager::class);

            return $subscriptionManager->isUserSubscribed($user, $productSlug);
        });

        Blade::if('notsubscribed', function (?string $productSlug = null) {
            /** @var User $user */
            $user = auth()->user();

            /** @var SubscriptionManager $subscriptionManager */
            $subscriptionManager = app(SubscriptionManager::class);

            return ! $subscriptionManager->isUserSubscribed($user, $productSlug);
        });

        Blade::if('trialing', function (?string $productSlug = null) {
            /** @var User $user */
            $user = auth()->user();

            /** @var SubscriptionManager $subscriptionManager */
            $subscriptionManager = app(SubscriptionManager::class);

            return $subscriptionManager->isUserTrialing($user, $productSlug);
        });

        Blade::if('purchased', function (?string $productSlug = null) {
            /** @var User $user */
            $user = auth()->user();

            /** @var OrderManager $orderManager */
            $orderManager = app(OrderManager::class);

            return $orderManager->hasUserOrdered($user, $productSlug);
        });
    }
}
