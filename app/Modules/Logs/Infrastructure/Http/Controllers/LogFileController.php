<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Http\Controllers;

use App\Modules\Logs\Domain\UseCases\DeleteLogFileByAdminUseCase;
use App\Modules\Logs\Domain\UseCases\FindLogFilesByAdminUseCase;
use App\Modules\Logs\Domain\UseCases\StreamLogFileByAdminUseCase;
use App\Modules\Logs\Infrastructure\Http\Resources\LogFileDownloadResponse;
use App\Modules\Logs\Infrastructure\Http\Resources\LogFileResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class LogFileController
{
    public function __construct(private FindLogFilesByAdminUseCase $findLogFilesByAdminUseCase, private StreamLogFileByAdminUseCase $streamLogFileByAdminUseCase, private DeleteLogFileByAdminUseCase $deleteLogFileByAdminUseCase)
    {
    }

    #[OaListItemTypeAttribute(LogFileResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return LogFileResource::collection($this->findLogFilesByAdminUseCase->handle());
    }

    public function download(string $id): LogFileDownloadResponse
    {
        $download = $this->streamLogFileByAdminUseCase->handle($id);

        return new LogFileDownloadResponse($download);
    }

    public function delete(string $id): void
    {
        $deleted = $this->deleteLogFileByAdminUseCase->handle($id);
    }
}
