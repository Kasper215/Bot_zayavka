<?php

namespace App\Classes;

use App\Models\User;
use Carbon\Carbon;

class BusinessLogic
{
    public function method(): static
    {
        return $this;
    }

    public function truncateTitle($title, $maxLength = 30)
    {
        if (mb_strlen($title) > $maxLength) {
            return mb_substr($title, 0, $maxLength) . '…';
        }
        return $title;
    }


}
