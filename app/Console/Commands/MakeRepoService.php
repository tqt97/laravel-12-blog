<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MakeRepoService extends Command
{
    protected $signature = 'make:repo-service
                            {repo : The base name for the Repository}
                            {service : The base name for the Service}
                            {--model= : Specify the Model for Repository}
                            {--repo= : Specify the Repository Interface for Service}';

    protected $description = '🚀 Create a new Repository and Service together';

    public function handle()
    {
        $repo = $this->argument('repo');
        $service = $this->argument('service');
        $model = $this->option('model');
        $repoOption = $this->option('repo') ?: $repo; // Nếu không nhập --repo, mặc định lấy giá trị của repo

        // Gọi command tạo Repository
        $this->info('Creating Repository...');
        Artisan::call('make:repository', [
            'name' => $repo,
            '--model' => $model,
        ]);
        $this->line(Artisan::output());

        // Gọi command tạo Service
        $this->info('Creating Service...');
        Artisan::call('make:service', [
            'name' => $service,
            '--repo' => $repoOption,
        ]);
        $this->line(Artisan::output());
    }
}
