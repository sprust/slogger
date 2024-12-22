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
    protected $description = 'Find and fix declare(strict_types=1); in all module php files';

    public function handle(): int
    {
        $write = $this->option('write');

        if ($write && !$this->confirm('Are you sure you want to fix declare(strict_types=1); in all files?')) {
            return self::FAILURE;
        }

        $appPath = app_path();

        $resGrep = [];

        exec("grep -Lr \"declare(strict_types=1);\" $appPath/Modules", $resGrep);

        foreach ($resGrep as $filePath) {
            if (File::extension($filePath) !== 'php') {
                continue;
            }

            if (!File::isFile($filePath)) {
                $this->error("File $filePath is not a file");
                break;
            }

            if (!$write) {
                $this->info("File $filePath can to be fixed");

                continue;
            }

            $fileContent = file_get_contents($filePath);

            $fileContent = str_replace("<?php\n\n", "<?php\n\ndeclare(strict_types=1);\n\n", $fileContent);

            file_put_contents($filePath, $fileContent);

            $this->info("File $filePath fixed");
        }

        return self::SUCCESS;
    }
}
