<?php

namespace App\Classes;

enum BotStatusEnum:int
{
    case Working = 0;
    case InMaintenance = 1;
}
