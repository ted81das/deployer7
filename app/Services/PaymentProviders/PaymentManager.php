<?php

namespace App\Services\PaymentProviders;

use App\Models\PaymentProvider;
use App\Models\Plan;

class PaymentManager
{
    private array $paymentProviders;

    public function __construct(PaymentProviderInterface ...$paymentProviders)
    {
        $this->paymentProviders = $paymentProviders;
    }

    public function getActivePaymentProviders(): array
    {
        $activePaymentProvidersMap = $this->getActivePaymentProvidersMap();

        foreach ($this->paymentProviders as $paymentProvider) {
            if (isset($activePaymentProvidersMap[$paymentProvider->getSlug()])) {
                $paymentProviders[] = $paymentProvider;
            }
        }

        return $paymentProviders;
    }

    public function getActivePaymentProvidersForPlan(Plan $plan): array
    {
        $activePaymentProvidersMap = $this->getActivePaymentProvidersMap();

        $paymentProviders = [];
        foreach ($this->paymentProviders as $paymentProvider) {
            if (isset($activePaymentProvidersMap[$paymentProvider->getSlug()]) &&
                in_array($plan->type, $paymentProvider->getSupportedPlanTypes())
            ) {
                $paymentProviders[] = $paymentProvider;
            }
        }

        return $paymentProviders;
    }

    public function getPaymentProviderBySlug(string $slug): PaymentProviderInterface
    {
        $activePaymentProvidersMap = $this->getActivePaymentProvidersMap();

        foreach ($this->paymentProviders as $paymentProvider) {
            if (isset($activePaymentProvidersMap[$paymentProvider->getSlug()])) {
                if ($paymentProvider->getSlug() === $slug) {
                    return $paymentProvider;
                }
            }
        }

        throw new \Exception('Payment provider not found: '.$slug);
    }

    private function getActivePaymentProvidersMap(): array
    {
        $activePaymentProviders = PaymentProvider::where('is_active', true)->get();

        $activePaymentProvidersMap = [];

        foreach ($activePaymentProviders as $activePaymentProvider) {
            $activePaymentProvidersMap[$activePaymentProvider->slug] = $activePaymentProvider;
        }

        return $activePaymentProvidersMap;
    }
}
