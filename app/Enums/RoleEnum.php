<?php

namespace App\Enums;

enum RoleEnum: int
{
    case USER = 0;
    case MANAGER = 1;
    case ADMIN = 2;
}
