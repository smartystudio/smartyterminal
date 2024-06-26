<?php

namespace SmartyStudio\SmartyTerminal\Console;

use Exception;
use Illuminate\Contracts\Console\Kernel as KernelContract;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Support\Arr;
use SmartyStudio\SmartyTerminal\Console\Application as Artisan;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Kernel implements KernelContract
{
    /**
     * The Artisan application instance.
     *
     * @var \Illuminate\Console\Application
     */
    protected \Illuminate\Console\Application $artisan;

    /**
     * $config.
     *
     * @var array
     */
    protected array $config;

    /**
     * The Artisan commands provided by the application.
     *
     * @var array
     */
    protected array $commands = [];

    /**
     * Create a new console kernel instance.
     *
     * @param Application $artisan
     * @param array $config
     */
    public function __construct(Artisan $artisan, array $config = [])
    {
        $this->artisan = $artisan;
        $this->config = Arr::except(array_merge([
            'username' => config(addslashes('app.name')), 'hostname' => php_uname('n'), 'os' => PHP_OS,
        ], $config), ['enabled', 'whitelists', 'route', 'commands']);
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Bootstrap the application for artisan commands.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        //
    }

    /**
     * Handle an incoming console command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     *
     * @throws Exception
     */
    public function handle($input, $output = null): int
    {
        $this->bootstrap();

        return $this->artisan->run($input, $output);
    }

    /**
     * Run an Artisan console command by name.
     *
     * @param string $command
     * @param array $parameters
     * @param OutputInterface $outputBuffer
     * @return int
     *
     * @throws Exception
     */
    public function call($command, array $parameters = [], $outputBuffer = null): int
    {
        $this->bootstrap();

        return $this->artisan->call($command, $parameters, $outputBuffer);
    }

    /**
     * Queue an Artisan console command by name.
     *
     * @param string $command
     * @param array $parameters
     * @return PendingDispatch
     */
    public function queue($command, array $parameters = []): PendingDispatch
    {
        $this->bootstrap();

        if (class_exists(QueuedCommand::class)) {
            return QueuedCommand::dispatch(func_get_args());
        }

        $app = $this->artisan->getLaravel();
        $app[Queue::class]->push(
            'Illuminate\Foundation\Console\QueuedJob',
            func_get_args()
        );
    }

    /**
     * Get all the commands registered with the console.
     *
     * @return array
     */
    public function all(): array
    {
        $this->bootstrap();

        return $this->artisan->all();
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output(): string
    {
        $this->bootstrap();

        return $this->artisan->output();
    }

    /**
     * Terminate the application.
     *
     * @param InputInterface $input
     * @param int $status
     * @return void
     */
    public function terminate($input, $status): void
    {
        $this->bootstrap();
        $this->artisan->terminate();
    }
}
