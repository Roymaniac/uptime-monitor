<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;

#[Signature('make:service {name}')]
#[Description('Create a new service class')]
class CreateServiceClass extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $input = $this->argument('name');

        // Normalize slashes
        $input = str_replace('\\', '/', $input);

        // Extract class name
        $className = class_basename($input);

        // Extract subdirectories (if any)
        $subPath = trim(str_replace($className, '', $input), '/');

        // Build directory path
        $directory = app_path('Services' . ($subPath ? '/' . $subPath : ''));

        // Create directory if it doesn't exist
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // File path
        $filePath = $directory . '/' . $className . '.php';

        if (File::exists($filePath)) {
            $this->error('Service class already exists!');
            return;
        }

        // Build namespace
        $namespace = 'App\Services' . ($subPath ? '\\' . str_replace('/', '\\', $subPath) : '');

        // Stub
        $stub = <<<PHP
        <?php

        namespace {$namespace};

        class {$className}
        {
            // Service class implementation
        }
        PHP;

        File::put($filePath, $stub);

        $this->info("Service class {$className}.php created successfully.");
    }
}
