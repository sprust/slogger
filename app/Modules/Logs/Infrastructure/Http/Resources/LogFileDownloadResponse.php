<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Http\Resources;

use App\Modules\Logs\Entities\File\LogFileDownloadObject;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogFileDownloadResponse extends StreamedResponse
{
    public function __construct(LogFileDownloadObject $download)
    {
        parent::__construct(
            callbackOrChunks: $download->chunks,
            status: Response::HTTP_OK,
            headers: [
                'Content-Type'        => 'text/plain; charset=utf-8',
                'Content-Disposition' => HeaderUtils::makeDisposition(
                    HeaderUtils::DISPOSITION_ATTACHMENT,
                    $download->file->name,
                    preg_replace('/[^\x20-\x7E]/', '_', $download->file->name) ?? 'log.txt'
                ),
                'X-Accel-Buffering'   => 'no',
            ]
        );
    }
}
