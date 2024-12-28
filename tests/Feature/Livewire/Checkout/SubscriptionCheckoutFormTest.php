<?php

namespace Tests\Feature\Livewire\Checkout;

use App\Constants\PlanPriceType;
use App\Constants\PlanType;
use App\Constants\SessionConstants;
use App\Dto\SubscriptionCheckoutDto;
use App\Livewire\Checkout\SubscriptionCheckoutForm;
use App\Models\Currency;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentProviders\PaymentManager;
use App\Services\PaymentProviders\PaymentProviderInterface;
use Illuminate\View\ViewException;
use Livewire\Livewire;
use Tests\Feature\FeatureTest;

class SubscriptionCheckoutFormTest extends FeatureTest
{
    public function test_can_checkout_new_user()
    {
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = 'plan-slug-5';

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => 'plan-slug-5',
            'is_active' => true,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('getSupportedPlanTypes')
            ->andReturn([
                PlanType::USAGE_BASED->value,
                PlanType::FLAT_RATE->value,
            ]);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', 'something+sub1@gmail.com')
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => 'something+sub1@gmail.com',
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());
    }

    public function test_can_checkout_existing_user()
    {
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = 'plan-slug-6';

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => 'plan-slug-6',
            'is_active' => true,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $user = User::factory()->create([
            'email' => 'existing+sub1@gmail.com',
            'password' => bcrypt('password'),
            'name' => 'Name',
        ]);

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('getSupportedPlanTypes')
            ->andReturn([
                PlanType::USAGE_BASED->value,
                PlanType::FLAT_RATE->value,
            ]);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', 'existing+sub1@gmail.com')
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => 'existing+sub1@gmail.com',
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());
    }

    public function test_can_not_checkout_if_payment_does_not_support_plan_type()
    {
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = 'plan-slug-9';

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => 'plan-slug-9',
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

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('getSupportedPlanTypes')
            ->andReturn([
                PlanType::FLAT_RATE->value,
            ]);

        $paymentProvider->shouldNotReceive('initSubscriptionCheckout');

        $this->expectException(ViewException::class);

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', 'existing+sub2@gmail.com')
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout');
    }

    public function test_checkout_success_if_plan_type_is_usage_based()
    {
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = 'plan-slug-10';

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => 'plan-slug-10',
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

        $paymentProvider = $this->addPaymentProvider();

        $paymentProvider->shouldReceive('getSupportedPlanTypes')
            ->andReturn([
                PlanType::USAGE_BASED->value,
            ]);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', 'existing+sub3@gmail.com')
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertRedirect('http://paymore.com/checkout');

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => 'existing+sub3@gmail.com',
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());
    }

    public function test_can_checkout_overlay_payment()
    {
        $sessionDto = new SubscriptionCheckoutDto;
        $sessionDto->planSlug = 'plan-slug-7';

        $this->withSession([SessionConstants::SUBSCRIPTION_CHECKOUT_DTO => $sessionDto]);

        $plan = Plan::factory()->create([
            'slug' => 'plan-slug-7',
            'is_active' => true,
        ]);

        PlanPrice::create([
            'plan_id' => $plan->id,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
            'price' => 100,
        ]);

        $paymentProvider = $this->addPaymentProvider(false);

        $paymentProvider->shouldReceive('getSupportedPlanTypes')
            ->andReturn([
                PlanType::USAGE_BASED->value,
                PlanType::FLAT_RATE->value,
            ]);

        $paymentProvider->shouldReceive('initSubscriptionCheckout')
            ->once()
            ->andReturn([]);

        // get number of subscriptions before checkout
        $subscriptionsBefore = Subscription::count();

        Livewire::test(SubscriptionCheckoutForm::class)
            ->set('name', 'Name')
            ->set('email', 'something+sub2@gmail.com')
            ->set('password', 'password')
            ->set('paymentProvider', 'paymore')
            ->call('checkout')
            ->assertDispatched('start-overlay-checkout');

        // assert user has been created
        $this->assertDatabaseHas('users', [
            'email' => 'something+sub2@gmail.com',
        ]);

        // assert user is logged in
        $this->assertAuthenticated();

        // assert order has been created
        $this->assertEquals($subscriptionsBefore + 1, Subscription::count());
    }

    private function addPaymentProvider(bool $isRedirect = true)
    {
        // find or create payment provider
        PaymentProvider::updateOrCreate([
            'slug' => 'paymore',
        ], [
            'name' => 'Paymore',
            'is_active' => true,
            'type' => 'any',
        ]);

        $mock = \Mockery::mock(PaymentProviderInterface::class);

        $mock->shouldReceive('isRedirectProvider')
            ->andReturn($isRedirect);

        $mock->shouldReceive('getSlug')
            ->andReturn('paymore');

        $mock->shouldReceive('getName')
            ->andReturn('Paymore');

        $mock->shouldReceive('isOverlayProvider')
            ->andReturn(! $isRedirect);

        if ($isRedirect) {
            $mock->shouldReceive('createSubscriptionCheckoutRedirectLink')
                ->andReturn('http://paymore.com/checkout');
        }

        $this->app->instance(PaymentProviderInterface::class, $mock);

        $this->app->bind(PaymentManager::class, function () use ($mock) {
            return new PaymentManager($mock);
        });

        return $mock;
    }
}
