<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Command;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class LoadFixturesCommand extends Command
{
    public function __construct(
        private ORMExecutor $executor,
        private Loader $loader
    ) {
        parent::__construct('fixtures:load');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->caution('WARNING: This will purge your database!');

        if ($io->confirm('Load fixtures?', false)) {
            $this->executor->execute($this->loader->getFixtures());
            $io->success('Fixtures loaded!');
            return Command::SUCCESS;
        }

        $io->error('Aborted.');

        return Command::SUCCESS;
    }
}
