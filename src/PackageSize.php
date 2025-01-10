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
    protected $totalSize;

    /**
     * @var int
     */
    protected $addedSize;

    public function __construct(
        Package $package,
        int     $size,
        int     $addedSize
    )
    {
        $this->package = $package;
        $this->totalSize = $size;
        $this->addedSize = $addedSize;
    }

    public function getPackage(): Package
    {
        return $this->package;
    }

    public function getTotalSize(): int
    {
        return $this->totalSize;
    }

    public function getAddedSize(): int
    {
        return $this->addedSize;
    }

    public function formatSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($size) - 1) / 3);
        return sprintf("%.2f %s", $size / (1024 ** $factor), $units[$factor]);
    }

    public function format(): string
    {
        return sprintf('%s: %s', $this->package->getName(), $this->getPrettySize());
    }

    public function toRow(): array
    {
        return [
            $this->package->getName(),
            new SizeTableCell($this->formatSize($this->totalSize)),
            new SizeTableCell($this->formatSize($this->addedSize)),
        ];
    }
}
