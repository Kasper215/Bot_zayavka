<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Row;

class ProductsSheetImport  implements OnEachRow, WithHeadingRow, WithTitle
{

    public $title;

    public function __construct($title)
    {
        $this->title = $title;

    }

    public function onRow(Row $row)
    {
        $row = $row->toArray();

        $category = ProductCategory::firstOrCreate([
            'name' => $this->title
        ]);

        $supplier = Supplier::firstOrCreate([
            'name' => $row['postavshhik']
        ]);

        Product::updateOrCreate(
            ['id' => $row['id']], // если ID уникален
            [
                'name' => $row['nazvanie'],
                'description' => $row['opisanie'],
                'price' => $row['cena'],
                'count' => $row['kolicestvo'],
                'supplier_id' => $supplier->id,
                'product_category_id' => $category->id,
            ]
        );
    }

    public function title(): string
    {
        return $this->title;
    }
}
