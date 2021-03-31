<?php

namespace NeutronStars\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class DoctrineDatabaseRestoreCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('doctrine:database:restore')
            ->setDescription('Drop and recreate the database and reload the migrations')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Set this parameter to execute this action')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if(!class_exists('App\\Kernel'))
        {
            $output->writeln('<error>This command must be executed in a symfony project !</error>');
            return Command::FAILURE;
        }
        $command = $this->getApplication()->find('doctrine:database:drop');
        if(($error = $command->run($input, $output)) !== Command::SUCCESS) {
            return $error;
        }
        $command = $this->getApplication()->find('doctrine:database:create');
        if(($error = $command->run(new ArrayInput([]), $output)) !== Command::SUCCESS) {
            return $error;
        }
        $count = 0;
        foreach (scandir(__DIR__.'/../../migrations') as $file) {
            if(pathinfo($file)['extension'] === 'php') {
                unlink(__DIR__.'/../../migrations/'.$file);
                $count++;
            }
        }
        $output->writeln('<comment>'.$count.' migration(s) deleted.</comment>');

        $command = $this->getApplication()->find('make:migration');
        if(($error = $command->run(new ArrayInput(['command' => 'make:migration']), $output)) !== Command::SUCCESS) {
            return $error;
        }

        $kernel = new \App\Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
        $application = new Application($kernel);

        $command = $application->find('doctrine:migrations:migrate');

        if(($error = $command->run(new ArrayInput([]), $output)) !== Command::SUCCESS) {
            return $error;
        }

        $output->writeln('<info>Success !</info>');
        return Command::SUCCESS;
    }
}
