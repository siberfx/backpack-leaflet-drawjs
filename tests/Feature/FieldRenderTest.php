<?php

namespace Siberfx\LeafletDrawjs\Tests\Feature;

use Siberfx\LeafletDrawjs\Tests\Fixtures\Region;
use Siberfx\LeafletDrawjs\Tests\Fixtures\RegionCrudController;
use Siberfx\LeafletDrawjs\Tests\TestCase;

class FieldRenderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->login();
    }

    private function createPage(): string
    {
        return $this->get('/admin/region/create')->assertOk()->getContent();
    }

    public function test_create_page_renders_the_field(): void
    {
        $html = $this->createPage();

        $this->assertStringContainsString('bp-field-type="leaflet-draw"', $html);
        $this->assertStringContainsString('<label>Polygon</label>', $html);
        $this->assertStringContainsString('data-init-function="bpFieldInitLeafletDrawElement"', $html);
        $this->assertSame('', $this->inputValue($html, 'coordinates'));
    }

    public function test_assets_are_loaded_once_for_multiple_fields(): void
    {
        $html = $this->createPage();

        $this->assertSame(2, substr_count($html, 'data-leaflet-draw-map'));

        foreach ([
            'leaflet/1.9.4/leaflet.min.css',
            'leaflet/1.9.4/leaflet.min.js',
            'leaflet.draw/1.0.4/leaflet.draw.css',
            'leaflet.draw/1.0.4/leaflet.draw.js',
        ] as $asset) {
            $this->assertSame(1, preg_match_all('#(href|src)="[^"]*'.preg_quote($asset, '#').'#', $html), "$asset should be included exactly once");
        }

        $this->assertSame(1, preg_match_all('#src="[^"]*siberfx/leaflet-draw/leaflet-draw-field[^"]*\.js#', $html));
        $this->assertSame(1, preg_match_all('#href="[^"]*siberfx/leaflet-draw/leaflet-draw-field[^"]*\.css#', $html));
    }

    public function test_falls_back_to_openstreetmap_without_a_mapbox_token(): void
    {
        config(['backpack.leaflet-draw.mapbox.access_token' => null]);

        $config = $this->mapConfig($this->createPage());

        $this->assertSame('openstreetmap', $config['provider']);
        $this->assertNull($config['accessToken']);
    }

    public function test_uses_mapbox_when_a_token_is_configured(): void
    {
        config(['backpack.leaflet-draw.mapbox.access_token' => 'pk.test-token']);

        $config = $this->mapConfig($this->createPage());

        $this->assertSame('mapbox', $config['provider']);
        $this->assertSame('pk.test-token', $config['accessToken']);
        $this->assertSame('mapbox/streets-v12', $config['style']);
    }

    public function test_config_defaults_are_used_for_the_map(): void
    {
        config([
            'backpack.leaflet-draw.default.lat' => 41.0082,
            'backpack.leaflet-draw.default.lng' => 28.9784,
            'backpack.leaflet-draw.default.zoom' => 9,
            'backpack.leaflet-draw.default.height' => '250px',
        ]);

        $html = $this->createPage();
        $config = $this->mapConfig($html);

        $this->assertSame(41.0082, $config['lat']);
        $this->assertSame(28.9784, $config['lng']);
        $this->assertSame(9, $config['zoom']);
        $this->assertFalse($config['multiple']);
        $this->assertFalse($config['scrollWheelZoom']);
        $this->assertStringContainsString('style="height: 250px;"', $html);
    }

    public function test_field_options_override_config(): void
    {
        RegionCrudController::$fields = [[
            'name' => 'coordinates',
            'type' => 'leaflet-draw',
            'options' => [
                'provider' => 'mapbox',
                'access_token' => 'pk.field-token',
                'style' => 'mapbox/satellite-v9',
                'lat' => 52.37,
                'lng' => 4.89,
                'zoom' => 15,
                'height' => '480px',
                'color' => '#ff0000',
                'multiple' => true,
                'scroll_wheel_zoom' => true,
            ],
        ]];

        $html = $this->createPage();
        $config = $this->mapConfig($html);

        $this->assertSame([
            'provider' => 'mapbox',
            'accessToken' => 'pk.field-token',
            'style' => 'mapbox/satellite-v9',
            'lat' => 52.37,
            'lng' => 4.89,
            'zoom' => 15,
            'multiple' => true,
            'scrollWheelZoom' => true,
            'color' => '#ff0000',
        ], $config);
        $this->assertStringContainsString('style="height: 480px;"', $html);
    }

    public function test_openstreetmap_provider_never_exposes_the_token(): void
    {
        config(['backpack.leaflet-draw.mapbox.access_token' => 'pk.secret']);

        RegionCrudController::$fields = [[
            'name' => 'coordinates',
            'type' => 'leaflet-draw',
            'options' => ['provider' => 'openstreetmap'],
        ]];

        $html = $this->createPage();

        $this->assertSame('openstreetmap', $this->mapConfig($html)['provider']);
        $this->assertStringNotContainsString('pk.secret', $html);
    }

    public function test_edit_page_renders_the_stored_geojson(): void
    {
        $feature = $this->feature();
        $collection = ['type' => 'FeatureCollection', 'features' => [$this->feature(), $this->feature(0.1)]];

        $region = Region::create(['name' => 'Antalya', 'coordinates' => $feature, 'areas' => $collection]);

        $html = $this->get("/admin/region/{$region->id}/edit")->assertOk()->getContent();

        $this->assertSame($feature, json_decode($this->inputValue($html, 'coordinates'), true));
        $this->assertSame($collection, json_decode($this->inputValue($html, 'areas'), true));
    }

    public function test_renders_a_raw_json_string_value(): void
    {
        // e.g. a text column without an array cast
        $json = json_encode($this->feature());

        RegionCrudController::$fields = [['name' => 'coordinates', 'type' => 'leaflet-draw', 'value' => $json]];

        $this->assertSame($json, $this->inputValue($this->createPage(), 'coordinates'));
    }

    public function test_old_input_is_restored_after_a_validation_error(): void
    {
        $json = json_encode($this->feature(0.3));

        $html = $this->withSession(['_old_input' => ['coordinates' => $json]])
            ->get('/admin/region/create')
            ->assertOk()
            ->getContent();

        $this->assertSame($json, $this->inputValue($html, 'coordinates'));
    }

    public function test_hint_is_rendered(): void
    {
        RegionCrudController::$fields = [[
            'name' => 'coordinates',
            'type' => 'leaflet-draw',
            'hint' => 'Draw the delivery area',
        ]];

        $this->assertStringContainsString('Draw the delivery area', $this->createPage());
    }
}
