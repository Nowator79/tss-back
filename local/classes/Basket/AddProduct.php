<?
namespace Godra\Api\Basket;

use \Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\UserTable;
use \Godra\Api\Helpers\Auth\Authorisation;
use Godra\Api\Helpers\Utility\Misc;
use Godra\Api\User\Get;
use CIBlockElement;

use \Bitrix\Main\Loader;
use \Bitrix\Sale\Basket;
use \Bitrix\Sale\Fuser;

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

Loader::includeModule("highloadblock");

class AddProduct extends Base
{
    /**
     * Отдаётся при /api/map
     * @var array
     */
    protected static $row_data = [
        'element_id' => [
            'mandatory' => true,
            'alias' => 'PRODUCT_ID',
            'description' => 'Ид товара'
        ],
        'quantity' => [
            'mandatory' => false,
            'alias' => 'QUANTITY',
            'default' => 1,
            'description' => 'Кол-во товара , по умолчанию 1'
        ],
        'measure_code' => [
            'mandatory' => false,
            'alias' => 'measure',
            'description' => 'Единица измерения , Символьный код единицы измерения'
        ],
    ];

    /**
     * Апи код информационного блока каталога
     * @var string
     */
    protected static $api_ib_code = IBLOCK_CATALOG_API;

    /**
     * Добавить товар в корзину по id
     */
    public function add_new()
    {
        $params = Misc::getPostDataFromJson();
//        $params = [array (
//            'id' => 19139,
//            'quantity' => 1,
//            'customName'=>'sdfsdf',
//            'options' =>
//                array (
//                    0 => '19135',
//                    1 => '19136',
//                ),
//            'price' => 111111,
//        )];


        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());

        global $USER;
        $quantity = 1;
        $renewal = 'N';

//        $basket->setUserId($USER->GetID());

