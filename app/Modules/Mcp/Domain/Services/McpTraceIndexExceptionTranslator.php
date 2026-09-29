<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Services;

use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexBuildingException;
use App\Modules\Mcp\Domain\Exceptions\McpTraceIndexFailedException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexParallelArraysException;
use Closure;

readonly class McpTraceIndexExceptionTranslator
{
    /**
     * @template T
     *
     * @param Closure(): T $query
     *
     * @return T
     *
     * @throws McpTraceIndexBuildingException
     * @throws McpTraceIndexFailedException
     */
    public function call(Closure $query): mixed
    {
        try {
            return $query();
        } catch (TraceDynamicIndexInProcessException $exception) {
            throw new McpTraceIndexBuildingException($exception->indexId);
        } catch (TraceDynamicIndexErrorException $exception) {
            throw new McpTraceIndexFailedException($exception->getMessage());
        } catch (TraceDynamicIndexNotInitException) {
            throw new McpTraceIndexFailedException('The trace dynamic index could not be initialized.');
        } catch (TraceDynamicIndexParallelArraysException) {
            throw new McpTraceIndexFailedException('Tags and a data field cannot be filtered together.');
        }
    }
}
