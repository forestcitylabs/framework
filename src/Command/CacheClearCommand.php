<?php

declare(strict_types=1);

/*
 * This file is part of the Forest City Labs Framework package.
 * (c) Forest City Labs <https://forestcitylabs.ca/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ForestCityLabs\Framework\Command;

use Psr\Cache\CacheItemPoolInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CacheClearCommand extends Command
{
    public function __construct(
        private array $pools,
        private array $paths = []
    ) {
        parent::__construct('cache:clear');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        foreach ($this->pools as $pool) {
            assert($pool instanceof CacheItemPoolInterface);
            $pool->clear();
        }

        foreach ($this->paths as $path) {
            foreach (glob($path) as $item) {
                if (is_dir($item)) {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($item, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST,
                    );

                    foreach ($iterator as $rm) {
                        if ($rm->isDir()) {
                            rmdir($rm->getRealPath());
                        } else {
                            unlink($rm->getRealPath());
                        }
                    }
                } else {
                    unlink($item);
                }
            }
        }

        $io->success('Cache cleared!');

        return Command::SUCCESS;
    }
}
