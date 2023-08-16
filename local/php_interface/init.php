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

//пример использования события OnSaleOrderSaved

    ////в обработчике получаем сумму, с которой планируются некоторые действия в дальнейшем:
    //function myFunction(Main\Event $event)
    //{
//        $order = \Bitrix\Sale\Order::load(146);
//        $order->setField("USER_DESCRIPTION", "Доставить к подъезду");
//        $shipmentCollection = $order->getShipmentCollection();
//        /** \Bitrix\Sale\Shipment $shipment */
//        foreach ($shipmentCollection as $shipment)
//        {
////           if (!$shipment->isSystem()){
//                $shipment->setStoreId(154);
//            }
//            AddMessage2Log($shipment->getStoreId());
//
//        }
//        $order->save();
    //}

use Bitrix\Main\DB\Connection;
function setStore2Shipment($data){
    global $DB;
    $DB->PrepareFields("b_sale_store_barcode");

    $arFields = $data;
    $DB->StartTransaction();
    $ID = $DB->Insert("b_sale_store_barcode", $arFields, $err_mess.__LINE__);
    $ID = intval($ID);

    $DB->Commit();
    return $ID;
// Получение экземпляра подключения к базе данных
//    $connection = Application::getConnection();
//
//// Выполнение вставки
//    $tableName = 'b_sale_store_barcode '; // Замените на имя вашей таблицы
//    $affectedRowsCount = $connection->insert($tableName, $data);
//    return $affectedRowsCount;
}

function getStoreInfo($orderId){
    Bitrix\Main\Loader::includeModule("sale");
    Bitrix\Main\Loader::includeModule("catalog");

    $order = \Bitrix\Sale\Order::load($orderId);
    $shipmentCollection = $order->getShipmentCollection();
    /** \Bitrix\Sale\Shipment $shipment */


    $data = [];
    foreach ($shipmentCollection as $shipment)
    {

        $shipmentItemCollection = $shipment->getShipmentItemCollection();

        foreach ($shipmentItemCollection as $shipmentItem)
        {

            $shipmentItemStoreCollection = $shipmentItem->getShipmentItemStoreCollection();

            $basketItem = $shipmentItem->getBasketItem();
            $quantity = $basketItem->getQuantity();

            $shipmentItemStore = $shipmentItemStoreCollection->createItem($basketItem);
            $orderDeliveryBasketId = $shipmentItemStore->getField('ORDER_DELIVERY_BASKET_ID');
            $basketId = $shipmentItemStore->getField('BASKET_ID');
            $productId = $basketItem->getField('PRODUCT_ID');
            $storeId = getStoreIdFromHL($productId);

            if ($storeId){
                $data = [
                    'QUANTITY' => $quantity,
                    'ORDER_DELIVERY_BASKET_ID' => $orderDeliveryBasketId,
                    'BASKET_ID' => $basketId,
                    'STORE_ID' => $storeId,
                ];
                setStore2Shipment($data);
            }
        }
    }
}

 function getStoreIdFromHL($id){
     $hlbl = 68; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
     $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
     $resultId = false;
     global $USER;
     $entity = HL\HighloadBlockTable::compileEntity($hlblock);
     $entity_data_class = $entity->getDataClass();

     $rsData = $entity_data_class::getList(array(
         "select" => array("ID", "UF_STORE_ID"),
         "order" => array("ID" => "ASC"),
         "filter" => array("UF_ITEM_ID"=>$id,"UF_USER_ID"=>$USER->GetID())  // Задаем параметры фильтра выборки
     ));

     while($arData = $rsData->Fetch()){
         if (!empty($arData['UF_STORE_ID']))
         $resultId = $arData['UF_STORE_ID'];
     }

    return $resultId;
}

AddEventHandler("sale", "OnOrderSave", "OnOrderSaveHandler");
function OnOrderSaveHandler($orderId) {

    getStoreInfo($orderId);
}

//// после добавление элемента в highload-блок Договоры
//$eventManager->addEventHandler('', 'DogovoraOnAfterAdd', 'OnAfterAdd');
//
//function OnAfterAdd(\Bitrix\Main\Entity\Event $event) {
//    //
//}


$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler('', 'LKDDopolnitelnyeSkidkiKontragentovOnAfterAdd', 'OnAfterAdd');

