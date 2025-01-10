<?php

namespace JulianVe\ComposerRSize;

use Composer\Package\Package;

class PackageSize implements \JsonSerializable
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

    public static function formatSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $factor = floor((strlen($size) - 1) / 3);
        return sprintf("%.2f %s", $size / (1024 ** $factor), $units[$factor]);
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

    public function toRow(): array
    {
        return [
            $this->package->getName(),
            new SizeTableCell($this->formatSize($this->totalSize)),
            new SizeTableCell($this->formatSize($this->addedSize)),
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->package->getName(),
            'totalSize' => $this->totalSize,
            'addedSize' => $this->addedSize,
        ];
    }
}
