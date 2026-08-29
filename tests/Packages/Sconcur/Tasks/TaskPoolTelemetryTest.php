<?php

namespace Tests\Packages\Sconcur\Tasks;

use PHPUnit\Framework\TestCase;
use SConcur\Laravel\Tasks\TaskPoolMetrics;
use SConcur\Laravel\Tasks\TaskPoolTelemetry;
use SConcur\Laravel\Tasks\TickResultEnum;

/**
 * The wire format is a contract with the master's collector, and the pool is the only
 * worker that speaks it from PHP rather than from the runtime's Go side. A frame that
 * drifts does not fail loudly — the collector drops it and the pool quietly disappears
 * from the panel — so the shape is pinned here.
 */
class TaskPoolTelemetryTest extends TestCase
{
    private string $path;

    /** @var resource */
    private mixed $server;

    protected function setUp(): void
    {
        // Short path on purpose: a unix socket address is capped near 108 bytes.
        $this->path = sys_get_temp_dir() . '/sconcur-tel-' . getmypid() . '.sock';

        $this->removeSocketFile();

        $server = stream_socket_server('unix://' . $this->path, $code, $message);

        if ($server === false) {
            $this->markTestSkipped('no unix sockets here: ' . $message);
        }

        $this->server = $server;
    }

    protected function tearDown(): void
    {
        fclose($this->server);

        $this->removeSocketFile();
    }

    public function testASnapshotGoesOutAsOneLengthPrefixedJsonFrame(): void
    {
        new TaskPoolTelemetry($this->path, 'tasks:0')->push();

        $frame = $this->receive();

        $this->assertSame('snapshot', $frame['t']);

        $snapshot = $frame['s'];

        // The collector drops a snapshot with no name or a non-positive pid, and the
        // group a worker belongs to is the name up to its last colon.
        $this->assertSame('tasks:0', $snapshot['name']);
        $this->assertGreaterThan(0, $snapshot['pid']);
        $this->assertGreaterThan(0, $snapshot['memory']['rssBytes']);
        $this->assertGreaterThan(0, $snapshot['updatedAtMs']);
    }

    public function testTheSectionsOfOtherRuntimesAreNeverSent(): void
    {
        new TaskPoolTelemetry($this->path, 'tasks:0')->push();

        $snapshot = $this->receive()['s'];

        // Requests belong to a server and connections to a socket server. A section
        // nobody sends is left out of the panel's answer entirely rather than shown as
        // zero, which is what this pool wants for those two.
        $this->assertArrayNotHasKey('requests', $snapshot);
        $this->assertArrayNotHasKey('connections', $snapshot);
    }

    public function testWithoutMetricsNoWorkloadSectionIsSentAtAll(): void
    {
        new TaskPoolTelemetry($this->path, 'tasks:0')->push();

        // report_ticks off: the pool stays out of the master's delivery totals.
        $this->assertArrayNotHasKey('consumers', $this->receive()['s']);
    }

    public function testTicksAreReportedAsTheConsumersSection(): void
    {
        $metrics = new TaskPoolMetrics(2);

        $metrics->tickStarted('cron');
        $metrics->tickFinished('cron', TickResultEnum::Worked);
        $metrics->tickStarted('indexes');
        $metrics->tickFinished('indexes', TickResultEnum::Failed);
        $metrics->tickStarted('cron');

        new TaskPoolTelemetry($this->path, 'tasks:0', $metrics)->push();

        $consumers = $this->receive()['s']['consumers'];

        // A tick is to a task what a delivery is to a consumer, which is what lets the
        // panel's own In-flight / Handled / Refused columns render this pool unchanged.
        // Two ticks did work; the third is still running and counts only as in-flight.
        $this->assertSame(2, $consumers['delivered']);
        $this->assertSame(1, $consumers['acked']);
        $this->assertSame(1, $consumers['refused']);
        $this->assertSame(1, $consumers['inFlight']);
        $this->assertSame(2, $consumers['coroutines']);
    }

    public function testTheFirstSampleReportsNoCpuRate(): void
    {
        $telemetry = new TaskPoolTelemetry($this->path, 'tasks:0');

        $telemetry->push();

        // A rate needs two points; the Go sampler starts at zero for the same reason.
        $this->assertSame(0.0, (float) $this->receive()['s']['cpuPercent']);
    }

    public function testWithoutTheMastersEnvironmentThereIsNoSender(): void
    {
        putenv('SCONCUR_TELEMETRY_SOCKET');
        putenv('SCONCUR_SERVER_NAME');

        // Nobody is collecting on a standalone run, so nothing is sampled or sent.
        $this->assertNull(TaskPoolTelemetry::fromEnvironment());
    }

    /**
     * Closing the server stream already unlinks the socket, so the file is usually gone
     * by now. Checked rather than suppressed with @: the error handler still sees a
     * suppressed failure and reports the test as a warning.
     */
    private function removeSocketFile(): void
    {
        if (file_exists($this->path)) {
            unlink($this->path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function receive(): array
    {
        $connection = stream_socket_accept($this->server, 2);

        $this->assertNotFalse($connection, 'the sender never connected');

        $header = fread($connection, 4);

        $this->assertIsString($header);
        $this->assertSame(4, strlen($header), 'no length prefix');

        $length = (int) unpack('N', $header)[1];
        $body   = (string) fread($connection, $length);

        fclose($connection);

        $decoded = json_decode($body, true);

        $this->assertIsArray($decoded);

        return $decoded;
    }
}
