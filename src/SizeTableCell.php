<?php

namespace JulianVe\ComposerRSize;

use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableCellStyle;

class SizeTableCell extends TableCell
{
    public function __construct(string $value, array $options = [])
    {
        $options['style'] = $options['style'] ?? new TableCellStyle(['align' => 'right']);
        parent::__construct($value, $options);
    }
}
