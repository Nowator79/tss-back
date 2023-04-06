<?
use Bitrix\Main\Application,
    Godra\Api\Core\Init;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;
       
// автолоад классов с composer
require_once(Application::getDocumentRoot() . '/local/vendor/autoload.php');

Godra\Api\Events::register();

$eventManager = \Bitrix\Main\EventManager::getInstance();

$eventManager->addEventHandlerCompatible(
  'main',
  'OnProlog',
  ['Godra\\Api\\Core\\Init', 'run']
);

$eventManager->addEventHandler('catalog', 'OnGetOptimalPriceResult', function(&$result){

    global $USER;
    $rsUser = \CUser::GetByID($USER->GetID());
    $arUser = $rsUser->Fetch();
    Loader::includeModule("highloadblock");
    $hlbl = 67; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
    $entity_data_class = $entity->getDataClass();

    $rsData = $entity_data_class::getList(array(
        "select" => array("*"),
        "order" => array("ID" => "ASC"),
        "filter" => array("UF_KONTRAGENTSSYLKA"=>$arUser['XML_ID']),  // Задаем параметры фильтра выборки
    ));

    while($arData = $rsData->Fetch()){
        $cont_discount =  $arData["UF_SKIDKA"];
    }
    if($cont_discount) {
        $result['PRICE']['PRICE'] -= $result['PRICE']['PRICE'] * $cont_discount / 100;
    }
});

// после добавление элемента в highload-блок Договоры
$eventManager->addEventHandler('', 'DogovoraOnAfterAdd', 'OnAfterAdd');

function OnAfterAdd(\Bitrix\Main\Entity\Event $event) {
    //
}

?>