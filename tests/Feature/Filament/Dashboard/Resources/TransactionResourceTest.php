<?php

namespace Tests\Feature\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\TransactionResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Feature\FeatureTest;
use Tests\TestCase;

class TransactionResourceTest extends FeatureTest
{
    public function test_list(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->get(TransactionResource::getUrl('index', [], true, 'dashboard'))->assertSuccessful();

        $response->assertStatus(200);
    }
}