function OnAfterAdd(\Bitrix\Main\Entity\Event $event) {
//id добавляемого элемента
    $id = $event->getParameter("id");

// получаем массив полей хайлоад блока
    $arFields = $event->getParameter("fields");
    $result = getDiscountProductId($arFields['UF_GRUPPA']);
    if (!empty($result)) {
        if (checkDateInRange($arFields['UF_NACHALOPERIODADEY'],$arFields['UF_OKONCHANIEPERIODA'] )) {


            if (!empty($result[0]['UF_MOSHCHNOSTOT'])) {
                $filter['>PROPERTY_MOSHCHNOST_NOMINALNAYA_KVA'] = $result[0]['UF_MOSHCHNOSTOT'];
            }

            if (!empty($result[0]['UF_MOSHCHNOSTDO'] && $result[0]['UF_MOSHCHNOSTDO'] !== 0)) {
                $filter['<PROPERTY_MOSHCHNOST_NOMINALNAYA_KVA'] = $result[0]['UF_MOSHCHNOSTDO'];
            }

            if (!empty($result[0]['UF_SERIIPRODUKTSII'])) {
                if (strpos($result['0']['UF_SERIIPRODUKTSII'], '|')) {
                    $seria = explode('|', $result['0']['UF_SERIIPRODUKTSII']);
                    foreach ($seria as $serKey => $serItem) {
                        if (empty($serItem)) {
                            unset($seria[$serKey]);
                        }
                    }
                    $filter['PROPERTY_SERIYA_VALUE'] = $seria;
                } else {
                    $filter['PROPERTY_SERIYA_VALUE'] = $result['0']['UF_SERIIPRODUKTSII'];
                }
            }



            if(!empty($result[0]['UF_NOMENKLATURNYEGRU'])){
                if (strpos($result['0']['UF_NOMENKLATURNYEGRU'], '|')) {
                    $nomGruppa = explode('|', $result['0']['UF_NOMENKLATURNYEGRU']);
                    foreach ($nomGruppa as $nomGruppaKey => $nomGruppaItem) {
                        if (empty($nomGruppaItem)) {
                            unset($nomGruppa[$nomGruppaKey]);
                        }
                    }
                    $filter['PROPERTY_NOMENKLATURNAYA_GRUPPA_VALUE'] = $nomGruppa;
                } else {
                    $filter['PROPERTY_NOMENKLATURNAYA_GRUPPA_VALUE'] = $result['0']['UF_NOMENKLATURNYEGRU'];
                }
            }

            if(!empty($result[0]['UF_VIDYNOMENKLATURY'])){
                if (strpos($result['0']['UF_VIDYNOMENKLATURY'], '|')) {
                    $vidNom = explode('|', $result['0']['UF_VIDYNOMENKLATURY']);
                    foreach ($vidNom as $vidNomKey => $vidNomItem) {
                        if (empty($vidNomItem)) {
                            unset($vidNom[$vidNomKey]);
                        }
                    }
                    $filter['PROPERTY_VID_NOMENKLATURY_VALUE'] = $vidNom;
                } else {
                    $filter['PROPERTY_VID_NOMENKLATURY_VALUE'] = $result['0']['UF_VIDYNOMENKLATURY'];
                }
            }
            if(!empty($result[0]['UF_ISKLYUCHENIYA'])){
                if (strpos($result['0']['UF_ISKLYUCHENIYA'], '|')) {
                    $iscluch = explode('|', $result['0']['UF_ISKLYUCHENIYA']);
                    foreach ($iscluch as $iscluchKey => $iscluchItem) {
                        if (empty($iscluchItem)) {
                            unset($iscluch[$iscluchKey]);
                        }
                    }
                    $filter['!XML_ID'] = $iscluch;
                } else {
                    $filter['!XML_ID'] = $result['0']['UF_ISKLYUCHENIYA'];
                }
            }

            if (!empty($result[0]['UF_DOPOLNENIYA'])){
                $filter = array(0 => [
                    'LOGIC' => 'OR',
                    $filter,
                    'XML_ID'=> explode('|', $result[0]['UF_DOPOLNENIYA']),
                ]);
            }


            $arFilter = [
                "IBLOCK_ID" => IBLOCK_CATALOG,
                "ACTIVE" => 'Y'
            ];
            if (!empty($filter)){
                $arFilter = array_merge($arFilter,$filter);
            }


            $ids = getAssortimentDiscount($arFilter);
            $data = [];
            foreach ($ids as $id ){
                $data[] = [
                    'UF_PRODUCT_ID' => $id,
                    'UF_USER_ID' => $arFields['UF_KONTRAGENT'],
                    'UF_DATE_START' => $arFields['UF_NACHALOPERIODADEY'],
                    'UF_DATE_END' => $arFields['UF_OKONCHANIEPERIODA'],
                    'UF_SKIDKA' => $arFields['UF_SKIDKA'],
                ];
            }

            foreach ($data as $datum){
                setDiscount2HL($datum);
            }
        }
    }
}


