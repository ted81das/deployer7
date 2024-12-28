<?php

namespace App\Services;

use App\Constants\AnnouncementPlacement;
use App\Models\Announcement;

class AnnouncementManager
{
    public function __construct(
        private OrderManager $orderManager,
        private SubscriptionManager $subscriptionManager,
    ) {}

    public function getAnnouncement(AnnouncementPlacement $announcementPlacement): ?Announcement
    {
        $user = auth()->user();

        $query = Announcement::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());

        if ($announcementPlacement === AnnouncementPlacement::USER_DASHBOARD) {
            $query->where('show_on_user_dashboard', true);
        } elseif ($announcementPlacement === AnnouncementPlacement::FRONTEND) {
            $query->where('show_on_frontend', true);
        }

        if ($user && ($this->subscriptionManager->isUserSubscribed($user) || $this->orderManager->hasUserOrdered($user))) {
            $query->where('show_for_customers', true);
        }

        return $query->first();
    }
}
