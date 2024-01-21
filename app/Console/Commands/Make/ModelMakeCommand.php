<?php

namespace App\Console\Commands\Make;

use Illuminate\Support\Facades\File;

class ModelMakeCommand extends \Illuminate\Foundation\Console\ModelMakeCommand
{
    protected function resolveStubPath($stub): string
    {
        if (File::name($stub) === 'model') {
            return __DIR__ . '/stubs/model.stub';
        }

        return parent::resolveStubPath($stub);
    }
}
