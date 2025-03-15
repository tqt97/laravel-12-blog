<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class MakeRepository extends Command
{
    protected $signature = 'make:repository
                            {name : The name of the Repository (without "Repository" suffix)}
                            {--model= : (Optional) Specify the Model name.}';

    protected $description = '🚀 Create a new Repository structure (Contracts, Eloquents, Decorators)';

    public function handle(): void
    {
        $repoData = $this->parseRepositoryArguments();

        // Generate stubs
        $interfaceStub = $this->generateInterfaceStub($repoData);
        $repositoryStub = $this->generateRepositoryStub($repoData);
        $cacheRepositoryStub = $this->generateCacheRepositoryStub($repoData);

        // Create files in the correct folders
        $this->createFiles($repoData, $interfaceStub, $repositoryStub, $cacheRepositoryStub);
    }

    protected function parseRepositoryArguments(): array
    {
        $rawName = $this->argument('name');
        $name = str_replace(['\\', '/'], '/', $rawName);
        $basePart = basename($name);

        // Configurations
        $repoPath = app_path('Repositories');
        $model = $this->option('model') ?: $basePart;

        // Define file paths
        $paths = [
            'interface' => [
                'namespace' => 'App\\Repositories\\Contracts',
                'directory' => "{$repoPath}/Contracts",
                'filename' => "{$basePart}RepositoryInterface.php",
            ],
            'repository' => [
                'namespace' => 'App\\Repositories\\Eloquents',
                'directory' => "{$repoPath}/Eloquents",
                'filename' => "{$basePart}Repository.php",
            ],
            'cache' => [
                'namespace' => 'App\\Repositories\\Decorators',
                'directory' => "{$repoPath}/Decorators",
                'filename' => "{$basePart}CacheRepository.php",
            ],
        ];

        return [
            'model' => $model,
            'baseName' => $basePart,
            'paths' => $paths,
        ];
    }

    protected function generateInterfaceStub(array $data): string
    {
        return <<<EOT
<?php

namespace {$data['paths']['interface']['namespace']};

interface {$data['baseName']}RepositoryInterface
{
    //
}
EOT;
    }

    protected function generateRepositoryStub(array $data): string
    {
        return <<<EOT
<?php

namespace {$data['paths']['repository']['namespace']};

use App\Models\\{$data['model']};
use App\Repositories\Contracts\\{$data['baseName']}RepositoryInterface;

class {$data['baseName']}Repository extends BaseRepository implements {$data['baseName']}RepositoryInterface
{
    public function __construct({$data['model']} \${$data['model']})
    {
        parent::__construct(\${$data['model']});
    }
}
EOT;
    }

    protected function generateCacheRepositoryStub(array $data): string
    {
        return <<<EOT
<?php

namespace {$data['paths']['cache']['namespace']};

use App\Repositories\Contracts\\{$data['baseName']}RepositoryInterface;
use App\Services\Cache\CacheServiceInterface;


class {$data['baseName']}CacheRepository extends BaseCachedRepository implements {$data['baseName']}RepositoryInterface
{
    public function __construct({$data['baseName']}RepositoryInterface \$repository, CacheServiceInterface \$cacheService, ?int \$cacheTTL = null)
    {
        parent::__construct(\$repository, \$cacheService, \$cacheTTL);
    }
}
EOT;
    }

    protected function createFiles(array $data, string $interfaceStub, string $repositoryStub, string $cacheRepositoryStub): void
    {
        $filesystem = new Filesystem;

        foreach ($data['paths'] as $type => $pathInfo) {
            $directory = $pathInfo['directory'];
            $filePath = "{$directory}/{$pathInfo['filename']}";

            if (! $filesystem->exists($directory)) {
                $filesystem->makeDirectory($directory, 0755, true);
            }

            if (! $filesystem->exists($filePath)) {
                $stubContent = match ($type) {
                    'interface' => $interfaceStub,
                    'repository' => $repositoryStub,
                    'cache' => $cacheRepositoryStub,
                };

                $filesystem->put($filePath, $stubContent);
                $this->info("✅ Created: \e[32m{$filePath}\e[0m");
            } else {
                $this->warn("⚠️ Already exists: \e[33m{$filePath}\e[0m");
            }
        }
    }
}