        foreach ($params as $key=>$item){
            $productId = intval($item['id']);
            $quantity = intval($item['quantity']);
            $prodName = $item['customName'];
            $properties = [];
            $option = [];
            $price = 0;
            $origin_price = 0;

            $price_mas = \CPrice::GetBasePrice(intval($item['id']));
            $price += $price_mas['PRICE'];

            $arPrice = \CCatalogProduct::GetOptimalPrice(
                $productId,
                $quantity,
                $USER->GetUserGroupArray(),
                $renewal
            );
            AddMessage2Log($arPrice);
            $origin_price +=$arPrice['PRICE']['PRICE'];

            // получение скидки
            global $USER;
            $rsUser = \CUser::GetByID($USER->GetID());
            $arUser = $rsUser->Fetch();
            Loader::includeModule("highloadblock");
			$hlSkidkiArray = HL\HighloadBlockTable::getList([
				'filter' => ['=NAME' => "SkidkiConnect"]
			])->fetch();
			$hlblock = HL\HighloadBlockTable::getById($hlSkidkiArray["ID"])->fetch();

            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();

            $rsData = $entity_data_class::getList(array(
                "select" => array("ID", "UF_SKIDKA"),
                "order" => array("ID" => "ASC"),
                //"filter" => array("UF_USER_ID"=>$arUser['XML_ID'], "<UF_DATE_END" => date("d.m.Y H:i:s")),  // Задаем параметры фильтра выборки
                "filter" => array("UF_PRODUCT_ID"=> $productId, "UF_USER_ID"=>$arUser['XML_ID'],">UF_DATE_END" => date("d.m.Y H:i:s")),
            ));
            $cont_discount = false;
            while($arData = $rsData->Fetch()){
                $cont_discount =  $arData['UF_SKIDKA'];
            }

            if($cont_discount) {
                $origin_price = $origin_price - ($origin_price * $cont_discount / 100);
            }

            $arSelect = Array("ID", "NAME", "XML_ID");
            $arFilter = Array("IBLOCK_ID"=>5, "ACTIVE"=>"Y");

            if($item['options']){
                $arFilter['ID']=$item['options'];

                $el = new CIBlockElement;
                $PROP = array();
                global $USER;
                $arLoadProductArray = Array(
                    "MODIFIED_BY"    => $USER->GetID(), // элемент изменен текущим пользователем
                    "IBLOCK_SECTION_ID" => 1223,          // элемент лежит в корне раздела
                    "IBLOCK_ID"      => 5,
                    "PROPERTY_VALUES"=> $PROP,
                    "NAME"           => $prodName,
                    "ACTIVE"         => "Y",            // активен
                    "PREVIEW_TEXT"   => "",
                    "DETAIL_TEXT"    => "",
                );

                if($productId = $el->Add($arLoadProductArray)){
                    $arFields = [
                        "ID" => $productId,
                        "VAT_ID" => 1, //выставляем тип ндс (задается в админке)
                        "VAT_INCLUDED" => "Y", //НДС входит в стоимость
//                        'QUANTITY'=>10,
//                        'QUANTITY_RESERVED'=>1,
//                        'QUANTITY_TRACE'=>'N',
                    ];
                    \Bitrix\Catalog\Model\Product::add($arFields);
                    \CPrice::SetBasePrice($productId,$price,$price_mas['CURRENCY']);

                    $params[$key]['id'] = $productId;
                }
            }else{
                $arFilter['ID']=$item['id'];
            }
            $res = \CIBlockElement::GetList(Array(), $arFilter, false, Array(), Array());
            while($ob = $res->GetNextElement())
            {
                $arFields = $ob->GetFields();
                $arProps = $ob->GetProperties();

                if($item['options']){

//                    $ar_res =\CPrice::GetBasePrice($arFields['ID']);
//                    $price += $ar_res['PRICE'];

                    $arPrice = \CCatalogProduct::GetOptimalPrice(
                        $arFields['ID'],
                        $quantity,
                        $USER->GetUserGroupArray(),
                        $renewal
                    );
                    $option[] = $arFields['XML_ID'].'|'.$arProps['VID_OPTSII']['VALUE'].'|'.$arPrice['PRICE']['PRICE'];

                    $optionPrice = $arPrice['PRICE']['PRICE'];
                    // получение скидки
                    global $USER;
                    $rsUser = \CUser::GetByID($USER->GetID());
                    $arUser = $rsUser->Fetch();
                    Loader::includeModule("highloadblock");
                    $hlbl = HIGHLOAD_SKIDI_CONNECT; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
                    $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

                    $entity = HL\HighloadBlockTable::compileEntity($hlblock);
                    $entity_data_class = $entity->getDataClass();

                    $rsData = $entity_data_class::getList(array(
                        "select" => array("ID", "UF_SKIDKA"),
                        "order" => array("ID" => "ASC"),
                        //"filter" => array("UF_USER_ID"=>$arUser['XML_ID'], "<UF_DATE_END" => date("d.m.Y H:i:s")),  // Задаем параметры фильтра выборки
                        "filter" => array("UF_PRODUCT_ID"=> $arFields['ID'], "UF_USER_ID"=>$arUser['XML_ID'],">UF_DATE_END" => date("d.m.Y H:i:s")),
                    ));
                    $cont_discount = false;
                    while($arData = $rsData->Fetch()){
                        $cont_discount =  $arData['UF_SKIDKA'];
                    }

                    if($cont_discount) {
                        $optionPrice = $optionPrice - ($optionPrice * $cont_discount / 100);
                    }

                    $origin_price +=$optionPrice;
                }else{
                    $prodName = $arFields['NAME'];
                }
            }

            $params[$key]['price'] = $price;

            //XML_ID origin
            $res = \CIBlockElement::GetByID(intval($item['id']));
            if($ar_res = $res->GetNext()){

                if($ar_res['XML_ID']){
                    $item['xmlId'] = $ar_res['XML_ID'];
                }
            }

            if($item['options'])
                $properties['OPTION'] = array(
                    'NAME' => 'OPTION',
                    'CODE' => 'OPTION',
                    'VALUE' => implode(";", $option),
                    'SORT' => 100
                );
            if($item['comment'])
                $properties['COMMENT']= array(
                    'NAME' => 'COMMENT',
                    'CODE' => 'COMMENT',
                    'VALUE' => $item['comment'],
                    'SORT' => 100
                );
            if($item['id_original'])
                $properties['ID_ORIGINAL']= array(
                    'NAME' => 'ID_ORIGINAL',
                    'CODE' => 'ID_ORIGINAL',
                    'VALUE' => intval($item['id']),
                    'SORT' => 100
                );
            if($item['customName'])
                $properties['CUSTOM_NAME']= array(
                    'NAME' => 'CUSTOM_NAME',
                    'CODE' => 'CUSTOM_NAME',
                    'VALUE' => $item['customName'],
                    'SORT' => 100
                );
            if($item['additionalSlotIds'])
                $properties['ADDITIONAL_SLOT_IDS']= array(
                    'NAME' => 'ADDITIONAL_SLOT_IDS',
                    'CODE' => 'ADDITIONAL_SLOT_IDS',
                    'VALUE' => json_encode($item['additionalSlotIds']),
                    'SORT' => 100
                );
            if($item['catalogSlotIds'])
                $properties['CATALOG_SLOT_IDS']= array(
                    'NAME' => 'CATALOG_SLOT_IDS',
                    'CODE' => 'CATALOG_SLOT_IDS',
                    'VALUE' => json_encode($item['catalogSlotIds']),
                    'SORT' => 100
                );
            if($item['mainSlotIds'])
                $properties['MAIN_SLOT_IDS']= array(
                    'NAME' => 'MAIN_SLOT_IDS',
                    'CODE' => 'MAIN_SLOT_IDS',
                    'VALUE' => json_encode($item['mainSlotIds']),
                    'SORT' => 100
                );
            if($item['comment'])
                $properties['COMMENT']= array(
                    'NAME' => 'COMMENT',
                    'CODE' => 'COMMENT',
                    'VALUE' => $item['comment'],
                    'SORT' => 100
                );
            if($item['xmlId'])
                $properties['PRODUCT_XML_ID']= array(
                    'NAME' => 'PRODUCT_XML_ID',
                    'CODE' => 'PRODUCT_XML_ID',
                    'VALUE' => $item['xmlId'],
                    'SORT' => 100
                );
            if($item['props'])
                $properties['PROPS']= array(
                    'NAME' => 'PROPS',
                    'CODE' => 'PROPS',
                    'VALUE' => json_encode($item['props']),
                    'SORT' => 100
                );

            if($item['xmlId']){
                $xmlId = $item['xmlId'];
            }else{
                $xmlId = 'bx_'.rand(1000000000000,9999999999999);
            }

            $item = $basket->createItem('catalog', $productId);

            $item->setFields(array(
                'NAME'=> $prodName,
                'QUANTITY' => $quantity,
                'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
                'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProviderCustom',
                'PRICE' => $origin_price,
                'CUSTOM_PRICE' => 'Y',
                'XML_ID'=>$xmlId,
            ));
            if(isset($properties)) {
                $basketPropertyCollection = $item->getPropertyCollection();
                $basketPropertyCollection->setProperty($properties);
            }

            $params[$key]['basket_id'] = $xmlId;

        }
        $basket->save();
        
