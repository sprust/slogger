<?php

namespace App\Models;

use Illuminate\Contracts\Container\BindingResolutionException;
use MongoDB\Collection as MongoCollection;
use MongoDB\Laravel\Eloquent\Model;

abstract class AbstractMongoModel extends Model
{
    abstract function getCollectionName(): string;

    public function getTable()
    {
        return $this->getCollectionName();
    }

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
