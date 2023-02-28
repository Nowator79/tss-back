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
            'select' => ['ID', 'PRODUCT_ID', 'PRICE', 'QUANTITY', 'XML_ID'],
            'filter' => [
                '=FUSER_ID' => \Bitrix\Sale\Fuser::getId(),
                '=ORDER_ID' => null,
                '=LID' => \Bitrix\Main\Context::getCurrent()->getSite(),
                '=CAN_BUY' => 'Y',

            ]
        ]);

        while ($item = $dbRes->fetch()) {
            $item_el = [
                'id' => $item['PRODUCT_ID'],
                'quantity' => $item['QUANTITY'],
                'origin_price' => $item['PRICE'],
                'basket_id' => $item['ID'],
            ];

            $item_el['price'] = 0;
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
                $item_el['price']+=$ar_res["PRICE"];
            }
//            global $USER;
//            $quantity = 1;
//            $renewal = 'N';
//            $arPrice = \CCatalogProduct::GetOptimalPrice(
//                $item['PRODUCT_ID'],
//                $quantity,
//                $USER->GetUserGroupArray(),
//                $renewal
//            );
//            $item_el['origin_price'] +=$arPrice['PRICE']['PRICE'];

            while ($property = $basketPropRes->fetch()) {
                $property_buf = $property;
                if ($property['NAME'] == 'OPTION' && $property['VALUE']) {
                    $item_el['options'] = [];
                    $arSelect = array("ID", "NAME", "XML_ID");
                    $buf_option_id = explode(';', $property['VALUE']);
                    foreach ($buf_option_id as $key=>$value){
                        $buf_val = explode('|', $value);
                        $buf_option_id[$key]=$buf_val[0];
                    }
                    $arFilter = array("IBLOCK_ID" => 5, 'XML_ID' => $buf_option_id, "ACTIVE" => "Y");
                    $res = \CIBlockElement::GetList(array(), $arFilter, false, array(), $arSelect);
                    while ($ob = $res->GetNextElement()) {
                        $arFields = $ob->GetFields();
                        $item_el['options'][] = $arFields['ID'];

                        $db_res = \CPrice::GetList(
                            array(),
                            array(
                                "PRODUCT_ID" => $arFields['ID'],
                                "CATALOG_GROUP_ID" => 496
                            )
                        );
                        if ($ar_res = $db_res->Fetch()) {
                            $item_el['price'] += $ar_res["PRICE"];
                        }
//                        $arPrice = \CCatalogProduct::GetOptimalPrice(
//                            $arFields['ID'],
//                            $quantity,
//                            $USER->GetUserGroupArray(),
//                            $renewal
//                        );
//                        $item_el['origin_price'] +=$arPrice['PRICE']['PRICE'];
                    }

                }
                if ($property['NAME'] == 'COMMENT') {
                    $item_el['comment'] = $property['VALUE'];
                }
                if ($property['NAME'] == 'PROPS') {
                    $item_el['comment'] = json_decode($property['VALUE']);
                }
            }

//            if (!isset($item_el['options'])) {
//                $db_res = \CPrice::GetList(
//                    array(),
//                    array(
//                        "PRODUCT_ID" => $item_el['id'],
//                        "CATALOG_GROUP_ID" => 496
//                    )
//                );
//                if ($ar_res = $db_res->Fetch()) {
//                    $item_el['origin_price'] = $ar_res["PRICE"];
//                }
//            }
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

    /**
     * Получить коммерческое предложение из корзины
     *
     * @return string|void
     */
    public function getInvoice()
    {
        global $USER;
        $useId = ($USER->GetID() == 0) ? 1 : $USER->GetID();
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser($useId, \Bitrix\Main\Context::getCurrent()->getSite());

        if (count($basket->getQuantityList())) {
            $fileExt = 'xls';
            $fileName = "invoice_{$basket->getFUserId()}.{$fileExt}";
            $tempDir = $_SESSION['REPORT_EXPORT_TEMP_DIR'] = \CTempFile::GetDirectoryName(1, array('invoice', uniqid('basket_invoice_')));
            \CheckDirPath($tempDir);
            $filePath = "{$tempDir}{$fileName}";

            $fileType = 'application/vnd.ms-excel';
            $fileHeader = '<?
                    Header("Content-Type: application/force-download");
                    Header("Content-Type: application/octet-stream");
                    Header("Content-Type: application/download");
                    Header("Content-Disposition: attachment;filename={$fileName}");
                    Header("Content-Transfer-Encoding: binary");
                    ?>
                    <html>
                    <head>
                        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    </head>
                    <body>

                    <table border="1">
                        <tr>
                            <td>N</td>
                            <td>Наименование товара</td>
                            <td>Кол-во</td>
                            <td>Ед.</td>
                            <td>Цена руб.</td>
                            <td>Сумма руб.</td>
                        </tr>';
            file_put_contents($filePath, $fileHeader, FILE_APPEND);

            // рендерим таблицу
            foreach ($basket as $item) {
                $row = '<tr><td>' . $item->getProductId() . '</td>
                                    <td>' . $item->getField("NAME") . '</td>
                                    <td>' . $item->getQuantity() . '</td>
                                    <td>шт</td>
                                    <td>' . $item->getPrice() . '</td>
                                    <td>' . $item->getFinalPrice() . '</td>
                                </tr>';
                file_put_contents($filePath, $row, FILE_APPEND);
            }
            //
            file_put_contents($filePath, '</table></body></html>', FILE_APPEND);

            return str_replace(\Bitrix\Main\Application::getDocumentRoot(
            ), '', $filePath);
        }

        return ['error' => 'Корзина пуста!'];
    }
}

?>