<?
namespace Godra\Api\Helpers\Debug;

class Debug
{
    public static function getObjectMethods($obj)
    {
        return get_class_methods(get_class($obj));
    }
}
?>