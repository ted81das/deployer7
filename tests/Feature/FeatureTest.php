<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\Testing\TestingDatabaseSeeder;
use Tests\TestCase;

class FeatureTest extends TestCase
{
    protected static bool $setUpHasRunOnce = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::$setUpHasRunOnce) {
            $this->artisan('migrate:fresh');
            $this->seed(TestingDatabaseSeeder::class);

            static::$setUpHasRunOnce = true;
        }

        $this->withoutExceptionHandling();
        $this->withoutVite();
    }

    protected function createUser(array $attributes = [])
    {
        return User::factory()->create($attributes);
    }

    protected function createAdminUser()
    {
        $user = User::factory()->create([
            'is_admin' => true,
        ]);

        $user->each(function ($user) {
            $user->assignRole('admin');
        });

        return $user;
    }
}
