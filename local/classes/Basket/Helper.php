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
            $db_res = \CPrice::GetList(
                array(),
                array(
                    "PRODUCT_ID" =>  $item['PRODUCT_ID'],
                    "CATALOG_GROUP_ID" => 496
                )
            );
            if ($ar_res = $db_res->Fetch())
            {
                $item_el['origin_price']+=$ar_res["PRICE"];
            }

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
        $compl_section_id = 1060;
        $mas_el_id = [];
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
            $mas_el_id[] = $item['PRODUCT_ID'];
        }
        if($mas_el_id){
            $filter =[
                'ID'=>$mas_el_id,
                'IBLOCK_ID'=>5,
                'SECTION_ID'=>$compl_section_id
            ];
            $res = \CIBlockElement::GetList(Array(),$filter, false, Array(), Array('*'));
            while($ob = $res->GetNextElement()){
                $arFields = $ob->GetFields();
                \CIBlockElement::Delete($arFields['ID']);
            }
        }

        \CSaleBasket::DeleteAll(\Bitrix\Sale\Fuser::getId());
    }
}
?>