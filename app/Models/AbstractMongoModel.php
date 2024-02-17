<?php

namespace App\Models;

use Illuminate\Contracts\Container\BindingResolutionException;
use MongoDB\Laravel\Collection as MongoCollection;
use MongoDB\Laravel\Eloquent\Model;

abstract class AbstractMongoModel extends Model
{
    public static function collection(): MongoCollection
    {
        /** @var MongoCollection $collection */
        $collection = (new static())->newQuery()->raw(null);

        return $collection;
    }

    /**
     * For relation mongo -> mysql
     */
    protected function newRelatedInstance($class)
    {
        try {
            return app()->make($class);
        } catch (BindingResolutionException) {
            return new $class;
        }
    }
}
