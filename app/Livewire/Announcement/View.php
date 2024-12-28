<?php

namespace App\Livewire\Announcement;

use App\Constants\AnnouncementPlacement;
use App\Services\AnnouncementManager;
use Livewire\Attributes\Lazy;
use Livewire\Component;

#[Lazy]
class View extends Component
{
    public ?string $placement;

    public function mount(?string $placement = null)
    {
        $this->placement = $placement;
    }

    public function render(AnnouncementManager $announcementManager)
    {
        $placement = AnnouncementPlacement::tryFrom($this->placement) ?? AnnouncementPlacement::FRONTEND;

        return view(
            'livewire.announcement.view', [
                'announcement' => $announcementManager->getAnnouncement($placement),
            ]
        );
    }
}
