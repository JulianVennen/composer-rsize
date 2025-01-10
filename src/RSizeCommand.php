<?php

namespace Julian\ComposerRsize;

use Closure;
use Composer\Command\BaseCommand;
use Composer\Composer;
use Composer\Console\Input\InputArgument;
use Composer\Console\Input\InputOption;
use Composer\Package\Package;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Repository\RootPackageRepository;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RSizeCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('rsize')
            ->setDescription('Show the recursive size of your dependencies')
            ->setDefinition([
                new InputArgument('package', InputArgument::OPTIONAL, 'The root package to show the size of', null, $this->suggestPackage()),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'Format of the output: text or json', 'text', ['json', 'text']),
                new InputOption('dev', null, InputOption::VALUE_NONE, 'Include dev dependencies.'),
            ])
            ->setHelp(
                <<<EOT
The <info>rsize</info> command shows the recursive size of your dependencies.

<info>composer rsize [--format=json] [--dev] [package]</info>

Read more at https://getcomposer.org/doc/03-cli.md#archive
EOT
            );
        // TODO: show size
        // TODO: show size of a specific package
        // TODO: show only diff size
        // TODO: show total size
        // TODO: ignore dev dependencies
        // TODO: json output
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
        $packages = $this->getDirectDependencies(
            $input->getArgument('package'),
            $input->getOption('dev')
        );

        $result = [];
        foreach ($packages as $package) {
            $result[] = $this->calculateSize($package);
        }

        uasort($result, function (PackageSize $a, PackageSize $b) {
            return $b->getSize() <=> $a->getSize();
        });

        $table = new Table($output);
        $table->setColumnWidths([30, 10, 10]);
        $table->setHeaders(['Package', 'Size', 'Diff']);
        $table->setRows(array_map(function (PackageSize $packageSize) {
            return $packageSize->toRow();
        }, $result));
        $table->render();

        return self::SUCCESS;
    }

    /**
     * @param string|null $packageName The root package to inspect
     * @return Package[]
     */
    protected function getDirectDependencies(?string $packageName, ?bool $includeDev = false): array
    {
        $composer = $this->requireComposer();

        $root = $composer->getPackage();
        if ($packageName) {
            $root = $this->getRepository()->findPackage($packageName, '*');
        }

        $requires = $root->getRequires();
        if ($includeDev) {
            $requires = array_merge($requires, $root->getDevRequires());
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

    protected function calculateSize(Package $package): PackageSize
    {
        $vendor = $this->requireComposer()->getConfig()->get('vendor-dir');
        $size = $this->recursiveFileSize($vendor . "/" . $package->getName());

        foreach ($this->getDirectDependencies($package->getName()) as $dependency) {
            $size += $this->calculateSize($dependency)->getSize();
        }
        // TODO: diff size

        return new PackageSize(
            $package,
            $size
        );
    }

    protected function recursiveFileSize(string $directory): int
    {
        $size = 0;

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    protected function getRepository(): InstalledRepositoryInterface
    {
        return $this->requireComposer()->getRepositoryManager()->getLocalRepository();
    }
}
