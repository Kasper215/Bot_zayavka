<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ProductsImport implements WithMultipleSheets
{
    public $titles;

    public function __construct(array $titles)
    {
        $this->titles = $titles;
    }

    public function sheets(): array
    {
        $tmp = [];
        foreach ($this->titles as $title)
        {
            $tmp[] =    new ProductsSheetImport($title);
        }
        return $tmp;
    }
}