        $dbRes = \Bitrix\Sale\Basket::getList([
            'select' => ['ID','PRODUCT_ID','PRICE','QUANTITY','XML_ID'],
            'filter' => [
                '=FUSER_ID' => \Bitrix\Sale\Fuser::getId(),
                '=ORDER_ID' => null,
                '=LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
                '=CAN_BUY' => 'Y',

            ]
        ]);

        while ($item = $dbRes->fetch())
        {
            foreach ($params as $key=>$value){
                if($value['basket_id']==$item['XML_ID'])$params[$key]['basket_id'] = $item['ID'];
            }
        }
        return $params;
    }
    public function byId()
    {
        /*
        $headers = apache_request_headers();

        // определить id пользователя по токену
//        $decoded = Authorisation::getUserId($headers);
//        if (!isset($decoded['error'])) {
//            $tokenUserId = $decoded;
//        }
//
//        // определение id типа цена
//        $priceType = $tokenUserId ? self::getPriceType($tokenUserId) : false;

        // определить id пользователя по токену
        $decoded = Authorisation::getUserId($headers);
        if (!isset($decoded['error'])) {
            $tokenUserId = $decoded;
        } else {
            return ['error' => $decoded['error']];
        }

        // является ли суперпользователем
        if (Authorisation::isSuperUser($headers)) {
            $superUserId = $tokenUserId;
        } else {
            // искать суперпользователя для текущего пользователя
            $superUserXmlId = Get::getParentUserXmlId($tokenUserId);
            $superUserId = Get::getUserIdByXmlId($superUserXmlId);
        }

        // Идентификатор текущего договора
        $dealId = UserTable::getList([
            'filter' => [ 'ID' => $superUserId, 'ACTIVE' => 'Y' ],
            'select' => [ 'ID', 'UF_ID_DOGOVOR' ]
        ])->Fetch()['UF_ID_DOGOVOR'];

        $deal =  \Godra\Api\Catalog\Element::getDeal($dealId);
        $priceTypeXmlId = $deal['UF_IDTIPACEN'];
        */

        $priceTypeXmlId = (new \Godra\Api\Helpers\Contract)->getPriceTypeByUserId(\Bitrix\Main\Engine\CurrentUser::get()->getId());

        // передавать id пользователя
        $this->addProductById(
            $this->post_data['element_id'],
            $this->post_data['measure_code'],
            $this->post_data['quantity'],
            $priceTypeXmlId
        );
    }
}
?>