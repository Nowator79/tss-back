<?

namespace Godra\Api\Basket;

use Godra\Api\Helpers\Utility\Misc;
use Godra\Api\SetsBuilder\Builder;

/**
 * Класс для обращений к публичным функциям абстрактного класса корзины Godra\Api\Basket\Base
 */
class Helper extends Base
{
    const BORDER_THIN = 'thin';
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
        $useId = \Bitrix\Sale\Fuser::getId(true);
        //данные по персональному мененджеру
        global $USER;
        //$userData = \CUser::GetByID($USER->GetID())->Fetch();
        $userData = \CUser::GetByID(1)->Fetch();
        //
        $params = Misc::getPostDataFromJson();
        if(!empty($params['orderId'])) {
            $order = \Bitrix\Sale\Order::load($params['orderId']);
            $basket = $order->getBasket();
        } else {
            $basket = \Bitrix\Sale\Basket::loadItemsForFUser($useId, \Bitrix\Main\Context::getCurrent()->getSite());
        }

        //for test
//        $params = [
//            'contragent' => 'Название контрагента',
//            'company' => 'ООО "Вектор"',
//            'name' => 'Иванов И.И.',
//            'phone' => '+7999 9999 99 99',
//            'email' => 'mail@mail.ru'
//        ];
        //

        if (count($basket->getQuantityList())) {
            $fileExt = 'xls';
            $fileName = "invoice_{$basket->getFUserId()}.{$fileExt}";
            $tempDir = $_SESSION['REPORT_EXPORT_TEMP_DIR'] = \CTempFile::GetDirectoryName(1, array('invoice', uniqid('basket_invoice_')));
            \CheckDirPath($tempDir);
            $filePath = "{$tempDir}{$fileName}";
            require_once $_SERVER["DOCUMENT_ROOT"] . '/local/classes/Helpers/PHPExcel/Classes/PHPExcel.php';
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
            $imgBarcode = imagecreatefromjpeg(\Bitrix\Main\Application::getDocumentRoot() . '/local/tmp/logo.754be02.jpg');
            $objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
            $objDrawing->setDescription('barcode');
            $objDrawing->setImageResource($imgBarcode);
            $objDrawing->setHeight(80);
            $objDrawing->setCoordinates('A3');

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('E3', $params["company"]);
            $objPHPExcel->getActiveSheet()->mergeCells('E3:G3');

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('E4', $params["name"]);
            $objPHPExcel->getActiveSheet()->mergeCells('E4:G4');

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('E5', $params["phone"]);
            $objPHPExcel->getActiveSheet()->mergeCells('E5:G5');

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('E6', $params["email"]);
            $objPHPExcel->getActiveSheet()->mergeCells('E6:G6');

            $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A8', 'На ваш запрос предлагаем вам следующее решение под вашу индивидуальную потребность:');
            $objPHPExcel->getActiveSheet()->mergeCells('A8:G8');

            //хедер таблицы
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A11', 'N');

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('B11', 'Наименование');
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('C11', 'Кол-во');
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('D11', 'Ед. изм.');
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('E11', 'Цена руб.');
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('F11', 'Сумма');

            //табличная часть корзины
            $startRowId = 12;
            foreach ($basket as $item) {
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . $startRowId, $item->getProductId());
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B' . $startRowId, $item->getField("NAME"));
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('C' . $startRowId, $item->getQuantity());
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('D' . $startRowId, 'шт');
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('E' . $startRowId, $item->getPrice());
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('F' . $startRowId, $item->getFinalPrice());
                $startRowId++;
            }
            //

            $styleArray = array(
                'borders' => array(
                    'allborders' => array(
                        'style' => \PHPExcel_Style_Border::BORDER_THIN
                    )
                )
            );

            $objPHPExcel->getActiveSheet()->getStyle('A11:F'.$startRowId)->applyFromArray($styleArray);
            unset($styleArray);
            $startRowId = $startRowId + 5;

            $startRowId++;
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $startRowId, 'Детализация комплектации указана в Приложении №1 к данному технико-коммерческому предложению (ТКП)');
            $objPHPExcel->getActiveSheet()->mergeCells('A'.$startRowId.':G'.$startRowId);

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $startRowId++, 'Ваш персональный менеджер:');

            $startRowId++;
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $startRowId, 'Ф.И.О');
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('B' . $startRowId, $userData["NAME"].' '.$userData["LAST_NAME"]);
            if(!empty($userData["PERSONAL_PHONE"])) {
                $startRowId++;
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . $startRowId, 'Телефон');
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B' . $startRowId, $userData["PERSONAL_PHONE"]);
            }
            $startRowId++;
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $startRowId, 'Email');
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('B' . $startRowId, $userData["EMAIL"]);

            //карточки товаров
            $startRowId = $startRowId + 5;
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $startRowId, 'Приложение1');
            $objPHPExcel->getActiveSheet()->mergeCells('A'.$startRowId.':G'.$startRowId);

            $startRowId = $startRowId + 4;
            foreach ($basket as $item) {
                $arProduct = Builder::getProduct('',$item->getField("PRODUCT_XML_ID"))[0];
                $startRowId++;
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . $startRowId, $arProduct['NAME']);
                if(!empty($arProduct["DETAIL_PICTURE"])){
                    $startRowId++;
                    $objPHPExcel->getActiveSheet()->mergeCells('A'.$startRowId.':G'.$startRowId);
                    $imgBarcode = imagecreatefromjpeg(\Bitrix\Main\Application::getDocumentRoot() . $arProduct["DETAIL_PICTURE"]);
                    $objDrawing = new \PHPExcel_Worksheet_MemoryDrawing();
                    $objDrawing->setDescription('barcode');
                    $objDrawing->setImageResource($imgBarcode);
                    $objDrawing->setHeight(100);
                    $objDrawing->setCoordinates('A'.$startRowId);
                }

            }

            if(!empty($arProduct["DETAIL_TEXT"])){
                $startRowId++;
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . $startRowId, strip_tags($arProduct['DETAIL_TEXT']));
            }

            foreach ($arProduct['TABS']['props'] as $prop) {
                $startRowId = $startRowId + 3;
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . $startRowId, $prop['NAME']);
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B' . $startRowId, $prop['VALUE']);
            }

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="01simple.xls"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');

            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save($filePath);
            return str_replace(\Bitrix\Main\Application::getDocumentRoot(), '', $filePath);
        }

        return ['error' => 'Корзина пуста!'];
    }
}
?>