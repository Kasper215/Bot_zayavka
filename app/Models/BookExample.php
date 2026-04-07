<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookExample extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'cover_path',
        'pdf_path',
        'tag',
        'order_index',
        'is_visible'
    ];
}
