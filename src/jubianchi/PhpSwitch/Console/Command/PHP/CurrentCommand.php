<?php
namespace jubianchi\PhpSwitch\Console\Command\PHP;

use Symfony\Component\Console;
use jubianchi\PhpSwitch\PHP\Version;
use jubianchi\PhpSwitch\Console\Command\Command;

class CurrentCommand extends Command
{
    const NAME = 'php:current';
    const DESC = 'Displays current PHP version';

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
	 * @throws \InvalidArgumentException
	 *
     * @return int
     */
    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        parent::execute($input, $output);

        try {
            $version = $this->getConfiguration()->get('version');
		} catch (\InvalidArgumentException $exception) {
			return $exception->getCode();
		}

		if (null !== $version) {
			$version = Version::fromString($version);
			if (true === $this->getApplication()->getService('app.php.installer')->isInstalled($version)) {
				$output->writeln((string) $version);
			} else {
				return 1;
			}
		}

        return 0;
    }
}