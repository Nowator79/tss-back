<?
use Bitrix\Main\Application,
    Godra\Api\Core\Init;
       
// автолоад классов с composer
require_once(Application::getDocumentRoot() . '/local/vendor/autoload.php');

Godra\Api\Events::register();

$eventManager = \Bitrix\Main\EventManager::getInstance();

$eventManager->addEventHandlerCompatible(
  'main',
  'OnProlog',
  ['Godra\\Api\\Core\\Init', 'run']
);

// после добавление элемента в highload-блок Договоры
$eventManager->addEventHandler('', 'DogovoraOnAfterAdd', 'OnAfterAdd');

function OnAfterAdd(\Bitrix\Main\Entity\Event $event) {
    //
}

?>