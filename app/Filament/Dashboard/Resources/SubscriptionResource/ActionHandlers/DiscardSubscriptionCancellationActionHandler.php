<?php

namespace App\Filament\Dashboard\Resources\SubscriptionResource\ActionHandlers;

use App\Filament\Dashboard\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Services\PaymentProviders\PaymentManager;
use App\Services\SubscriptionManager;
use Filament\Notifications\Notification;

class DiscardSubscriptionCancellationActionHandler
{
    public function __construct(
        private SubscriptionManager $subscriptionManager,
        private PaymentManager $paymentManager
    ) {

    }

    public function handle(Subscription $record)
    {
        $user = auth()->user();

        $userSubscription = $this->subscriptionManager->findActiveByUserAndSubscriptionUuid($user->id, $record->uuid);

        if (! $userSubscription) {
            Notification::make()
                ->title(__('Error canceling subscription'))
                ->danger()
                ->send();

            return redirect()->to(SubscriptionResource::getUrl());
        }

        $paymentProvider = $userSubscription->paymentProvider()->first();

        $paymentProviderStrategy = $this->paymentManager->getPaymentProviderBySlug(
            $paymentProvider->slug
        );

        $this->subscriptionManager->discardSubscriptionCancellation($userSubscription, $paymentProviderStrategy);

        Notification::make()
            ->title(__('Subscription cancellation discarded'))
            ->success()
            ->send();

        return redirect()->to(SubscriptionResource::getUrl());
    }
}
