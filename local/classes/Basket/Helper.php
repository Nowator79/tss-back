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
        $useId = \Bitrix\Sale\Fuser::getId();
        $params = Misc::getPostDataFromJson();
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser($useId, \Bitrix\Main\Context::getCurrent()->getSite());

        if (count($basket->getQuantityList())) {
            $fileExt = 'xls';
            $fileName = "invoice_{$basket->getFUserId()}.{$fileExt}";
            $tempDir = $_SESSION['REPORT_EXPORT_TEMP_DIR'] = \CTempFile::GetDirectoryName(1, array('invoice', uniqid('basket_invoice_')));
            \CheckDirPath($tempDir);
            $filePath = "{$tempDir}{$fileName}";
            require_once $_SERVER["DOCUMENT_ROOT"] .'/local/classes/Helpers/PHPExcel/Classes/PHPExcel.php';
            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->getProperties()->setCreator("TSS")
                ->setLastModifiedBy("TSS")
                ->setTitle("invoice_{$basket->getFUserId()}")
                ->setSubject("Office 2007 XLSX Test Document")
                ->setDescription("invoice_{$basket->getFUserId()}")
                ->setKeywords("office 2007 openxml php")
                ->setCategory("invoice");

            //рендеринг
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A1', 'Коммерческое предложение от ' . $params["contragent"]);
                $objPHPExcel->getActiveSheet()->mergeCells('A1:G1');
                $imgBarcode = imagecreatefromjpeg(\Bitrix\Main\Application::getDocumentRoot().'/local/tmp/logo.754be02.jpg');
                $objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
                $objDrawing->setDescription('barcode');
                $objDrawing->setImageResource($imgBarcode);
                $objDrawing->setHeight(150);
                $objDrawing->setCoordinates('A2');
                $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
            //

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="01simple.xls"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');

            header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
            header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header ('Pragma: public'); // HTTP/1.0

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save($filePath);
            return str_replace(\Bitrix\Main\Application::getDocumentRoot(), '', $filePath);
        }

        return ['error' => 'Корзина пуста!'];
    }
}

?>