<?php
namespace jubianchi\PhpSwitch\PHP;

use Symfony\Component\Process\Exception\ProcessFailedException;
use jubianchi\PhpSwitch\PHP\Option\OptionCollection;
use jubianchi\PhpSwitch\Process\Builder as ProcessBuilder;
use jubianchi\PhpSwitch\Event\Emitter;
use jubianchi\PhpSwitch\Event\Dispatcher;

class Builder extends Emitter
{
    /** @var string */
    private $directory;

    /** @var \jubianchi\PhpSwitch\Process\Builder */
    private $builder;

    /**
     * @param string                                $directory
     * @param \jubianchi\PhpSwitch\Process\Builder  $builder
     * @param \jubianchi\PhpSwitch\Event\Dispatcher $dispatcher
     */
    public function __construct($directory, ProcessBuilder $builder = null, Dispatcher $dispatcher = null)
    {
        $this->directory = $directory;
        $this->builder = $builder ?: new ProcessBuilder();

        if (null !== $dispatcher) {
            $this->setDispatcher($dispatcher);
        }
    }

    /**
     * @param \jubianchi\PhpSwitch\PHP\Version                 $version
     * @param string                                           $source
     * @param \jubianchi\PhpSwitch\PHP\Option\OptionCollection $options
     * @param int                                              $jobs
     *
     * @return \jubianchi\PhpSwitch\PHP\Builder
     */
    public function build(Version $version, $source, OptionCollection $options, $jobs = null)
    {
        $this->emit(
            'build.before',
            $args = array(
                'version' => $version,
                'source' => $source,
                'option' => $options,
                'jobs' => $jobs,
                'prefix' => $this->getDestination($version)
            )
        );

        $self = $this;
        $callback = function($type, $buffer) use ($self) {
            $buffer = rtrim($buffer);
            if (false === empty($buffer)) {
                $self->emit(
                    'build.progress',
                    array(
                        'type' => $type,
                        'buffer' => $buffer,
                    )
                );
            }
        };

        $this
            ->clean($source, $callback)
            ->configure($version, $source, $options, $callback)
            ->make($source, $jobs, $callback)
        ;

        $this->emit('build.after', $args);

        return $this;
    }

    /**
     * @param string   $source
     * @param callable $callback
     *
     * @return \jubianchi\PhpSwitch\PHP\Builder
     */
    public function clean($source, $callback = null)
    {
        if (true === file_exists($this->directory . DIRECTORY_SEPARATOR . 'Makefile')) {
            $this->builder->get()
                ->setWorkingDirectory($source)
                ->add('make')
                ->add('clean')
                ->getProcess()
                    ->run($callback)
            ;
        }

        return $this;
    }

    /**
     * @param \jubianchi\PhpSwitch\PHP\Version $version
     * @param string                           $source
     * @param array                            $options
     * @param callable                         $callback
     *
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     *
     * @return \jubianchi\PhpSwitch\PHP\Builder
     */
    public function configure(Version $version, $source, $options, $callback = null)
    {
        $prefix = $this->getDestination($version);

        if (null !== $callback) {
            $callback('init', $prefix);
        }

        $builder = $this->builder->get()
            ->setWorkingDirectory($source)
            ->add('./configure')
            ->add('--prefix=' . $prefix)
            ->add('--with-config-file-path=' . $prefix . '/etc')
            ->add('--with-config-file-scan-dir=' . $prefix . '/var/db')
            ->add('--with-pear=' . $prefix . '/lib/php')
        ;

        foreach (explode(' ', $options) as $option) {
            $builder->add((string) $option);
        }

        $process = $builder->getProcess();

        $process->run($callback);

        if (false === $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $this;
    }

    /**
     * @param string   $source
     * @param int      $jobs
     * @param callable $callback
     *
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     *
     * @return \jubianchi\PhpSwitch\PHP\Builder
     */
    public function make($source, $jobs = null, $callback = null)
    {
        $builder = $this->builder->get()
            ->setTimeout(null)
            ->setWorkingDirectory($source)
            ->add('make')
        ;

        if (null !== $jobs) {
            $builder->add('-j' . (int) $jobs);
        }

        $process = $builder->getProcess();
        $process->run($callback);
        if (false === $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $builder->add('install');
        $process = $builder->getProcess();
        $process->run($callback);
        if (false === $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $this;
    }

    /**
     * @param \jubianchi\PhpSwitch\PHP\Version $version
     *
     * @return string
     */
    public function getDestination(Version $version)
    {
        return $this->directory . DIRECTORY_SEPARATOR . $version->getName() . '-' . $version->getVersion();
    }
}
