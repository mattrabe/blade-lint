<?php

namespace MattRabe\BladeLint\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class Lint extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blade:lint
                                {--F|fix}
                                {paths=./resources/views/**/*.php}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lint blade files.';

    /**
     * Execute the console command.
     *
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     *
     * @return void
     */
    public function handle(Filesystem $filesystem)
    {
        $this->info('🔪🔪🔪  Blade Lint 🔪🔪🔪');

        // Read rc file to config array
        $rcFilePath = getcwd().'/.bladelintrc.yaml';
        $config = [];
        if ($filesystem->exists($rcFilePath)) {
            $config = Yaml::parse(file_get_contents($rcFilePath));
        } else {
            $this->info('⚠️  No .bladelintrc.yaml file found. Using defaults.');
        }

        // Gather list of paths
        $paths = $this->argument('paths');
        $this->info('🔍  Scanning for files ('.$paths.')...');
        $paths = explode(',', $paths);
        foreach ($paths as $i => $path) {
            $paths[$i] = [
                'path' => preg_replace('/^\./', '', preg_replace('/\*\*\//', '', $path)),
                'isRecursive' => preg_match('/\*\*/', $path),
            ];
        }

        // Get list of files
        $files = self::getFiles($paths);
        if (isset($config['parameters']['exclude_files'])) {
            $files = self::excludeFiles($files, $config['parameters']['exclude_files']);
        }
        $this->info(count($files).' files found.');

        // Gather list of rules
        $rules = isset($config['rules']) ? collect($config['rules'])->reduce(function($acc, $value) {
            if (is_array($value) && array_values($value)[0] === false) {
                return $acc;
            }

            $acc[] = (object)(is_array($value) ? [
                'name' => array_keys($value)[0],
                'options' => array_values($value)[0],
            ] : [
                'name' => $value,
                'options' => null,
            ]);

            return $acc;
        }, []) : [];

        $results = $this->executeRules($rules, $files);
        $this->info('✨  '.collect($results)->filter(function($result) {
            return collect($result['rulesExecuted'])->first(function($ruleExecuted) {
                return $ruleExecuted['fileWritten'];
            });
        })->count().' files fixed.');
    }

    private static function getFiles(array $paths): array {
        $files = [];

        foreach ($paths as $path) {
            $files = array_merge($files, self::glob(getcwd().$path['path'], $path['isRecursive']));
        }

        return $files;
    }

    private static function glob(string $pattern = '', bool $isRecursive): array {
        $files = glob($pattern);

        if ($isRecursive) {
            foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
                $files = array_merge($files, self::glob($dir.'/'.basename($pattern), true));
            }
        }

        return $files;
    }

    private static function excludeFiles(array $files, array $exclusions): array {
        foreach ($exclusions as $pattern) {
            $pattern = str_replace('/', '\/', $pattern);

            $files = preg_grep('/^((?!'.$pattern.').)*$/', $files);
        }

        return $files;
    }

    private function executeRules(array $rules, array $files): array
    {
        $results = [];

        foreach ($files as $file) {
            $results[$file] = [ 'rulesExecuted' => [] ];

            foreach ($rules as $rule) {
                $className = strstr($rule->name, '\\') ? $rule->name : '\MattRabe\BladeLint\Rules\\'.$rule->name;
                $ruleClass = new $className($file, $rule->options);

                $result = $ruleClass->test();

                $result['fileWritten'] = $this->option('fix') ? $ruleClass->fix() : false;

                $results[$file]['rulesExecuted'][$rule->name] = $result;
            }
        }

        return $results;
    }
}
