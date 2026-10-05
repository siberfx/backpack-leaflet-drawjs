<?php

namespace Siberfx\LeafletDrawjs\Tests\Fixtures;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use CrudTrait;

    protected $fillable = ['name', 'coordinates', 'areas'];

    protected function casts(): array
    {
        return [
            'coordinates' => 'array',
            'areas' => 'array',
        ];
    }
}
