<?php
namespace jubianchi\PhpSwitch\Console\Command\PHP;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use jubianchi\PhpSwitch\Console\Command\Command;
use jubianchi\PhpSwitch\PHP;

class REPLCommand extends Command
{
    const NAME = 'php:repl';
    const DESC = 'Enters PHP REPL';

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @throws \RuntimeException
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if (false === extension_loaded('pcntl')) {
            throw new \RuntimeException('PCNTL extension is not enabled');
        }

        $version = $this->getConfiguration()->get('version', phpversion());
        $boris = new \Boris\Boris($output->getFormatter()->format(sprintf('<info>%s →</info> ', $version)));
        $boris->start();

        return 0;
    }
}
