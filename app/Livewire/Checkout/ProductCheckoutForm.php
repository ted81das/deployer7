<?php

namespace App\Livewire\Checkout;

use App\Exceptions\LoginException;
use App\Services\CheckoutManager;
use App\Services\DiscountManager;
use App\Services\LoginManager;
use App\Services\OneTimeProductManager;
use App\Services\PaymentProviders\PaymentManager;
use App\Services\SessionManager;
use App\Services\UserManager;
use App\Validator\LoginValidator;
use App\Validator\RegisterValidator;

class ProductCheckoutForm extends CheckoutForm
{
    public function checkout(
        LoginValidator $loginValidator,
        RegisterValidator $registerValidator,
        CheckoutManager $checkoutManager,
        PaymentManager $paymentManager,
        DiscountManager $discountManager,
        UserManager $userManager,
        OneTimeProductManager $oneTimeProductManager,
        SessionManager $sessionManager,
        LoginManager $loginManager,
    ) {
        try {
            parent::handleLoginOrRegistration($loginValidator, $registerValidator, $userManager, $loginManager);
        } catch (LoginException $exception) { // 2fa is enabled, user has to go through typical login flow to enter 2fa code
            return redirect()->route('login');
        }

        $cartDto = $sessionManager->getCartDto();

        $order = $checkoutManager->initProductCheckout($cartDto);

        $cartDto->orderId = $order->id;

        $paymentProvider = $paymentManager->getPaymentProviderBySlug(
            $this->paymentProvider
        );

        $discount = null;
        if ($cartDto->discountCode !== null) {
            $discount = $discountManager->getActiveDiscountByCode($cartDto->discountCode);
            $product = $oneTimeProductManager->getOneTimeProductById($cartDto->items[0]->productId);

            if (! $discountManager->isCodeRedeemableForOneTimeProduct($cartDto->discountCode, auth()->user(), $product)) {
                // this is to handle the case when user adds discount code that has max redemption limit per customer,
                // then logs-in during the checkout process and the discount code is not valid anymore
                $cartDto->discountCode = null;
                $discount = null;
                $this->dispatch('calculations-updated')->to(ProductTotals::class);
            }
        }

        $initData = $paymentProvider->initProductCheckout($order, $discount);

        $sessionManager->saveCartDto($cartDto);

        $user = auth()->user();

        if ($paymentProvider->isRedirectProvider()) {
            $link = $paymentProvider->createProductCheckoutRedirectLink(
                $order,
                $discount,
            );

            return redirect()->away($link);
        }

        $this->dispatch('start-overlay-checkout',
            paymentProvider: $paymentProvider->getSlug(),
            initData: $initData,
            successUrl: route('checkout.product.success'),
            email: $user->email,
            orderUuid: $order->uuid,
        );
    }
}
