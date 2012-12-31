<?php
namespace jubianchi\PhpSwitch\Console\Command;

use jubianchi\PhpSwitch;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use jubianchi\PhpSwitch\Exception\DirectoryExistsException;

class InitCommand extends Command
{
    const NAME = 'init';
    const DESC = 'Initializes PhpSwitch environment';

    const INDENT = '    ';

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directories = array(
            $workspace = $this->getApplication()->getService('app.workspace.path'),
            $this->getApplication()->getService('app.workspace.downloads.path'),
            $this->getApplication()->getService('app.workspace.sources.path'),
            $installed = $this->getApplication()->getService('app.workspace.installed.path')
        );

        $status = 0;
        foreach ($directories as $directory) {
            try {
                if ($this->makeDirectory($directory)) {
                    $this->log(sprintf('Directory <info>%s</info> was created', $directory), \Monolog\Logger::INFO, $output);
                } else {
                    $this->log(sprintf('Directory <error>%s</error> was not created', $directory), \Monolog\Logger::ERROR, $output);
                    $status = 1;
                }
            } catch (DirectoryExistsException $exc) {
                $this->log(sprintf('Directory <info>%s</info> already exists', $directory), \Monolog\Logger::ERROR, $output);
            }
        }

        file_put_contents(
            $workspace . '/.phpswitchrc',
            $this->getApplication()->getService('app.twig')->render(
                'phpswitchrc.twig',
                array(
                    'path' => $this->getApplication()->getService('app.path'),
                    'installed' => $installed
                )
            )
        );

        $this->log(
            sprintf(
                'You should <info>source %s</info> to use phpswitch',
                $workspace . '/.phpswitchrc'
            )
        );

        return $status;
    }

    /**
     * @param string $path
     *
     * @throws \RuntimeException
     *
     * @return bool
     */
    protected function checkWriteAccess($path)
    {
        $write = is_writable($path);

        if (false === $write) {
            throw new \RuntimeException(sprintf('You don\'t have write access on %s', $path));
        }

        return $write;
    }

    /**
     * @param $path
     *
     * @throws \RuntimeException
     * @throws \jubianchi\PhpSwitch\Exception\DirectoryExistsException
     *
     * @return bool
     */
    protected function makeDirectory($path)
    {
        $this->checkWriteAccess(dirname($path));

        if (false === file_exists($path)) {
            $create = mkdir($path);

            if (false === $create) {
                throw new \RuntimeException(sprintf('Could not create directory %s', $path));
            }
        } else {
            throw new DirectoryExistsException($path);
        }

        return $create;
    }
}
