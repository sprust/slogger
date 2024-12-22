<?php

declare(strict_types=1);

namespace App\Modules\Service\Infrastructure\Commands;

use App\Modules\Service\Contracts\Actions\CreateServiceActionInterface;
use App\Modules\Service\Domain\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Parameters\ServiceCreateParameters;
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
     */
    public function handle(CreateServiceActionInterface $createServiceAction): int
    {
        $serviceName = $this->ask('Name *');

        if (!$serviceName) {
            return self::FAILURE;
        }

        try {
            $service = $createServiceAction->handle(
                new ServiceCreateParameters(
                    name: $serviceName
                )
            );
        } catch (ServiceAlreadyExistsException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

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