function deleteDataFromHL(){
    Loader::includeModule("highloadblock");
    $hlbl = 70; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
    $resultId = false;
    global $USER;
    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
    $entity_data_class = $entity->getDataClass();

    $rsData = $entity_data_class::getList(array(
        "select" => array("ID", "UF_GRUPPA"),
        "order" => array("ID" => "ASC"),// Задаем параметры фильтра выборки

    ));

    while($arData = $rsData->Fetch()){
        $entity_data_class::Delete($arData['ID']);
    }

    deleteDiscountHL();
    deleteDiscountGroupHL();

    return 'deleteDataFromHL();';
}



function deleteDiscountHL(){
    Loader::includeModule("highloadblock");
    $hlbl = 73; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
    $resultId = false;
    global $USER;
    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
    $entity_data_class = $entity->getDataClass();

    $rsData = $entity_data_class::getList(array(
        "select" => array("ID"),
        "order" => array("ID" => "ASC"),// Задаем параметры фильтра выборки
    ));


    while($arData = $rsData->Fetch()){
        $entity_data_class::Delete($arData['ID']);
    }

}

function deleteDiscountGroupHL(){
    Loader::includeModule("highloadblock");
    $hlbl = 69; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
    $resultId = false;
    global $USER;
    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
    $entity_data_class = $entity->getDataClass();

    $rsData = $entity_data_class::getList(array(
        "select" => array("ID"),
        "order" => array("ID" => "ASC"),// Задаем параметры фильтра выборки
    ));


    while($arData = $rsData->Fetch()){
        $entity_data_class::Delete($arData['ID']);
    }

}

function deleteSkidkiConnectHL(){
    Loader::includeModule("highloadblock");
    $hlbl = HIGHLOAD_SKIDI_CONNECT; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
    $resultId = false;
    global $USER;
    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
    $entity_data_class = $entity->getDataClass();

    $rsData = $entity_data_class::getList(array(
        "select" => array("ID"),
        "order" => array("ID" => "ASC"),// Задаем параметры фильтра выборки
        "filter" => array("<UF_DATE_END" => date("d.m.Y H:i:s")),
    ));

    $result = [];

    while($arData = $rsData->Fetch()){
        $entity_data_class::Delete($arData['ID']);
    }
}


function setDiscount2HL($data){
    $hlbl = HIGHLOAD_SKIDI_CONNECT; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
    $entity_data_class = $entity->getDataClass();

    $result = $entity_data_class::add($data);

}

function getAssortimentDiscount($filter){
    $arSelect = ['ID'];
    $res = CIBlockElement::GetList(Array(), $filter, false, false, $arSelect);
    $ids = [];
    while($ob = $res->GetNextElement())
    {
        $arFields = $ob->GetFields();

        $ids[] = $arFields['ID'];

    }
    return $ids;
}


function checkDateInRange($startDateStr, $endDateStr) {
    // Преобразуем строки с датами в объекты DateTime
    $startDate = DateTime::createFromFormat('d.m.Y H:i:s', $startDateStr);
    $endDate = DateTime::createFromFormat('d.m.Y H:i:s', $endDateStr);

    // Получаем текущую дату и время
    $currentDate = new DateTime();

    // Проверяем, входит ли текущая дата в промежуток между $startDate и $endDate
    if ($startDate <= $currentDate && $currentDate <= $endDate) {
        return true;
    } else {
        return false;
    }
}

function getDiscountProductId($grupId){
    $hlbl = 69; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
    $entity_data_class = $entity->getDataClass();

    $rsData = $entity_data_class::getList(array(
        "select" => array("*"),
        "order" => array("ID" => "ASC"),
        "filter" => array("UF_GRUPPA"=>$grupId)  // Задаем параметры фильтра выборки
    ));
    $data = [];
    while($arData = $rsData->Fetch()){
        $data[] = $arData;
    }
    return $data;
}
?>