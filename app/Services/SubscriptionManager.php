<?php

namespace App\Services;

use App\Constants\PaymentProviderConstants;
use App\Constants\PlanType;
use App\Constants\SubscriptionStatus;
use App\Events\Subscription\InvoicePaymentFailed;
use App\Events\Subscription\Subscribed;
use App\Events\Subscription\SubscriptionCancelled;
use App\Events\Subscription\SubscriptionRenewed;
use App\Exceptions\SubscriptionCreationNotAllowedException;
use App\Models\PaymentProvider;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentProviders\PaymentProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionManager
{
    public function __construct(
        private CalculationManager $calculationManager,
        private PlanManager $planManager,
    ) {

    }

    public function canCreateSubscription(int $userId): bool
    {
        if (config('app.multiple_subscriptions_enabled')) {
            return true;
        }

        $notDeadSubscriptions = $this->findAllSubscriptionsThatAreNotDead($userId);

        return count($notDeadSubscriptions) === 0;
    }

    public function create(string $planSlug, int $userId, ?PaymentProvider $paymentProvider = null, ?string $paymentProviderSubscriptionId = null): Subscription
    {
        $plan = Plan::where('slug', $planSlug)->where('is_active', true)->firstOrFail();

        if (! $this->canCreateSubscription($userId)) {
            throw new SubscriptionCreationNotAllowedException(__('You already have subscription.'));
        }

        $newSubscription = null;
        DB::transaction(function () use ($plan, $userId, &$newSubscription, $paymentProvider, $paymentProviderSubscriptionId) {
            $this->deleteAllNewSubscriptions($userId);

            $planPrice = $this->calculationManager->getPlanPrice($plan);

            $subscriptionAttributes = [
                'uuid' => (string) Str::uuid(),
                'user_id' => $userId,
                'plan_id' => $plan->id,
                'price' => $planPrice->price,
                'currency_id' => $planPrice->currency_id,
                'status' => SubscriptionStatus::NEW->value,
                'interval_id' => $plan->interval_id,
                'interval_count' => $plan->interval_count,
                'price_type' => $planPrice->type,
                'price_tiers' => $planPrice->tiers,
                'price_per_unit' => $planPrice->price_per_unit,
            ];

            if ($paymentProvider) {
                $subscriptionAttributes['payment_provider_id'] = $paymentProvider->id;
            }

            if ($paymentProviderSubscriptionId) {
                $subscriptionAttributes['payment_provider_subscription_id'] = $paymentProviderSubscriptionId;
            }

            $newSubscription = Subscription::create($subscriptionAttributes);
        });

        return $newSubscription;
    }

    public function findAllSubscriptionsThatAreNotDead(int $userId): array
    {
        return Subscription::where('user_id', $userId)
            ->where(function ($query) {
                $query->where('status', SubscriptionStatus::ACTIVE->value)
                    ->orWhere('status', SubscriptionStatus::PENDING->value)
                    ->orWhere('status', SubscriptionStatus::PAUSED->value)
                    ->orWhere('status', SubscriptionStatus::PAST_DUE->value);
            })
            ->get()
            ->toArray();
    }

    public function setAsPending(int $subscriptionId): void
    {
        // make it all in one statement to avoid overwriting webhook status updates
        Subscription::where('id', $subscriptionId)
            ->where('status', SubscriptionStatus::NEW->value)
            ->update([
                'status' => SubscriptionStatus::PENDING->value,
            ]);
    }

    public function deleteAllNewSubscriptions(int $userId): void
    {
        Subscription::where('user_id', $userId)
            ->where('status', SubscriptionStatus::NEW->value)
            ->delete();
    }

    public function findActiveUserSubscription(int $userId): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->where('status', '=', SubscriptionStatus::ACTIVE->value)
            ->first();
    }

    public function findActiveUserSubscriptionWithPlanType(int $userId, PlanType $planType): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->where('status', '=', SubscriptionStatus::ACTIVE->value)
            ->whereHas('plan', function ($query) use ($planType) {
                $query->where('type', $planType->value);
            })->first();
    }


    public function findActiveByUserAndSubscriptionUuid(int $userId, string $subscriptionUuid): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->where('uuid', $subscriptionUuid)
            ->where('status', '=', SubscriptionStatus::ACTIVE->value)
            ->first();
    }

    public function findNewByPlanSlugAndUser(string $planSlug, int $userId): ?Subscription
    {
        $plan = Plan::where('slug', $planSlug)->where('is_active', true)->firstOrFail();

        return Subscription::where('user_id', $userId)
            ->where('plan_id', $plan->id)
            ->where('status', SubscriptionStatus::NEW->value)
            ->first();
    }

    public function findByUuidOrFail(string $uuid): Subscription
    {
        return Subscription::where('uuid', $uuid)->firstOrFail();
    }

    public function findByPaymentProviderId(PaymentProvider $paymentProvider, string $paymentProviderSubscriptionId): ?Subscription
    {
        return Subscription::where('payment_provider_id', $paymentProvider->id)
            ->where('payment_provider_subscription_id', $paymentProviderSubscriptionId)
            ->first();
    }

    public function updateSubscription(
        Subscription $subscription,
        array $data
    ): Subscription {
        $oldStatus = $subscription->status;
        $newStatus = $data['status'] ?? $oldStatus;
        $oldEndsAt = $subscription->ends_at;
        $newEndsAt = $data['ends_at'] ?? $oldEndsAt;
        $subscription->update($data);

        $this->handleDispatchingEvents(
            $oldStatus,
            $newStatus,
            $oldEndsAt,
            $newEndsAt,
            $subscription
        );

        return $subscription;
    }

    private function handleDispatchingEvents(
        string $oldStatus,
        string|SubscriptionStatus $newStatus,
        Carbon|string|null $oldEndsAt,
        Carbon|string|null $newEndsAt,
        Subscription $subscription
    ): void {
        $newStatus = $newStatus instanceof SubscriptionStatus ? $newStatus->value : $newStatus;

        if ($oldStatus !== $newStatus) {
            switch ($newStatus) {
                case SubscriptionStatus::ACTIVE->value:
                    Subscribed::dispatch($subscription);
                    break;
                case SubscriptionStatus::CANCELED->value:
                    SubscriptionCancelled::dispatch($subscription);
                    break;
            }
        }

        // if $oldEndsAt is string, convert it to Carbon
        if (is_string($oldEndsAt)) {
            $oldEndsAt = Carbon::parse($oldEndsAt);
        }

        // if $newEndsAt is string, convert it to Carbon
        if (is_string($newEndsAt)) {
            $newEndsAt = Carbon::parse($newEndsAt);
        }

        // if $newEndsAt > $oldEndsAt, then subscription is renewed
        if ($newEndsAt && $oldEndsAt && $newEndsAt->greaterThan($oldEndsAt)) {
            SubscriptionRenewed::dispatch($subscription, $oldEndsAt, $newEndsAt);
        }
    }

    public function handleInvoicePaymentFailed(Subscription $subscription)
    {
        InvoicePaymentFailed::dispatch($subscription);
    }

    public function calculateSubscriptionTrialDays(Plan $plan): int
    {
        if (! $plan->has_trial) {
            return 0;
        }

        $interval = $plan->trialInterval()->firstOrFail();
        $intervalCount = $plan->trial_interval_count;

        $now = Carbon::now();

        return intval(round(abs(now()->add($interval->date_identifier, $intervalCount)->diffInDays($now))));
    }

    public function changePlan(Subscription $subscription, PaymentProviderInterface $paymentProviderStrategy, string $newPlanSlug, bool $isProrated = false): bool
    {
        if ($subscription->plan->slug === $newPlanSlug) {
            return false;
        }

        if (! $this->planManager->isPlanChangeable($subscription->plan)) {
            return false;
        }

        $newPlan = $this->planManager->getActivePlanBySlug($newPlanSlug);

        if (! $newPlan) {
            return false;
        }

        $changeResult = $paymentProviderStrategy->changePlan($subscription, $newPlan, $isProrated);

        if ($changeResult) {
            Subscribed::dispatch($subscription);

            return true;
        }

        return false;
    }

    public function canAddDiscount(Subscription $subscription)
    {
        return ($subscription->status === SubscriptionStatus::ACTIVE->value ||
            $subscription->status === SubscriptionStatus::PAST_DUE->value)
            && $subscription->price > 0
            && $subscription->discounts()->count() === 0  // only one discount per subscription for now
            && $subscription->paymentProvider->slug !== PaymentProviderConstants::LEMON_SQUEEZY_SLUG; // LemonSqueezy does not support discounts for active subscriptions
    }

    public function cancelSubscription(
        Subscription $subscription,
        PaymentProviderInterface $paymentProviderStrategy,
        string $reason,
        ?string $additionalInfo = null

    ): bool {
        $result = $paymentProviderStrategy->cancelSubscription($subscription);

        if ($result) {
            $this->updateSubscription($subscription, [
                'is_canceled_at_end_of_cycle' => true,
                'cancellation_reason' => $reason,
                'cancellation_additional_info' => $additionalInfo,
            ]);
        }

        return $result;
    }

    public function discardSubscriptionCancellation(Subscription $subscription, PaymentProviderInterface $paymentProviderStrategy): bool
    {
        $result = $paymentProviderStrategy->discardSubscriptionCancellation($subscription);

        if ($result) {
            $this->updateSubscription($subscription, [
                'is_canceled_at_end_of_cycle' => false,
                'cancellation_reason' => null,
                'cancellation_additional_info' => null,
            ]);
        }

        return $result;
    }

    public function isUserSubscribed(?User $user, ?string $productSlug = null): bool
    {
        if (! $user) {
            return false;
        }

        $subscriptions = $user->subscriptions()
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('ends_at', '>', Carbon::now())
            ->get();

        if ($productSlug) {
            $subscriptions = $subscriptions->filter(function (Subscription $subscription) use ($productSlug) {
                return $subscription->plan->product->slug === $productSlug;
            });
        }

        return $subscriptions->count() > 0;
    }

    public function isUserTrialing(?User $user, ?string $productSlug = null): bool
    {
        if (! $user) {
            return false;
        }

        $subscriptions = $user->subscriptions()
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('trial_ends_at', '>', Carbon::now())
            ->get();

        if ($productSlug) {
            $subscriptions = $subscriptions->filter(function (Subscription $subscription) use ($productSlug) {
                return $subscription->plan->product->slug === $productSlug;
            });
        }

        return $subscriptions->count() > 0;
    }

    public function getUserSubscriptionProductMetadata(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $subscriptions = $user->subscriptions()
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->where('ends_at', '>', Carbon::now())
            ->get();

        if ($subscriptions->count() === 0) {
            // if there is no active subscriptions, return metadata of default product
            $defaultProduct = Product::where('is_default', true)->first();

            if (! $defaultProduct) {
                return [];
            }

            return $defaultProduct->metadata ?? [];
        }

        // if there is 1 subscription, return metadata of its product
        if ($subscriptions->count() === 1) {
            return $subscriptions->first()->plan->product->metadata ?? [];
        }

        // if there are multiple subscriptions, return array of product-slug => metadata
        return $subscriptions->mapWithKeys(function (Subscription $subscription) {
            return [$subscription->plan->product->slug => $subscription->plan->product->metadata ?? []];
        })->toArray();
    }

    public function canEditSubscriptionPaymentDetails(Subscription $subscription)
    {
        return $subscription->status === SubscriptionStatus::ACTIVE->value || $subscription->status === SubscriptionStatus::PAST_DUE->value;

    }

    public function canCancelSubscription(Subscription $subscription)
    {
        return ! $subscription->is_canceled_at_end_of_cycle && $subscription->status === SubscriptionStatus::ACTIVE->value;
    }

    public function canDiscardSubscriptionCancellation(Subscription $subscription)
    {
        return $subscription->is_canceled_at_end_of_cycle && $subscription->status === SubscriptionStatus::ACTIVE->value;
    }
}
