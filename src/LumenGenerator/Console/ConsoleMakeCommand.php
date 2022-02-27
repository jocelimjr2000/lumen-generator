<?php

namespace JocelimJr\LumenGenerator\Console;

use Illuminate\Console\Concerns\CreatesMatchingTest;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ConsoleMakeCommand extends GeneratorCommand
{
    use CreatesMatchingTest;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:command';

    /**
     * The name of the console command.
     *
     * This name is used to identify the command during lazy loading.
     *
     * @var string|null
     */
    protected static $defaultName = 'make:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Artisan command';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Console command';
    protected $kernel;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        if (parent::handle() === false && ! $this->option('force')) {
            return false;
        }

        if($this->argument('kernel') && $this->argument('kernel') == true){
            $this->appendKernel($this->qualifyClass($this->getNameInput()));
        }
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name, $kernel = null)
    {
        $this->kernel = $kernel;

        $stub = parent::replaceClass($stub, $name);

        return str_replace(['dummy:command', '{{ command }}'], $this->option('command'), $stub);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        $relativePath = '/stubs/console.stub';

        return file_exists($customPath = $this->laravel->basePath(trim($relativePath, '/')))
            ? $customPath
            : __DIR__.$relativePath;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Console\Commands';
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the command'],
            ['kernel', InputArgument::OPTIONAL, 'Include class on kernel file (true|false)'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['command', null, InputOption::VALUE_OPTIONAL, 'The terminal command that should be assigned', 'command:name']
        ];
    }
    
    /**
     * Include class on app/Console/Kernel.php
     * 
     * @return void
     */
    protected function appendKernel(string $completeName)
    {
        $kernelFile = $this->laravel->basePath('app/Console/Kernel.php');
        $kernelContent = $this->files->get($kernelFile);

        preg_match('/protected \$commands = \[(.*?)\];/s', $kernelContent, $commands);
        $currentCommands = $commands[0];
        
        $classes = trim($commands[1]);
        $classes = preg_replace('/\s+/', '', $classes);

        if($classes == '//' || empty($classes)){
            $classes = '';
        }else if(substr($classes, -1) !== ','){
            $classes .= ',';
        }

        $classes .= '\\' . $completeName . '::class';
        
        $newCommands = 'protected $commands = [' . PHP_EOL .
                        "\t\t" . str_replace(',', ',' . PHP_EOL . "\t\t", $classes) . PHP_EOL .
                        "\t];";

        $this->files->put($kernelFile, str_replace($currentCommands, $newCommands, $kernelContent));
    }

}
