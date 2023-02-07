<?
namespace Godra\Api\Basket;

use Godra\Api\Helpers\Utility\Misc;

/**
 * Класс для обращений к публичным функциям абстрактного класса корзины Godra\Api\Basket\Base
 */
class Helper extends Base
{
    public function getBasketItems_new()
    {
        $mas_item = [];
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());

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
            $item_el =  [
                'id' => $item['PRODUCT_ID'],
                'quantity' => $item['QUANTITY'],
                'price' => $item['PRICE'],
                'basket_id' => $item['ID'],
            ];

            $item_el['origin_price'] = 0;
            $basketPropRes = \Bitrix\Sale\Internals\BasketPropertyTable::getList(array(
                'filter' => array(
                    "BASKET_ID" => $item['ID'],
                ),
            ));

            while ($property = $basketPropRes->fetch()) {
                if($property['NAME']=='OPTION'&&$property['VALUE']){
                    $item_el['options']=[];
                    $arSelect = Array("ID", "NAME", "XML_ID");
                    $arFilter = Array("IBLOCK_ID"=>5, 'XML_ID'=>explode(';', $property['VALUE']), "ACTIVE"=>"Y");
                    $res = \CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelect);
                    while($ob = $res->GetNextElement())
                    {
                        $arFields = $ob->GetFields();
                        $item_el['options'][] = $arFields['ID'];

                        $db_res = \CPrice::GetList(
                            array(),
                            array(
                                "PRODUCT_ID" =>  $arFields['ID'],
                                "CATALOG_GROUP_ID" => 496
                            )
                        );
                        if ($ar_res = $db_res->Fetch())
                        {
                            $item_el['origin_price']+=$ar_res["PRICE"];
                        }
                    }

                }
                if($property['NAME']=='COMMENT') {
                    $item_el['comment'] = $property['VALUE'];
                }
                if($property['NAME']=='PROPS') {
                    $item_el['comment'] = json_decode($property['VALUE']);
                }
            }
            if(!isset($item_el['options'])){
                $db_res = \CPrice::GetList(
                    array(),
                    array(
                        "PRODUCT_ID" =>  $item_el['id'],
                        "CATALOG_GROUP_ID" => 496
                    )
                );
                if ($ar_res = $db_res->Fetch())
                {
                    $item_el['origin_price']=$ar_res["PRICE"];
                }
            }
            $mas_item[] = $item_el;
        }

        return $mas_item;
    }
    public function deleteAll()
    {
        \CSaleBasket::DeleteAll(\Bitrix\Sale\Fuser::getId());
    }
}
?>