<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * The command accepts:
     * - name: The name of the Service.
     * - --repo: (Optional) Specify the Repository Interface, which can include subdirectories.
     *
     * @var string
     */
    protected $signature = 'make:service
                            {name : The name of the Service. Accepts directory separators "/" or "\\".}
                            {--repo= : Specify the Repository Interface. Can include subdirectories.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '🚀 Create a new Service with an optional Repository Interface';

    /**
     * Detailed help message for the command.
     *
     * @var string
     */
    protected $help = <<<'EOT'
The <info>make:service</info> command creates a new Service class with an optional Repository Interface.

Usage:
  <info>php artisan make:service {name} {--repo=}</info>

Arguments:
  name      The name of the Service.
            - You can use both "/" and "\" as directory separators.
            - Lowercase or uppercase initial letters are acceptable; the name will be normalized to StudlyCase.
            - The service name may include the prefix "Service" or not; if absent, it will be appended automatically.
            Example: <info>user</info>, <info>User</info>, <info>admin/user</info>, or <info>Admin\User</info>

Options:
  --repo    (Optional) Specify the Repository Interface.
            - Accepts both "/" and "\" as directory separators.
            - The input is normalized to StudlyCase.
            - If a path is provided, that path will be used (e.g., <info>User/UserRepository</info> results in <info>App\Repositories\User\UserRepositoryInterface</info>).
            - If not provided, it defaults to using {name}Repository from the root repository path (<info>App\Repositories</info>).
            Example: <info>user/user</info> or <info>User\UserRepository</info>

Example:
  <info>php artisan make:service User --repo=Admin/UserRepository</info>
EOT;

    /**
     * Execute the console command.
     *
     * This method orchestrates the parsing of arguments, generating the service stub,
     * and creating the service file on disk.
     */
    public function handle(): void
    {
        // Parse and normalize service arguments.
        $serviceData = $this->parseServiceArguments();

        // Parse the repository option and generate repository data.
        // If no repo option is provided, default to using the service's base name.
        $repoData = $this->parseRepositoryOption($this->option('repo'), $serviceData['baseName']);

        // Generate the service class stub based on whether repository injection is required.
        $serviceStub = $this->generateServiceStub($serviceData, $repoData);

        // Create the service file in the proper directory.
        $this->createServiceFile($serviceData, $serviceStub);
    }

    /**
     * Parse and normalize the service arguments.
     *
     * This method processes the 'name' argument to:
     * - Normalize directory separators.
     * - Convert each segment to StudlyCase.
     * - Append the configured service suffix if missing.
     * - Build the final namespace and file path based on config values.
     *
     * @return array Associative array containing:
     *               - namespace: Final namespace for the service.
     *               - className: Final service class name.
     *               - directory: Directory path where the service file will be stored.
     *               - servicePath: Full path to the service file.
     *               - baseName: The base name derived from the service name (used for repository defaults).
     */
    protected function parseServiceArguments(): array
    {
        // Retrieve the raw service name argument.
        $rawName = $this->argument('name');

        // Normalize path separators to "/" and convert each segment to StudlyCase.
        $normalizedName = str_replace(['\\', '/'], '/', $rawName);
        $parts = array_map(fn ($part) => Str::studly($part), explode('/', $normalizedName));

        // The last part is considered the base name for the service.
        $baseName = array_pop($parts);

        // Retrieve configuration for service namespace, path, and suffix.
        $serviceNamespaceConfig = config('service_pattern.namespace', 'App\\Services');
        $servicePathConfig = config('service_pattern.path', app_path('Services'));
        $serviceSuffix = config('service_pattern.service_suffix', 'Service');

        // Append the service suffix if not already present.
        $className = $baseName;
        if (! Str::endsWith($baseName, $serviceSuffix)) {
            $className .= $serviceSuffix;
        }

        // Build the final namespace by combining the base namespace with any subdirectories.
        $namespace = $serviceNamespaceConfig;
        if (! empty($parts)) {
            $namespace .= '\\'.implode('\\', $parts);
        }

        // Construct the directory path where the service file will be stored.
        $directory = $servicePathConfig.(! empty($parts) ? '/'.implode('/', $parts) : '');
        $servicePath = "{$directory}/{$className}.php";

        return [
            'namespace' => $namespace,
            'className' => $className,
            'directory' => $directory,
            'servicePath' => $servicePath,
            'baseName' => $baseName,
        ];
    }

    /**
     * Parse the repository option to generate repository-related data.
     *
     * This method handles the '--repo' option input. It supports subdirectories
     * by normalizing the input and constructing the proper namespace. It then creates:
     * - The repository base name (ensuring it ends with "Repository").
     * - The fully qualified repository interface name.
     * - A variable name in camelCase for dependency injection.
     *
     * @param  string|null  $repoOptionInput  The repository option input from the command.
     * @param  string  $defaultBaseName  The default base name to use if no repo option is provided.
     * @return array Associative array containing:
     *               - repositoryNamespace: The namespace for the repository interface.
     *               - baseRepositoryName: The repository class name (ensured to end with "Repository").
     *               - repositoryInterface: The fully qualified repository interface name.
     *               - repositoryVar: The variable name for dependency injection.
     */
    protected function parseRepositoryOption(?string $repoOptionInput, string $defaultBaseName): array
    {
        // Default to the base name if no repository option is provided.
        if (! $repoOptionInput) {
            $repoOptionInput = $defaultBaseName.'Repository';
        }

        // Normalize repository input by replacing "\" with "/" for consistent processing.
        $normalizedRepo = str_replace(['\\', '/'], '/', $repoOptionInput);
        $repoParts = array_map(fn ($part) => Str::studly($part), explode('/', $normalizedRepo));

        // The last segment is considered the repository name.
        $baseRepoName = array_pop($repoParts);
        if (! Str::endsWith($baseRepoName, 'Repository')) {
            $baseRepoName .= 'Repository';
        }

        // Build the repository namespace, starting from the base "App\Repositories" and appending any subdirectories.
        $repositoryNamespace = 'App\\Repositories';
        if (! empty($repoParts)) {
            $repositoryNamespace .= '\\'.implode('\\', $repoParts);
        }

        // Derive the repository interface name by removing the "Repository" suffix and appending "RepositoryInterface".
        $repositoryNameWithoutSuffix = preg_replace('/Repository$/i', '', $baseRepoName);
        $repositoryInterface = "{$repositoryNamespace}\\Contracts\\{$repositoryNameWithoutSuffix}RepositoryInterface";

        // Create a variable name for dependency injection in camelCase.
        $repositoryVar = lcfirst($repositoryNameWithoutSuffix);

        return [
            'repositoryNamespace' => $repositoryNamespace,
            'baseRepositoryName' => $baseRepoName,
            'repositoryInterface' => $repositoryInterface,
            'repositoryVar' => $repositoryVar,
        ];
    }

    /**
     * Generate the service class stub.
     *
     * This method creates the PHP class content for the service. If repository injection
     * is required (i.e., a repository option was provided), it will include the appropriate
     * use statement and a constructor with dependency injection.
     *
     * Note: The constructor uses the short interface name (alias) for type hinting.
     *
     * @param  array  $serviceData  Parsed service data from parseServiceArguments().
     * @param  array  $repoData  Parsed repository data from parseRepositoryOption().
     * @return string The generated PHP stub for the service class.
     */
    protected function generateServiceStub(array $serviceData, array $repoData): string
    {
        if ($this->option('repo')) {
            // Use Laravel's class_basename() helper to get short interface name for type hinting.
            $shortInterfaceName = class_basename($repoData['repositoryInterface']);

            return <<<EOT
<?php

namespace {$serviceData['namespace']};

use {$repoData['repositoryInterface']};

class {$serviceData['className']}
{
    public function __construct(protected {$shortInterfaceName} \${$repoData['repositoryVar']}Repository)
    {
        // Initialization code here
    }
}
EOT;
        } else {
            return <<<EOT
<?php

namespace {$serviceData['namespace']};

class {$serviceData['className']}
{
    // TODO: Implement service logic
}
EOT;
        }
    }

    /**
     * Create the service file on disk.
     *
     * This method checks if the target directory exists and creates it if needed.
     * It then writes the generated service stub to a new file. If the file already exists,
     * an error message is displayed.
     *
     * @param  array  $serviceData  Parsed service data including file path and directory.
     * @param  string  $stub  The PHP code to write to the service file.
     */
    protected function createServiceFile(array $serviceData, string $stub): void
    {
        $filesystem = new Filesystem;

        // Create the directory if it does not exist.
        if (! $filesystem->exists($serviceData['directory'])) {
            $filesystem->makeDirectory($serviceData['directory'], 0755, true);
        }

        // Create the service file if it does not already exist.
        if (! $filesystem->exists($serviceData['servicePath'])) {
            $filesystem->put($serviceData['servicePath'], $stub);
            $this->info("✅ Service created: \e[32m{$serviceData['servicePath']}\e[0m 🎉");
        } else {
            $this->error('❌ Service already exists! 🚨');
        }
    }
}
