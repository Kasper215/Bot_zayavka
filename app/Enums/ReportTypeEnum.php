<?php

namespace App\Enums;

enum ReportTypeEnum : int
{
    case INCOMING = 0;
    case RESULT = 1;
    case EVENT = 2;

}
