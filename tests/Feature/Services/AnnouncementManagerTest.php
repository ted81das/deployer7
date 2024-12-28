<?php

namespace Tests\Feature\Services;

use App\Constants\AnnouncementPlacement;
use App\Models\Announcement;
use App\Services\AnnouncementManager;
use Tests\Feature\FeatureTest;

class AnnouncementManagerTest extends FeatureTest
{
    public function test_get_announcement_for_frontend()
    {
        Announcement::query()->delete();

        $user = $this->createUser();
        $this->actingAs($user);

        $announcement = Announcement::factory()->create([
            'title' => 'Test Announcement',
            'content' => 'Test content 1',
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'show_on_frontend' => true,
            'show_on_user_dashboard' => false,
        ]);

        $manager = app()->make(AnnouncementManager::class);

        $result = $manager->getAnnouncement(AnnouncementPlacement::FRONTEND);

        $this->assertNotNull($result);
        $this->assertEquals($announcement->id, $result->id);
    }


    public function test_get_announcement_for_user_dashboard()
    {
        Announcement::query()->delete();

        $user = $this->createUser();
        $this->actingAs($user);

        $announcement = Announcement::factory()->create([
            'title' => 'Test Announcement',
            'content' => 'Test content 2',
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'show_on_frontend' => false,
            'show_on_user_dashboard' => true,
        ]);

        $manager = app()->make(AnnouncementManager::class);

        $result = $manager->getAnnouncement(AnnouncementPlacement::USER_DASHBOARD);

        $this->assertNotNull($result);
        $this->assertEquals($announcement->id, $result->id);
    }

    public function test_get_announcement_only_active()
    {
        Announcement::query()->delete();

        $user = $this->createUser();
        $this->actingAs($user);

        $announcement = Announcement::factory()->create([
            'title' => 'Test Announcement',
            'content' => 'Test content 3',
            'is_active' => false,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'show_on_frontend' => true,
            'show_on_user_dashboard' => true,
        ]);

        $manager = app()->make(AnnouncementManager::class);

        $result = $manager->getAnnouncement(AnnouncementPlacement::FRONTEND);

        $this->assertNull($result);
    }

    public function test_get_announcement_only_when_dates_match()
    {
        Announcement::query()->delete();

        $user = $this->createUser();
        $this->actingAs($user);

        $announcement = Announcement::factory()->create([
            'title' => 'Test Announcement',
            'content' => 'Test content 4',
            'is_active' => true,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'show_on_frontend' => true,
            'show_on_user_dashboard' => true,
        ]);

        $manager = app()->make(AnnouncementManager::class);

        $result = $manager->getAnnouncement(AnnouncementPlacement::FRONTEND);

        $this->assertNull($result);
    }
}
