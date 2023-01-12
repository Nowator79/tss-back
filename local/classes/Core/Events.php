<?
namespace Godra\Api\Core;

use Godra\Api\EventHandlers,
    \Bitrix\Main\EventManager as em;

class Events
{
    public static function init()
    {
        $eventManager = em::getInstance();

        # Глобальное меню
        $eventManager->addEventHandler(
            "main", "OnBuildGlobalMenu",[ EventHandlers\Admin\Handler::class, "addMenuItem"]
        );
    }
}
?>