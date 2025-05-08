<?php

namespace DDR\LaravelQualityTools\Commands;

use DDR\LaravelQualityTools\Enums\QualityTool;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'quality-tools:install {--force : Overwrite existing files without asking}';

    protected $description = 'Installs quality tools in the project';

    protected $toolFiles = [
        'phpstan' => [
            [
                'source' => 'phpstan/phpstan.neon',
                'target' => 'phpstan.neon',
            ],
        ],
        'pint' => [
            [
                'source' => 'pint/pint.json',
                'target' => 'pint.json',
            ],
        ],
        'prettier' => [
            [
                'source' => 'prettier/.prettierrc',
                'target' => '.prettierrc',
            ],
        ],
        'commitlint' => [
            [
                'source' => 'commitlint/commitlint.config.js',
                'target' => 'commitlint.config.js',
            ],
        ],
        'husky' => [
            [
                'source' => 'husky/prepare-commit-msg',
                'target' => '.husky/prepare-commit-msg',
                'executable' => true,
            ],
            [
                'source' => 'husky/pre-push',
                'target' => '.husky/pre-push',
                'executable' => true,
            ],
            [
                'source' => 'husky/pre-commit',
                'target' => '.husky/pre-commit',
                'executable' => true,
            ],
            [
                'source' => 'husky/commit-msg',
                'target' => '.husky/commit-msg',
                'executable' => true,
            ],
        ],
    ];

    protected $packageDependenciesForInstall = [
        'npm' => [
            'devDependencies' => [
                '@commitlint/cli' => '^19.2.1',
                '@commitlint/config-conventional' => '^19.1.0',
                'commitizen' => '^4.3.0',
                'cz-conventional-changelog' => '^3.3.0',
                'husky' => '^9.0.11',
                'prettier' => '^3.2.5',
                'prettier-plugin-blade' => '^2.1.6',
                'prettier-plugin-tailwindcss' => '^0.5.11',
            ],
            'scripts' => [
                'prepare' => 'node -e "if(process.env.NODE_ENV !== \'production\') { process.exit(1) }" 2>/dev/null || husky',
                'postinstall' => 'npm run prepare',
                'prettier' => 'prettier --write resources/',
            ],
            'config' => [
                'commitizen' => [
                    'path' => './node_modules/cz-conventional-changelog',
                ],
            ],
        ],
        'composer' => [
            'require-dev' => [
                'larastan/larastan' => '^3.0',
                'laravel/pint' => '^1.0',
            ],
            'scripts' => [
                'test' => 'pest --parallel',
                'pstan' => 'phpstan analyse --memory-limit=-1',
                'pint' => 'pint',
            ],
        ],
    ];

    protected array $npmDependencies = [
        'devDependencies' => [],
        'scripts' => [],
        'config' => [],
    ];

    protected array $npmDepenciesMap = [
        'devDependencies' => [
            'commitlint' => [
                '@commitlint/cli' => '^19.2.1',
                '@commitlint/config-conventional' => '^19.1.0',
            ],
            'commitizen' => [
                'commitizen' => '^4.3.0',
                'cz-conventional-changelog' => '^3.3.0',
            ],
            'husky' => [
                'husky' => '^9.0.11',
            ],
            'prettier' => [
                'prettier' => '^3.2.5',
                'prettier-plugin-blade' => '^2.1.6',
                'prettier-plugin-tailwindcss' => '^0.5.11',
            ],
        ],
        'scripts' => [
            'husky' => [
                'prepare' => 'node -e "if(process.env.NODE_ENV !== \'production\') { process.exit(1) }" 2>/dev/null || husky',
                'postinstall' => 'npm run prepare',
            ],
            'prettier' => [
                'prettier' => 'prettier --write resources/',
            ],
        ],
        'config' => [
            'commitizen' => [
                'commitizen' => [
                    'path' => './node_modules/cz-conventional-changelog',
                ],
            ],
        ],
    ];

    protected $composerDependencies = [
        'require-dev' => [],
        'scripts' => [
            'test' => 'pest --parallel',
        ],
    ];

    protected array $composerDependenciesMap = [
        'require-dev' => [
            'phpstan' => [
                'larastan/larastan' => '^3.0',
            ],
            'pint' => [
                'laravel/pint' => '^1.0',
            ],
        ],
        'scripts' => [
            'phpstan' => [
                'pstan' => 'phpstan analyse --memory-limit=-1',
            ],
            'pint' => [
                'pint' => 'pint',
            ],
        ],
    ];

    public function handle(): int
    {
        $this->info('Installing quality tools...');

        $choices = QualityTool::options();
        $choices['all'] = 'All';

        $tools = $this->choice(
            question: 'Select tools to install:',
            choices: $choices,
            default: 'all',
            multiple: true,
        );

        if ($tools === ['all']) {
            $tools = array_map(fn (QualityTool $tool) => $tool->value, QualityTool::cases());
        }

        foreach ($tools as $tool) {
            if (isset($this->toolFiles[$tool])) {
                foreach ($this->toolFiles[$tool] as $fileInfo) {
                    $this->installFile($fileInfo, $this->option('force'));
                }
            }

            foreach ($this->npmDependencies as $section => $dependencies) {
                if (isset($this->npmDepenciesMap[$section][$tool])) {
                    $this->npmDependencies[$section] = array_merge($this->npmDependencies[$section], $this->npmDepenciesMap[$section][$tool]);
                }
            }

            foreach ($this->composerDependencies as $section => $dependencies) {
                if (isset($this->composerDependenciesMap[$section][$tool])) {
                    $this->composerDependencies[$section] = array_merge($this->composerDependencies[$section], $this->composerDependenciesMap[$section][$tool]);
                }
            }
        }

        $this->updatePackageJson();

        try {
            $this->updateComposerJson();
        } catch (Exception $exception) {
            $this->error($exception->getMessage());

            return 1;
        }

        $this->info('Quality tools installed successfully!');
        $this->info('Run `npm install` to install Node.js dependencies and `composer update` to update Composer dependencies.');

        return 0;
    }

    protected function installFile(array $fileInfo, bool $force): void
    {
        $sourcePath = __DIR__.'/../../resources/samples/'.$fileInfo['source'];
        $targetPath = base_path($fileInfo['target']);

        $targetDir = dirname($targetPath);

        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        if (File::exists($targetPath) && ! $force) {
            if (! $this->confirm("The file {$fileInfo['target']} already exists. Do you want to overwrite it?")) {
                $this->info("Installation of {$fileInfo['target']} canceled by the user.");

                return;
            }
        }

        File::copy($sourcePath, $targetPath);

        if (! empty($fileInfo['executable'])) {
            chmod($targetPath, 0755);
        }

        $this->info("File {$fileInfo['target']} installed successfully.");
    }

    protected function updatePackageJson(): void
    {
        $packageJsonPath = base_path('package.json');

        if (! File::exists($packageJsonPath)) {
            $this->warn('Arquivo package.json não encontrado. Criando um novo...');

            File::put($packageJsonPath, json_encode([
                'private' => true,
                'type' => 'module',
                'scripts' => [],
                'devDependencies' => [],
                'dependencies' => [],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $packageJson = json_decode(File::get($packageJsonPath), true);
        $npmDeps = $this->packageDependenciesForInstall['npm'];

        foreach ($npmDeps as $section => $dependencies) {
            if (! isset($packageJson[$section])) {
                $packageJson[$section] = [];
            }

            foreach ($dependencies as $key => $value) {
                if (! isset($this->npmDependencies[$section][$key])) {
                    continue;
                }

                if (! is_array($value)) {
                    $packageJson[$section][$key] = $value;

                    continue;
                }

                if (! isset($packageJson[$section][$key])) {
                    $packageJson[$section][$key] = [];
                }

                $packageJson[$section][$key] = array_merge($packageJson[$section][$key], $value);

            }
        }

        File::put($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Arquivo package.json atualizado com sucesso.');
    }

    protected function updateComposerJson(): void
    {
        $composerJsonPath = base_path('composer.json');

        if (! File::exists($composerJsonPath)) {
            throw new Exception('Arquivo composer.json não encontrado. Verifique sua instalação do Laravel.');
        }

        $composerJson = json_decode(File::get($composerJsonPath), true);
        $composerDeps = $this->packageDependenciesForInstall['composer'];

        foreach ($composerDeps as $section => $dependencies) {
            $composerJson[$section] = array_merge($composerJson[$section], $this->composerDependencies[$section] ?? []);
        }

        File::put($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Arquivo composer.json atualizado com sucesso.');
    }
}
