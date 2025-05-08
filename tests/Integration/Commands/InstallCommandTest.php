<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $testDir = base_path();
    if (! File::exists($testDir)) {
        File::makeDirectory($testDir, 0755, true);
    }

    if (! File::exists(base_path('.husky'))) {
        File::makeDirectory(base_path('.husky'), 0755, true);
    }

    $files = [
        base_path('phpstan.neon'),
        base_path('pint.json'),
        base_path('.prettierrc'),
        base_path('commitlint.config.js'),
        base_path('.husky/prepare-commit-msg'),
        base_path('.husky/pre-push'),
        base_path('.husky/pre-commit'),
        base_path('.husky/commit-msg'),
    ];

    foreach ($files as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    if (File::exists(base_path('package.json'))) {
        File::copy(
            base_path('package.json'),
            base_path('package.json.backup')
        );
    }

    if (File::exists(base_path('composer.json'))) {
        File::copy(
            base_path('composer.json'),
            base_path('composer.json.backup')
        );
    }

    File::put(base_path('composer.json'), json_encode([
        'name' => 'laravel/laravel',
        'type' => 'project',
        'require' => [],
        'require-dev' => [],
        'scripts' => [],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    if (! File::exists(base_path('package.json'))) {
        File::put(base_path('package.json'), json_encode([
            'private' => true,
            'type' => 'module',
            'scripts' => [],
            'devDependencies' => [],
            'dependencies' => [],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
});

test('installs all tools by default', function () {
    $this->artisan('quality-tools:install')
        ->expectsQuestion('Select tools to install:', ['all'])
        ->assertSuccessful();

    expect(File::exists(base_path('phpstan.neon')))->toBeTrue()
        ->and(File::exists(base_path('pint.json')))->toBeTrue()
        ->and(File::exists(base_path('.prettierrc')))->toBeTrue()
        ->and(File::exists(base_path('commitlint.config.js')))->toBeTrue()
        ->and(File::exists(base_path('.husky/prepare-commit-msg')))->toBeTrue()
        ->and(File::exists(base_path('.husky/pre-push')))->toBeTrue()
        ->and(File::exists(base_path('.husky/pre-commit')))->toBeTrue()
        ->and(File::exists(base_path('.husky/commit-msg')))->toBeTrue();

    $packageJson = json_decode(File::get(base_path('package.json')), true);

    expect($packageJson)->toHaveKey('devDependencies')
        ->and($packageJson['devDependencies'])->toHaveKey('@commitlint/cli')
        ->and($packageJson['devDependencies'])->toHaveKey('prettier');

    $composerJson = json_decode(File::get(base_path('composer.json')), true);

    expect($composerJson)->toHaveKey('require-dev')
        ->and($composerJson['require-dev'])->toHaveKey('larastan/larastan')
        ->and($composerJson['require-dev'])->toHaveKey('laravel/pint')
        ->and($composerJson)->toHaveKey('scripts')
        ->and($composerJson['scripts'])->toHaveKey('pstan')
        ->and($composerJson['scripts'])->toHaveKey('pint');
});

test('installs only specific NPM tools', function () {
    File::delete(base_path('package.json'));

    $this->artisan('quality-tools:install')
        ->expectsQuestion('Select tools to install:', ['prettier'])
        ->assertSuccessful();

    expect(File::exists(base_path('phpstan.neon')))->toBeFalse()
        ->and(File::exists(base_path('pint.json')))->toBeFalse()
        ->and(File::exists(base_path('.prettierrc')))->toBeTrue()
        ->and(File::exists(base_path('commitlint.config.js')))->toBeFalse()
        ->and(File::exists(base_path('.husky/prepare-commit-msg')))->toBeFalse()
        ->and(File::exists(base_path('.husky/pre-push')))->toBeFalse()
        ->and(File::exists(base_path('.husky/pre-commit')))->toBeFalse()
        ->and(File::exists(base_path('.husky/commit-msg')))->toBeFalse();

    $packageJson = json_decode(File::get(base_path('package.json')), true);

    expect($packageJson)->devDependencies
        ->not->toHaveKeys([
            'husky',
            '@commitlint/cli',
            '@commitlint/config-conventional',
            'commitizen',
            'cz-conventional-changelog',
        ])
        ->toHaveKeys([
            'prettier',
            'prettier-plugin-blade',
            'prettier-plugin-tailwindcss',
        ]);

    $composerJson = json_decode(File::get(base_path('composer.json')), true);

    expect($composerJson['require-dev'])->toBeEmpty()
        ->and($composerJson['scripts'])->toHaveCount(1);
});

test('installs only specific Composer tools', function () {
    File::delete(base_path('package.json'));

    $this->artisan('quality-tools:install')
        ->expectsQuestion('Select tools to install:', ['phpstan'])
        ->assertSuccessful();

    expect(File::exists(base_path('phpstan.neon')))->toBeTrue()
        ->and(File::exists(base_path('pint.json')))->toBeFalse()
        ->and(File::exists(base_path('.prettierrc')))->toBeFalse()
        ->and(File::exists(base_path('commitlint.config.js')))->toBeFalse()
        ->and(File::exists(base_path('.husky/prepare-commit-msg')))->toBeFalse()
        ->and(File::exists(base_path('.husky/pre-push')))->toBeFalse()
        ->and(File::exists(base_path('.husky/pre-commit')))->toBeFalse()
        ->and(File::exists(base_path('.husky/commit-msg')))->toBeFalse();

    $packageJson = json_decode(File::get(base_path('package.json')), true);

    expect($packageJson)
        ->devDependencies->toBeEmpty()
        ->scripts->toBeEmpty()
        ->config->toBeEmpty();

    $composerJson = json_decode(File::get(base_path('composer.json')), true);

    expect($composerJson['require-dev'])
        ->toHaveCount(1)
        ->toHaveKey('larastan/larastan')
        ->and($composerJson['scripts'])
        ->toHaveCount(2)
        ->toHaveKey('pstan');
});

test('creates package.json if it does not exist', function () {
    File::delete(base_path('package.json'));

    $this->artisan('quality-tools:install')->expectsQuestion('Select tools to install:', ['all']);

    expect(File::exists(base_path('package.json')))
        ->toBeTrue()
        ->and(json_decode(File::get(base_path('package.json')), true))
        ->toHaveKeys([
            'private',
            'type',
            'scripts',
            'devDependencies',
        ]);
});

test('fails when composer.json does not exist', function () {
    File::delete(base_path('composer.json'));

    $this->artisan('quality-tools:install')
        ->expectsQuestion('Select tools to install:', ['all'])
        ->assertFailed()
        ->expectsOutput('composer.json file not found. Check your Laravel installation.');
});

test('preserves existing configurations when merging configuration files', function () {
    File::put(base_path('package.json'), json_encode([
        'name' => 'app-existente',
        'private' => true,
        'scripts' => [
            'dev' => 'vite',
            'build' => 'vite build',
        ],
        'devDependencies' => [
            'vite' => '^4.0.0',
            '@commitlint/cli' => '^17.0.0', // Previous version that should be updated
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $this->artisan('quality-tools:install')->expectsQuestion('Select tools to install:', ['all']);

    expect(json_decode(File::get(base_path('package.json')), true))
        ->name->toBe('app-existente')
        ->scripts->toHaveKeys(['dev', 'build', 'prepare'])
        ->devDependencies->toHaveKeys(['vite', '@commitlint/cli'])
        ->toContain(...[
            '@commitlint/cli' => '^19.2.1',
        ]);
});

test('requests confirmation when files already exist and --force is not used', function () {
    File::put(base_path('phpstan.neon'), 'existing content');

    $this->artisan('quality-tools:install')
        ->expectsQuestion('Select tools to install:', ['phpstan'])
        ->expectsConfirmation('The file phpstan.neon already exists. Do you want to overwrite it?')
        ->expectsOutput('Installation of phpstan.neon canceled by the user.');
});

test('overwrites existing files when confirmed by the user', function () {
    File::put(base_path('phpstan.neon'), 'existing content');

    $this->artisan('quality-tools:install')
        ->expectsQuestion('Select tools to install:', ['phpstan'])
        ->expectsConfirmation('The file phpstan.neon already exists. Do you want to overwrite it?', 'yes')
        ->expectsOutput('File phpstan.neon installed successfully.');
});

test('creates directories for files when necessary', function () {
    File::deleteDirectory(base_path('.husky'));

    expect(File::exists(base_path('.husky')))->toBeFalse();

    $this->artisan('quality-tools:install', ['--force' => true])
        ->expectsQuestion('Select tools to install:', ['husky'])
        ->assertSuccessful();

    expect(File::exists(base_path('.husky')))->toBeTrue()
        ->and(File::exists(base_path('.husky/commit-msg')))->toBeTrue()
        ->and(File::exists(base_path('.husky/commit-msg')))->toBeTrue();
});

test('correctly handles package.json with different sections, including arrays', function () {
    File::put(base_path('package.json'), json_encode([
        'name' => 'app-test',
        'scripts' => [
            'dev' => 'vite',
        ],
        'config' => [
            'commitizen' => [
                'existing' => 'value',
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    $this->artisan('quality-tools:install', ['--force' => true])
        ->expectsQuestion('Select tools to install:', ['all'])
        ->assertSuccessful();

    expect(json_decode(File::get(base_path('package.json')), true))
        ->config
        ->toEqual([
            'commitizen' => [
                'existing' => 'value',
                'path' => './node_modules/cz-conventional-changelog',
            ],
        ]);
});
