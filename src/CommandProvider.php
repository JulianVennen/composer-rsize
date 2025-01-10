<?php

namespace Julian\ComposerRsize;

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
