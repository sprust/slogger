<?php

declare(strict_types=1);

namespace SConcur\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Queue\WorkerOptions;
use SConcur\Features\Amqp\Consumer\QueueConsumer;
use SConcur\Laravel\Queue\Rabbitmq\ConsumerRunner;

/**
 * Run a queue-consumer pool in the foreground: the worker script of a master group
 * (workerScript = artisan, workerArgs = [this command]), and a standalone consumer in
 * development.
 *
 * The consumer settings come from argv, which is how the master configures its
 * workers: a group's `server` block is forwarded to their argv verbatim. Symfony
 * Console rejects flags a command does not declare, so every one of them is declared
 * below even though QueueConsumer::fromArgs is what actually reads them.
 *
 * The two sets are not the same, though, and the raw argv cannot simply be handed
 * over: fromArgs refuses an argument it does not know, and --connection, --tries and
 * --backoff are ours rather than the runtime's. So the runtime's flags are rebuilt
 * from the parsed options and the rest is kept on this side.
 */
class RabbitmqConsumerStartCommand extends Command
{
    /** Artisan name; used by the provider to gate async wiring to the worker process. */
    public const string NAME = 'sconcur:servers:rabbitmq:start';

    /** Options that belong to QueueConsumer rather than to this command. */
    protected const array RUNTIME_OPTIONS = [
        'queues',
        'prefetchCount',
        'handlerTimeoutMs',
        'requeueOnFailure',
        'maxMessages',
        'maxRuntimeSeconds',
        'maxMemoryBytes',
        'preemptionQuantumMs',
        'masterPid',
    ];

    protected $signature = self::NAME . '
        {--queues= : Queues and their weights as JSON, e.g. [{"name":"default","coroutineCount":8}]}
        {--prefetchCount= : Unacknowledged messages one consumer may hold}
        {--handlerTimeoutMs= : How long one message may spend in the handler}
        {--requeueOnFailure= : Put a failed message back (1) instead of dead-lettering it (0)}
        {--maxMessages= : Drain and stop after this many messages}
        {--maxRuntimeSeconds= : Drain and stop after this long}
        {--maxMemoryBytes= : Drain and stop once the PHP heap passes this}
        {--preemptionQuantumMs= : Preemption quantum while consuming}
        {--connection= : The config/queue.php connection to run jobs on}
        {--tries= : Attempts before a job is marked failed}
        {--backoff= : Seconds to wait before a released job is retried}
        {--masterPid= : Master pid, injected by the supervisor for orphan self-termination}';

    protected $description = 'Run a SConcur AMQP queue-consumer pool in the foreground';

    public function handle(): int
    {
        $connection = $this->stringOption('connection')
            ?? (string) config('sconcur.queue.rabbitmq.connection', 'sconcur_rabbitmq');

        new ConsumerRunner(
            consumer: QueueConsumer::fromArgs($this->consumerArgs()),
            connectionName: $connection,
            options: $this->workerOptions(),
        )->run($this->getLaravel());

        return self::SUCCESS;
    }

    /**
     * The flags QueueConsumer understands, and only those: it throws on anything else.
     * A flag left unset is omitted rather than passed empty, so the library's own
     * default applies.
     *
     * @return list<string>
     */
    protected function consumerArgs(): array
    {
        $args = [];

        foreach (self::RUNTIME_OPTIONS as $name) {
            $value = $this->stringOption($name);

            if ($value === null) {
                continue;
            }

            $args[] = sprintf('--%s=%s', $name, $value);
        }

        return $args;
    }

    /**
     * What Worker::process() reads. `timeout` is deliberately left at zero: the deadline
     * on a job belongs to the consumer runtime's handlerTimeoutMs, which unwinds the
     * coroutine, rather than to the worker's SIGALRM, which would take the process down
     * with every other handler running in it.
     */
    protected function workerOptions(): WorkerOptions
    {
        $config = (array) config('sconcur.queue.rabbitmq', []);

        return new WorkerOptions(
            backoff: (int) ($this->stringOption('backoff') ?? $config['backoff'] ?? 0),
            memory: (int) ($config['memory_mb'] ?? 128),
            timeout: 0,
            maxTries: (int) ($this->stringOption('tries') ?? $config['tries'] ?? 1),
        );
    }

    protected function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
