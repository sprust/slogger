<?php

namespace App\Models;

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
}
