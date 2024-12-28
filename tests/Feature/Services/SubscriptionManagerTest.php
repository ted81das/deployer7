<?php

namespace Tests\Feature\Services;

use App\Constants\SubscriptionStatus;
use App\Events\Subscription\Subscribed;
use App\Events\Subscription\SubscriptionCancelled;
use App\Events\Subscription\SubscriptionRenewed;
use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Models\Currency;
use App\Models\Interval;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Feature\FeatureTest;

class SubscriptionManagerTest extends FeatureTest
{
    /**
     * @dataProvider nonDeadSubscriptionProvider
     */
    public function test_can_only_create_subscription_if_no_other_non_dead_subscription_exists($status)
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => $status,
            'plan_id' => $plan->id,
        ])->save();

        $manager = app()->make(SubscriptionManager::class);

        $this->expectException(SubscriptionCreationNotAllowedException::class);
        $manager->create($slug, $user->id);
    }

    public function test_calculate_subscription_trial_days()
    {
        $manager = app()->make(SubscriptionManager::class);

        $plan = Plan::factory()->create([
            'slug' => Str::random(),
            'has_trial' => true,
            'trial_interval_count' => 1,
            'trial_interval_id' => Interval::where('slug', 'day')->first()->id,
        ]);

        $this->assertEquals(1, $manager->calculateSubscriptionTrialDays($plan));

        $plan = Plan::factory()->create([
            'slug' => Str::random(),
            'has_trial' => true,
            'trial_interval_count' => 1,
            'trial_interval_id' => Interval::where('slug', 'week')->first()->id,
        ]);

        $this->assertEquals(7, $manager->calculateSubscriptionTrialDays($plan));

        $plan = Plan::factory()->create([
            'slug' => Str::random(),
            'has_trial' => true,
            'trial_interval_count' => 2,
            'trial_interval_id' => Interval::where('slug', 'week')->first()->id,
        ]);

        $this->assertEquals(14, $manager->calculateSubscriptionTrialDays($plan));

        $plan = Plan::factory()->create([
            'slug' => Str::random(),
            'has_trial' => true,
            'trial_interval_count' => 1,
            'trial_interval_id' => Interval::where('slug', 'month')->first()->id,
        ]);

        $this->assertContains($manager->calculateSubscriptionTrialDays($plan), [28, 29, 30, 31]);

        $plan = Plan::factory()->create([
            'slug' => Str::random(),
            'has_trial' => true,
            'trial_interval_count' => 1,
            'trial_interval_id' => Interval::where('slug', 'year')->first()->id,
        ]);

        $this->assertContains($manager->calculateSubscriptionTrialDays($plan), [365, 366]);
    }

    public function test_can_create_subscription_multiple_subscriptions_are_enabled()
    {
        config()->set('app.multiple_subscriptions_enabled', true);
        $user = $this->createUser();
        $this->actingAs($user);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        // add a plan price
        $plan->prices()->create([
            'price' => 1000,
            'currency_id' => Currency::where('code', 'USD')->first()->id,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'plan_id' => $plan->id,
        ])->save();

        $manager = app()->make(SubscriptionManager::class);

        $subscription = $manager->create($slug, $user->id);

        $this->assertNotNull($subscription);
    }

    public function test_update_subscription_dispatches_subscribed_event()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::PENDING->value,
            'plan_id' => $plan->id,
        ]);

        /** @var SubscriptionManager $manager */
        $manager = app()->make(SubscriptionManager::class);

        Event::fake();

        $subscription = $manager->updateSubscription($subscription, [
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);

        Event::assertDispatched(Subscribed::class);
    }

    public function test_update_subscription_dispatches_canceled_event()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
        ]);

        /** @var SubscriptionManager $manager */
        $manager = app()->make(SubscriptionManager::class);

        Event::fake();

        $subscription = $manager->updateSubscription($subscription, [
            'status' => SubscriptionStatus::CANCELED->value,
        ]);

        Event::assertDispatched(SubscriptionCancelled::class);
    }

    public function test_update_subscription_dispatches_renewed_event()
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $slug = Str::random();
        $plan = Plan::factory()->create([
            'slug' => $slug,
            'is_active' => true,
        ]);

        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
            'plan_id' => $plan->id,
            'ends_at' => now(),
        ]);

        /** @var SubscriptionManager $manager */
        $manager = app()->make(SubscriptionManager::class);

        Event::fake();

        $subscription = $manager->updateSubscription($subscription, [
            'status' => SubscriptionStatus::ACTIVE->value,
            'ends_at' => now()->addDays(30),
        ]);

        Event::assertDispatched(SubscriptionRenewed::class);
    }

    public static function nonDeadSubscriptionProvider()
    {
        return [
            'pending' => [
                'pending',
            ],
            'active' => [
                'active',
            ],
            'paused' => [
                'paused',
            ],
            'past_due' => [
                'past_due',
            ],
        ];
    }
}
