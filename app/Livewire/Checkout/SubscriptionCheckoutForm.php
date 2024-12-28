<?php

namespace App\Livewire\Checkout;

use App\Exceptions\LoginException;
use App\Exceptions\NoPaymentProvidersAvailableException;
use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Services\CheckoutManager;
use App\Services\DiscountManager;
use App\Services\LoginManager;
use App\Services\PaymentProviders\PaymentManager;
use App\Services\PlanManager;
use App\Services\SessionManager;
use App\Services\UserManager;
use App\Validator\LoginValidator;
use App\Validator\RegisterValidator;

class SubscriptionCheckoutForm extends CheckoutForm
{
    private PlanManager $planManager;
    private SessionManager $sessionManager;

    public function boot(PlanManager $planManager, SessionManager $sessionManager)
    {
        $this->planManager = $planManager;
        $this->sessionManager = $sessionManager;
    }

    public function checkout(
        LoginValidator $loginValidator,
        RegisterValidator $registerValidator,
        CheckoutManager $checkoutManager,
        PaymentManager $paymentManager,
        DiscountManager $discountManager,
        UserManager $userManager,
        LoginManager $loginManager,
    ) {
        try {
            parent::handleLoginOrRegistration($loginValidator, $registerValidator, $userManager, $loginManager);
        } catch (LoginException $exception) { // 2fa is enabled, user has to go through typical login flow to enter 2fa code
            return redirect()->route('login');
        }

        $subscriptionCheckoutDto = $this->sessionManager->getSubscriptionCheckoutDto();
        $planSlug = $subscriptionCheckoutDto->planSlug;

        $plan = $this->planManager->getActivePlanBySlug($planSlug);

        if ($plan === null) {
            return redirect()->route('home');
        }

        $paymentProvider = $paymentManager->getPaymentProviderBySlug(
            $this->paymentProvider
        );

        $user = auth()->user();

        $discount = null;
        if ($subscriptionCheckoutDto->discountCode !== null) {
            $discount = $discountManager->getActiveDiscountByCode($subscriptionCheckoutDto->discountCode);
            $plan = $this->planManager->getActivePlanBySlug($planSlug);

            if (! $discountManager->isCodeRedeemableForPlan($subscriptionCheckoutDto->discountCode, $user, $plan)) {
                // this is to handle the case when user adds discount code that has max redemption limit per customer,
                // then logs-in during the checkout process and the discount code is not valid anymore
                $subscriptionCheckoutDto->discountCode = null;
                $discount = null;
                $this->dispatch('calculations-updated')->to(SubscriptionTotals::class);
            }
        }

        try {
            $subscription = $checkoutManager->initSubscriptionCheckout($planSlug);
        } catch (SubscriptionCreationNotAllowedException $e) {
            return redirect()->route('checkout.subscription.already-subscribed');
        }

        $initData = $paymentProvider->initSubscriptionCheckout($plan, $subscription, $discount);

        $subscriptionCheckoutDto->subscriptionId = $subscription->id;
        $this->sessionManager->saveSubscriptionCheckoutDto($subscriptionCheckoutDto);

        if ($paymentProvider->isRedirectProvider()) {
            $link = $paymentProvider->createSubscriptionCheckoutRedirectLink(
                $plan,
                $subscription,
                $discount,
            );

            return redirect()->away($link);
        }

        $this->dispatch('start-overlay-checkout',
            paymentProvider: $paymentProvider->getSlug(),
            initData: $initData,
            successUrl: route('checkout.subscription.success'),
            email: $user->email,
            subscriptionUuid: $subscription->uuid,
        );
    }

    protected function getPaymentProviders(PaymentManager $paymentManager)
    {
        if (count($this->paymentProviders) > 0) {
            return $this->paymentProviders;
        }

        $subscriptionCheckoutDto = $this->sessionManager->getSubscriptionCheckoutDto();
        $planSlug = $subscriptionCheckoutDto->planSlug;

        $plan = $this->planManager->getActivePlanBySlug($planSlug);

        $this->paymentProviders = $paymentManager->getActivePaymentProvidersForPlan($plan);

        if (empty($this->paymentProviders)) {
            logger()->error('No payment providers available for plan', [
                'plan' => $plan->slug,
            ]);

            throw new NoPaymentProvidersAvailableException('No payment providers available for plan' . $plan->slug);
        }

        if ($this->paymentProvider === null) {
            $this->paymentProvider = $this->paymentProviders[0]->getSlug();
        }

        return $this->paymentProviders;
    }
}
