<?php

namespace App\Models;

use Illuminate\Contracts\Container\BindingResolutionException;
use MongoDB\Collection as MongoCollection;
use MongoDB\Laravel\Eloquent\Model;
use SConcur\Features\Mongodb\Connection\Client as SconcurClient;
use SConcur\Features\Mongodb\Connection\Collection as SconcurCollection;

abstract class AbstractMongoModel extends Model
{
    /**
     * @var array<class-string<AbstractMongoModel>, SconcurCollection>
     */
    private static array $sconcurCollections = [];

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

    public static function sconcur(): SconcurCollection
    {
        $class = static::class;

        if (array_key_exists($class, static::$sconcurCollections)) {
            return static::$sconcurCollections[$class];
        }

        $instance = new static();

        $config = config("database.connections.$instance->connection");

        $username = $config['username'];
        $password = $config['password'];
        $host     = $config['host'];
        $port     = $config['port'];
        $database = $config['database'];
        $options  = $config['options'];

        $uri = "mongodb://$username:$password@$host:$port";

        $collection = new SconcurClient($uri, socketTimeoutMs: $options['socketTimeoutMS'] ?? null)
            ->selectDatabase($database)
            ->selectCollection($instance->getCollectionName());

        return static::$sconcurCollections[$class] = $collection;
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
