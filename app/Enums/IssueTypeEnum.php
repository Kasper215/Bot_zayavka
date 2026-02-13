<?php

namespace App\Enums;

enum IssueTypeEnum: int
{
    case PROBLEM = 0;
    case SOLUTION=1;
    case DIFFICULTY=2;

}
