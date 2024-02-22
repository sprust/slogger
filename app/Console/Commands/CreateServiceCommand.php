<?php

namespace App\Console\Commands;

use App\Modules\Services\Dto\Parameters\ServiceCreateParameters;
use App\Modules\Services\Repository\ServicesRepositoryInterface;
use Illuminate\Console\Command;

class CreateServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a service';

    /**
     * Execute the console command.
     */
    public function handle(ServicesRepositoryInterface $servicesRepository): int
    {
        $serviceName = $this->ask('Name *');

        if (!$serviceName) {
            return self::FAILURE;
        }

        $service = $servicesRepository->create(
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
                    $service->api_token,
                ],
            ]
        );

        return self::SUCCESS;
    }
}
