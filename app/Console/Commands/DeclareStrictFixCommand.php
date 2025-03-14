<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeclareStrictFixCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'declare-strict-fix {--write}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and fix declare(strict_types=1); in php files';

    public function handle(): int
    {
        $write = $this->option('write');

        if ($write && !$this->confirm('Are you sure you want to fix declare(strict_types=1); in all files?')) {
            return self::FAILURE;
        }

        /** @var string[] $filePaths */
        $filePaths = [
            ...$this->findFilePaths(app_path() . '/Modules'),
        ];

        foreach ($filePaths as $filePath) {
            if (File::extension($filePath) !== 'php') {
                continue;
            }

            if (!File::isFile($filePath)) {
                $this->error("File $filePath is not a file");
                break;
            }

            if (!$write) {
                $this->info("File $filePath can be fixed");

                continue;
            }

            $fileContent = file_get_contents($filePath);

            $fileContent = str_replace("<?php\n\n", "<?php\n\ndeclare(strict_types=1);\n\n", $fileContent);

            file_put_contents($filePath, $fileContent);

            $this->info("File $filePath fixed");
        }

        $filesCount = count($filePaths);

        if ($write) {
            if ($filesCount) {
                $this->info('All files fixed');
            } else {
                $this->info('All files already fixed');
            }
        } elseif ($filesCount) {
            $this->warn('Use --write option to fix declare(strict_types=1); in all files');

            return self::FAILURE;
        } else {
            $this->info('All files have declare(strict_types=1);');
        }

        return self::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function findFilePaths(string $path): array
    {
        $filePaths = [];

        exec("grep -Lr \"declare(strict_types=1);\" $path", $filePaths);

        return $filePaths;
    }
}
