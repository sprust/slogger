<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Commands;

use App\Modules\User\Domain\Actions\DeleteExpiredUserTokensAction;
use Illuminate\Console\Command;

class DeleteExpiredUserTokensCommand extends Command
{
    protected $signature = 'user:tokens:clear';

    protected $description = 'Delete user sessions that have gone past their idle window';

    /**
     * Housekeeping, not enforcement: a lapsed session stops authenticating the moment it
     * lapses, because the lookup checks the expiry on the row it finds. This only keeps
     * the table from growing for ever.
     */
    public function handle(DeleteExpiredUserTokensAction $deleteExpiredUserTokensAction): int
    {
        $this->components->info(
            sprintf('%d expired sessions deleted', $deleteExpiredUserTokensAction->handle())
        );

        return self::SUCCESS;
    }
}
