<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Domain\Actions;

use App\Modules\Common\Domain\Services\DispatcherInterface;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Modules\Dashboard\Entities\DatabaseStatObject;
use App\Modules\Dashboard\Repositories\DatabaseStatRepository;

readonly class FindDatabaseStatAction
{
    public function __construct(private DispatcherInterface $dispatcher, private DatabaseStatRepository $databaseStatRepository)
    {
    }

    /**
     * @return DatabaseStatObject[]
     */
    public function handle(): array
    {
        $databases = $this->databaseStatRepository->findMongo();

        try {
            $databases[] = $this->databaseStatRepository->findClickhouse();
        } catch (ClickhouseQueryException $exception) {
            $this->dispatcher->report($exception);
        }

        return $databases;
    }
}
