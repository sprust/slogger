<?php

namespace App\Models;

use App\Services\Mongo\MongoConnectionFactory;
use Illuminate\Database\Eloquent\Model;
use SConcur\Features\Mongodb\Connection\Collection as SconcurCollection;

/**
 * A facade over a Mongo collection, not an active record.
 *
 * Nothing here is ever hydrated, saved or queried through Eloquent: every read and write
 * goes through sconcur(), which hands back the collection of the non-blocking SConcur
 * driver. What the model still is, is the one named place that declares where a document
 * lives — the collection, the connection it is on — and what it holds, in the property
 * annotations of each subclass, so a repository points at a class instead of at a string.
 *
 * The casts of the subclasses are documentation for the same reason. None of them runs:
 * no attribute is ever set on an instance.
 *
 * The Eloquent base is kept for the declarations it already carries. It is not a
 * connection to Mongo — the ORM has no driver for it here, and resolving the connection
 * this model names would fail.
 */
abstract class AbstractMongoModel extends Model
{
    /**
     * @var array<class-string<AbstractMongoModel>, SconcurCollection>
     */
    private static array $sconcurCollections = [];

    abstract public function getCollectionName(): string;

    public function getTable()
    {
        return $this->getCollectionName();
    }

    public static function sconcur(): SconcurCollection
    {
        $class = static::class;

        if (array_key_exists($class, static::$sconcurCollections)) {
            return static::$sconcurCollections[$class];
        }

        $instance = new static();

        $collection = new MongoConnectionFactory()
            ->database($instance->connection)
            ->selectCollection($instance->getCollectionName());

        return static::$sconcurCollections[$class] = $collection;
    }
}
