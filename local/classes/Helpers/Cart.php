<?

namespace Godra\Api\Helpers;

use CModule;

class Cart
{
    function __construct()
    {
        CModule::IncludeModule('sale');
    }
    
    public function OnBeforeBasketAddHandler(&$arFields)
    {
        //
    }
    
    public function OnBeforeBasketUpdateHandler(&$arFields)
    {
        //
    }
    
    public function OnBeforeBasketDeleteHandler(&$arFields)
    {
        //
    }
}
