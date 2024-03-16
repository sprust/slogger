<?php

namespace App\Modules\Service\Commands;

use App\Modules\Service\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Services\Parameters\ServiceCreateParameters;
use App\Modules\Service\Services\ServiceService;
use Illuminate\Console\Command;

class CreateServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'service:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a service';

    /**
     * Execute the console command.
     *
     * @throws ServiceAlreadyExistsException
     */
    public function handle(ServiceService $serviceService): int
    {
        $serviceName = $this->ask('Name *');

        if (!$serviceName) {
            return self::FAILURE;
        }

        $service = $serviceService->create(
            new ServiceCreateParameters(
                name: $serviceName
            )
        );

        $this->table(
            [
                'id',
                'token',
            ],
            [
                [
                    $service->id,
                    $service->apiToken,
                ],
            ]
        );

        return self::SUCCESS;
    }
}
