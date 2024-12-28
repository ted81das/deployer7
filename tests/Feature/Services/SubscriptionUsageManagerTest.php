<?php

namespace Tests\Feature\Services;

use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Constants\SubscriptionStatus;
use App\Models\Currency;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Services\PaymentProviders\PaymentManager;
use App\Services\PaymentProviders\PaymentProviderInterface;
use App\Services\SubscriptionManager;
use App\Services\SubscriptionUsageManager;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class SubscriptionUsageManagerTest extends FeatureTest
{
    public function test_report_usage()
    {
        $paymentManager = $this->createMock(PaymentManager::class);
        $subscriptionManager = $this->createMock(SubscriptionManager::class);
        $subscriptionUsageManager = new SubscriptionUsageManager($paymentManager, $subscriptionManager);

        $paymentProviderModel = PaymentProvider::updateOrCreate([
            'slug' => 'paymore-'.Str::random(10),
        ], [
            'name' => 'Paymore',
            'is_active' => true,
            'type' => 'any',
        ]);

        $paymentProvider = \Mockery::mock(PaymentProviderInterface::class);

        $this->app->instance(PaymentProviderInterface::class, $paymentProvider);

        $this->app->bind(PaymentManager::class, function () use ($paymentProvider) {
            return new PaymentManager($paymentProvider);
        });

        $plan = Plan::factory()->create([
            'slug' => 'plan-slug-12',
            'is_active' => true,
            'type' => PlanType::USAGE_BASED->value,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
            'price_per_unit' => 20,
            'type' => PlanPriceType::USAGE_BASED_PER_UNIT->value,
        ]);

        $subscription = Subscription::factory()->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'payment_provider_id' => $paymentProviderModel->id,
        ]);

        $paymentProvider->expects('reportUsage')
            ->with($subscription, 10)
            ->andReturn(true);

        $paymentManager->method('getPaymentProviderBySlug')->willReturn($paymentProvider);

        $result = $subscriptionUsageManager->reportUsage(10, $subscription);

        $this->assertTrue($result);
        $this->assertDatabaseHas('subscription_usages', [
            'subscription_id' => $subscription->id,
            'unit_count' => 10,
        ]);
    }
}
