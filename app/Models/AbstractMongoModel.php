<?php

namespace App\Models;

use Illuminate\Contracts\Container\BindingResolutionException;
use MongoDB\Collection as MongoCollection;
use MongoDB\Laravel\Eloquent\Model;
use SConcur\Features\Mongodb\Connection\Client as SconcurClient;
use SConcur\Features\Mongodb\Connection\Collection as SconcurCollection;

abstract class AbstractMongoModel extends Model
{
    private static ?SconcurCollection $sconcurCollection = null;

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
        if (self::$sconcurCollection !== null) {
            return self::$sconcurCollection;
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

        return self::$sconcurCollection = $collection;
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
