<?
namespace Godra\Api\Basket;

use \Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\UserTable;
use \Godra\Api\Helpers\Auth\Authorisation;
use Godra\Api\Helpers\Utility\Misc;
use Godra\Api\User\Get;

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
//            'id' => 16217,
//            'quintity' => 1,
//            'code' => 'dizelnyy_generator_tss_ad_1400s_t400_1rm9',
//            'options' =>
//                array (
//                    0 => '16440',
//                    1 => '16441',
//                ),
//            'price' => 111111,
//        )];
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());
        foreach ($params as $key=>$item){
            $productId = intval($item['id']);
            $quantity = intval($item['quantity']);
            $properties = [];
            $option = [];
            $price = 0;
            $price += \CPrice::GetBasePrice(intval($item['id']));

            $arSelect = Array("ID", "NAME", "XML_ID");
            $arFilter = Array("IBLOCK_ID"=>5, "ACTIVE"=>"Y");
            if($item['options']){
                $arFilter['ID']=$item['options'];



            }else{
                $arFilter['ID']=$item['id'];
            }
            $res = \CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelect);
            while($ob = $res->GetNextElement())
            {
                $arFields = $ob->GetFields();

                if($item['options'])$option[] = $arFields['XML_ID'];
                $ar_res =\CPrice::GetBasePrice($arFields['ID']);
                $price += $ar_res['PRICE'];
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
                'QUANTITY' => $quantity,
                'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                'LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
                'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProviderCustom',
                'PRICE' => $price,
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