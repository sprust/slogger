<?php

namespace App\Models;

use App\Modules\Projects\ProjectDatabaseContainer;

abstract class AbstractProjectModel extends AbstractMongoModel
{
    protected $connection = 'mongodb.projects';

    abstract static protected function getCollectionName(): string;

    public function getTable()
    {
        return static::getCollectionName();
    }

    public function getConnection()
    {
        return parent::getConnection()->setDatabaseName(
            app(ProjectDatabaseContainer::class)->getDatabaseName()
        );
    }
}
