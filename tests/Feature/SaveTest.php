<?php

namespace Siberfx\LeafletDrawjs\Tests\Feature;

use Siberfx\LeafletDrawjs\Tests\Fixtures\Region;
use Siberfx\LeafletDrawjs\Tests\TestCase;

class SaveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->login();
    }

    public function test_store_saves_a_polygon_feature(): void
    {
        $feature = $this->feature();

        $this->post('/admin/region', [
            'name' => 'Antalya',
            'coordinates' => json_encode($feature),
            'areas' => '',
            '_save_action' => 'save_and_back',
        ])->assertRedirect();

        $region = Region::sole();

        $this->assertSame($feature, $region->coordinates);
        $this->assertNull($region->areas);
    }

    public function test_store_saves_a_feature_collection_in_multiple_mode(): void
    {
        $collection = ['type' => 'FeatureCollection', 'features' => [$this->feature(), $this->feature(0.2)]];

        $this->post('/admin/region', [
            'name' => 'Areas',
            'coordinates' => '',
            'areas' => json_encode($collection),
            '_save_action' => 'save_and_back',
        ])->assertRedirect();

        $this->assertSame($collection, Region::sole()->areas);
    }

    public function test_update_replaces_the_polygon(): void
    {
        $region = Region::create(['name' => 'Antalya', 'coordinates' => $this->feature()]);
        $edited = $this->feature(0.5);

        $this->put("/admin/region/{$region->id}", [
            'id' => $region->id,
            'name' => 'Antalya',
            'coordinates' => json_encode($edited),
            'areas' => '',
            '_save_action' => 'save_and_back',
        ])->assertRedirect();

        $this->assertSame($edited, $region->fresh()->coordinates);
    }

    public function test_update_with_deleted_polygon_clears_the_value(): void
    {
        $region = Region::create(['name' => 'Antalya', 'coordinates' => $this->feature()]);

        $this->put("/admin/region/{$region->id}", [
            'id' => $region->id,
            'name' => 'Antalya',
            'coordinates' => '',
            'areas' => '',
            '_save_action' => 'save_and_back',
        ])->assertRedirect();

        $this->assertNull($region->fresh()->coordinates);
    }

    public function test_round_trip_store_then_edit(): void
    {
        $feature = $this->feature();

        $this->post('/admin/region', [
            'name' => 'Round trip',
            'coordinates' => json_encode($feature),
            '_save_action' => 'save_and_back',
        ])->assertRedirect();

        $html = $this->get('/admin/region/'.Region::sole()->id.'/edit')->assertOk()->getContent();

        $this->assertSame($feature, json_decode($this->inputValue($html, 'coordinates'), true));
    }
}
