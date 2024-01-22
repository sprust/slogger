<?php

namespace App\Models;

use MongoDB\Collection as MongodbCollection;
use MongoDB\Laravel\Eloquent\Model;

abstract class AbstractMongoModel extends Model
{
    public static function collection(): MongodbCollection
    {
        /** @var MongodbCollection $collection */
        $collection = (new static())->newQuery()->raw(null);

        return $collection;
    }
}
