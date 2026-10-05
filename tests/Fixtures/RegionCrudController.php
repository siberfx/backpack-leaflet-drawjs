<?php

namespace Siberfx\LeafletDrawjs\Tests\Fixtures;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class RegionCrudController extends CrudController
{
    use CreateOperation;
    use UpdateOperation;

    /**
     * Field definitions, swappable per test. Empty means the defaults below.
     */
    public static array $fields = [];

    public function setup()
    {
        CRUD::setModel(Region::class);
        CRUD::setRoute('admin/region');
        CRUD::setEntityNameStrings('region', 'regions');
    }

    protected function setupCreateOperation()
    {
        CRUD::field('name');

        foreach (static::$fields ?: static::defaultFields() as $field) {
            CRUD::field($field);
        }
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public static function defaultFields(): array
    {
        return [
            ['name' => 'coordinates', 'label' => 'Polygon', 'type' => 'leaflet-draw'],
            ['name' => 'areas', 'label' => 'Areas', 'type' => 'leaflet-draw', 'options' => ['multiple' => true]],
        ];
    }
}
