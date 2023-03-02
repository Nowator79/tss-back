<?
namespace Godra\Api\Basket;

use \Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\UserTable;
use \Godra\Api\Helpers\Auth\Authorisation;
use Godra\Api\Helpers\Utility\Misc;
use Godra\Api\User\Get;
use CIBlockElement;

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
            $origin_price +=$arPrice['PRICE']['PRICE'];

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
                    $option[] = $arFields['XML_ID'].'|'.$arProps['VID_OPTSII']['VALUE'];
//                    $ar_res =\CPrice::GetBasePrice($arFields['ID']);
//                    $price += $ar_res['PRICE'];

                    $arPrice = \CCatalogProduct::GetOptimalPrice(
                        $arFields['ID'],
                        $quantity,
                        $USER->GetUserGroupArray(),
                        $renewal
                    );
                    $origin_price +=$arPrice['PRICE']['PRICE'];
                }
            }

            $params[$key]['price'] = $price;

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
            if($item['props'])
                $properties['PROPS']= array(
                    'NAME' => 'PROPS',
                    'CODE' => 'PROPS',
                    'VALUE' => json_encode($item['props']),
                    'SORT' => 100
                );


            $xml_id = 'bx_'.rand(1000000000000,9999999999999);
            $item = $basket->createItem('catalog', $productId);

            $item->setFields(array(
                'NAME'=> $prodName,
                'QUANTITY' => $quantity,
                'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
                'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProviderCustom',
                'PRICE' => $origin_price,
                'CUSTOM_PRICE' => 'Y',
                'XML_ID'=>$xml_id
            ));
            if(isset($properties)) {
                $basketPropertyCollection = $item->getPropertyCollection();
                $basketPropertyCollection->setProperty($properties);
            }

            $params[$key]['basket_id'] = $xml_id;

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