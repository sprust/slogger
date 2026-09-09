<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Types;

use App\Modules\Watcher\Domain\Services\Checkers\WatcherCheckerInterface;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherTypeObject;

/**
 * Everything one watcher type is: how to read its stored settings, how to describe it to
 * the panel, and who answers for it during a pass.
 *
 * One definition per type, so that the three questions are answered in one file each
 * instead of in three `match` statements that have to be kept in step. Adding a type is
 * one class and one arm of WatcherTypeRegistry.
 */
interface WatcherTypeDefinitionInterface
{
    /**
     * The stored json, read into the settings of this type.
     *
     * Missing fields fall back to defaults rather than failing: a settings column written
     * before a field existed is the ordinary case after a release, and a watcher that will
     * not load is worse than one running on the default.
     *
     * @param array<string, mixed> $settings
     */
    public function makeSettings(array $settings): WatcherSettingsInterface;

    /** What the panel needs to offer this type and build its form. */
    public function describe(): WatcherTypeObject;

    public function checker(): WatcherCheckerInterface;
}
