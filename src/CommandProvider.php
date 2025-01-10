<?php

namespace JulianVe\ComposerRSize;

use Composer\Plugin\Capability;

class CommandProvider implements Capability\CommandProvider
{

    public function getCommands()
    {
        return [
            new RSizeCommand()
        ];
    }
}
