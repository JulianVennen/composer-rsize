<?php

namespace Julian\ComposerRsize;

use Composer\Package\Package;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;
use Symfony\Component\Console\Helper\TableStyle;

class PackageSize
{
    /**
     * @var Package
     */
    protected $package;

    /**
     * @var int
     */
    protected $size;

    public function __construct(
        Package $package,
        int     $size
    )
    {
        $this->size = $size;
        $this->package = $package;
    }

    public function getPackage(): Package
    {
        return $this->package;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getPrettySize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($this->size) - 1) / 3);
        return sprintf("%.2f %s", $this->size / (1024 ** $factor), $units[$factor]);
    }

    public function format(): string
    {
        return sprintf('%s: %s', $this->package->getName(), $this->getPrettySize());
    }

    public function toRow(): array
    {
        return [
            $this->package->getName(),
            new SizeTableCell($this->getPrettySize()),
        ];
    }
}
