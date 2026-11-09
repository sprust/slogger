<?php

namespace Ifksco\OpenApiGenerator;

use Ifksco\OpenApiGenerator\Sources\SourceInterface;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

readonly class OaService
{
    public function __construct(
        private Application $app
    ) {
    }

    /**
     * @throws BindingResolutionException
     */
    public function generateScheme(string $diskAlias): void
    {
        $diskSetting = Arr::first(
            config('oa-generator.disks'),
            static fn(array $setting) => ($setting['alias'] ?? null) === $diskAlias
        );

        if (!$diskSetting) {
            throw new RuntimeException("Setting disk for $diskAlias not found");
        }

        $diskName = $diskSetting['name'] ?? null;
        $isPublic = $diskSetting['is_public'] ?? null;

        if (!$diskName || !is_bool($isPublic)) {
            throw new RuntimeException("Invalid disk settings for $diskAlias");
        }

        $disk = Storage::disk($diskName);

        foreach (config('oa-generator.sources') as $sourceClass) {
            /** @var class-string<SourceInterface> $sourceClass */

            /** @var SourceInterface $source */
            $source = $this->app->make($sourceClass);

            $jsonGenerator = new JsonFileGenerator($source);

            $jsonGenerator->generate($isPublic);

            if (!App::runningUnitTests()) {
                $jsonGenerator->saveToDisk(
                    $disk,
                    "{$source->getName()}-openapi-scheme"
                );
            }
        }
    }
}
