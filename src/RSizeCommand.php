<?php

namespace JulianVe\ComposerRSize;

use Closure;
use Composer\Command\BaseCommand;
use Composer\Console\Input\InputArgument;
use Composer\Console\Input\InputOption;
use Composer\Json\JsonFile;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RSizeCommand extends BaseCommand
{
    const FORMAT_PLAIN = 'text';
    const FORMAT_JSON = 'json';

    protected function configure()
    {
        $this->setName('rsize')
            ->setDescription('Show the recursive size of your dependencies')
            ->setDefinition([
                new InputArgument('package', InputArgument::OPTIONAL, 'The package to inspect', null, $this->suggestPackage()),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'Format of the output: text or json',
                    static::FORMAT_PLAIN, [static::FORMAT_PLAIN, static::FORMAT_JSON]),
                new InputOption('dev', null, InputOption::VALUE_NONE, 'Include dev dependencies.'),
            ])
            ->setHelp(
                <<<EOT
The <info>rsize</info> command shows the recursive size of your dependencies.

<info>composer rsize [--format=json] [--dev] [package]</info>

Read more at https://getcomposer.org/doc/03-cli.md#archive
EOT
            );
    }

    protected function suggestPackage(): Closure
    {
        return function (CompletionInput $input) {
            return $this->getDirectDependencies(
                $input->getArgument('package'),
                $input->getOption('dev')
            );
        };
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getIO();
        $format = $input->getOption('format');
        if (!in_array($format, [static::FORMAT_PLAIN, static::FORMAT_JSON])) {
            $io->writeError('<error>Invalid format. Allowed values: text, json</error>');
            return static::FAILURE;
        }

        $packages = $this->getDirectDependencies(
            $input->getArgument('package'),
            $input->getOption('dev')
        );

        $result = [];
        foreach ($packages as $package) {
            $size = $this->calculateSize($package, $package, $packages);
            $result[] = $size;
        }

        usort($result, function (PackageSize $a, PackageSize $b) {
            return $b->getTotalSize() <=> $a->getTotalSize();
        });

        if ($format === static::FORMAT_JSON) {
            $io->write(JsonFile::encode($result));
            return static::SUCCESS;
        }

        $table = new Table($output);
        $table->setColumnWidths([30, 10, 10]);
        $table->setHeaders(['Package', 'Total Size', 'Added Size']);
        $table->setRows(array_map(function (PackageSize $packageSize) {
            return $packageSize->toRow();
        }, $result));
        $table->render();

        return static::SUCCESS;
    }

    /**
     * @param string|null $packageName The package to inspect
     * @return PackageInterface[]
     */
    protected function getDirectDependencies(?string $packageName, ?bool $includeDev = false): array
    {
        $composer = $this->requireComposer();

        $package = $composer->getPackage();
        if ($packageName) {
            $package = $this->getRepository()->findPackage($packageName, '*');
        }

        $requires = $package->getRequires();
        if ($includeDev) {
            $requires = array_merge($requires, $package->getDevRequires());
        }

        $packages = [];

        foreach ($requires as $name => $constraint) {
            $packageName = $this->getRepository()->findPackage($name, $constraint->getConstraint());
            if ($packageName) {
                $packages[] = $packageName;
            }
        }
        return $packages;
    }

    /**
     * @param PackageInterface $package
     * @param PackageInterface $root
     * @param PackageInterface[] $directDependencies
     * @return PackageSize
     */
    protected function calculateSize(PackageInterface $package, PackageInterface $root, array $directDependencies): PackageSize
    {
        $vendor = $this->requireComposer()->getConfig()->get('vendor-dir');
        $totalSize = $this->recursiveFileSize($vendor . "/" . $package->getName());
        $addedSize = $totalSize;

        foreach ($this->getDirectDependencies($package->getName()) as $dependency) {
            $dependencySize = $this->calculateSize($dependency, $root, $directDependencies)->getTotalSize();
            $totalSize += $dependencySize;
            foreach ($directDependencies as $directDependency) {
                if ($root === $directDependency) {
                    continue;
                }
                if ($this->isTransitiveDependency($dependency, $directDependency)) {
                    continue 2;
                }
            }
            $addedSize += $dependencySize;
        }

        return new PackageSize(
            $package,
            $totalSize,
            $addedSize
        );
    }

    protected function recursiveFileSize(string $directory): int
    {
        $size = 0;

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    protected function getRepository(): InstalledRepositoryInterface
    {
        return $this->requireComposer()->getRepositoryManager()->getLocalRepository();
    }

    protected function isTransitiveDependency(PackageInterface $dependency, PackageInterface $package): bool
    {
        if ($dependency->getName() === $package->getName()) {
            return true;
        }

        foreach ($package->getRequires() as $require) {
            if ($require->getTarget() === $dependency->getName()) {
                return true;
            }
        }

        return false;
    }
}
