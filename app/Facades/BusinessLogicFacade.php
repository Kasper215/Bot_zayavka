<?php

namespace App\Facades;

use App\Classes\BusinessLogic;
use Illuminate\Support\Facades\Facade;

/**
 * Class BusinessLogicFacade
 *
 *
 * @method static BusinessLogic method()
 */
class BusinessLogicFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'businesslogic'; // связывается с псевдонимом в service provider
    }
}

