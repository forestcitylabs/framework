<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Command;

use ForestCityLabs\Framework\Session\SessionDriverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SessionClearCommand extends Command
{
    public function __construct(
        private SessionDriverInterface $driver
    ) {
        parent::__construct('session:clear');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->driver->deleteAll();
        $io->success('All sessions deleted!');
        return Command::SUCCESS;
    }
}
