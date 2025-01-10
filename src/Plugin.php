<?php

namespace Julian\ComposerRsize;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, Capable
{

    public function activate(Composer $composer, IOInterface $io)
    {
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    public function getCapabilities()
    {
        return [
            Capability\CommandProvider::class => CommandProvider::class,
        ];
    }
}
