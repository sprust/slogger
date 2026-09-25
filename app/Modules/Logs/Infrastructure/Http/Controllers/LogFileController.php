<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Http\Controllers;

use App\Modules\Logs\Domain\Actions\FindLogFilesAction;
use App\Modules\Logs\Domain\Actions\StreamLogFileAction;
use App\Modules\Logs\Domain\Exceptions\LogFileTooLargeException;
use App\Modules\Logs\Infrastructure\Http\Resources\LogFileDownloadResponse;
use App\Modules\Logs\Infrastructure\Http\Resources\LogFileResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class LogFileController
{
    public function __construct(
        private FindLogFilesAction $findLogFilesAction,
        private StreamLogFileAction $streamLogFileAction
    ) {
    }

    #[OaListItemTypeAttribute(LogFileResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return LogFileResource::collection(
            $this->findLogFilesAction->handle()
        );
    }

    public function download(string $id): LogFileDownloadResponse
    {
        try {
            $download = $this->streamLogFileAction->handle($id);
        } catch (LogFileTooLargeException $exception) {
            abort(ResponseFoundation::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }

        if ($download === null) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Log file [$id] not found");
        }

        return new LogFileDownloadResponse($download);
    }
}
