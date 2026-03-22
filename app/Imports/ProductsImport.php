<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * DEPRECATED: This class was part of the old sales/product system and is currently not used.
 */
class ProductsImport implements WithMultipleSheets
{
    public function __construct(array $titles) {}
    public function sheets(): array { return []; }
}
