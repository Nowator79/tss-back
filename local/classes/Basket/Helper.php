<?

namespace Godra\Api\Basket;

use Godra\Api\Helpers\Utility\Misc;
use Godra\Api\SetsBuilder\Builder;
use Godra\Api\Catalog\Element;

use \Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

Loader::includeModule("highloadblock");


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
            'select' => ['ID', 'PRODUCT_ID', 'PRICE', 'QUANTITY', 'XML_ID', 'NOTES'],
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
                'props' => $item['NOTES'],
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
                    "PRODUCT_ID" => $item['PRODUCT_ID'],
                    "CATALOG_GROUP_ID" => 496
                )
            );
            if ($ar_res = $db_res->Fetch()) {
                $item_el['price'] += $ar_res["PRICE"];
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
                if ($property['NAME'] == 'ID_ORIGINAL' && $property['VALUE']) {
                    $item_el['id'] = $property['VALUE'];
                }
                if ($property['NAME'] == 'PRODUCT_XML_ID' && $property['VALUE']) {
                    $item_el['xmlId'] = $property['VALUE'];
                }

                if ($property['NAME'] == 'OPTION' && $property['VALUE']) {
                    $item_el['options'] = [];
                    $arSelect = array("ID", "NAME", "XML_ID");
                    $buf_option_id = explode(';', $property['VALUE']);
                    foreach ($buf_option_id as $key => $value) {
                        $buf_val = explode('|', $value);
                        $buf_option_id[$key] = $buf_val[0];
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

//


                }

                if ($property['NAME'] == 'PROPS') {
                    $item_el['props'] = $property['VALUE'];
                }
                if ($property['NAME'] == 'CUSTOM_NAME') {
                    $item_el['customName'] = $property['VALUE'];
                }
                if ($property['NAME'] == 'ADDITIONAL_SLOT_IDS') {
                    $item_el['additionalSlotIds'] = json_decode($property['VALUE']);
                }
                if ($property['NAME'] == 'CATALOG_SLOT_IDS') {
                    $item_el['catalogSlotIds'] = json_decode($property['VALUE']);
                }
                if ($property['NAME'] == 'MAIN_SLOT_IDS') {
                    $item_el['mainSlotIds'] = json_decode($property['VALUE']);
                }
                if ($property['NAME'] == 'COMMENT') {
                    $item_el['comment'] = $property['VALUE'];
                }
                if ($property['NAME'] == 'PROPS') {
                    $item_el['props'] = json_decode($property['VALUE']);
                }
            }

//            if (empty($item_el['origin_price']) || $item_el['origin_price'] == 0) {
//                $item_el['origin_price'] = $item_el['price'];
//            }
			global $USER;
			$filterUserReq = [ "ID" => $USER->GetID() ];
			$rsUsers = \CUser::GetList(
				($by="personal_country"), 
				($order="desc"), 
				$filterUserReq
			);
			$userxmlid = "";
			if($arUser = $rsUsers->Fetch()){
				$userxmlid = $arUser["XML_ID"];
			};
			$discount = 0;

			if($userxmlid){
				$hlblock = HL\HighloadBlockTable::getById(HIGHLOAD_SKIDI_CONNECT)->fetch();
				$entity = HL\HighloadBlockTable::compileEntity($hlblock);
				$entity_data_class = $entity->getDataClass();
				
				$rsData = $entity_data_class::getList(array(
					"select" => array("*"),
					"order" => array("ID" => "ASC"),
					"filter" => array("UF_PRODUCT_ID"=>$item['PRODUCT_ID'], "UF_USER_ID" => $userxmlid)
				));
				while($arData = $rsData->Fetch()){
					$discount = $arData["UF_SKIDKA"];
				}
			}

            $item_el['origin_price'] = $item_el['price'];
			if($discount > 0){
				$item_el['price'] = $item_el['price'] - $item_el['price'] / 100 * $discount;
			}
            $item_el['discount'] = $discount;


            $mas_item[] = $item_el;
        }
        return $mas_item;
    }

    public function deleteAll()
    {
        $compl_section_id = 1223;
        $mas_el_id = [];
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
            $mas_el_id[] = $item['PRODUCT_ID'];
        }
        if ($mas_el_id) {
            $filter = [
                'ID' => $mas_el_id,
                'IBLOCK_ID' => 5,
                'SECTION_ID' => $compl_section_id
            ];
            $res = \CIBlockElement::GetList(array(), $filter, false, array(), array('*'));
            while ($ob = $res->GetNextElement()) {
                $arFields = $ob->GetFields();
                \CIBlockElement::Delete($arFields['ID']);
            }
        }

        \CSaleBasket::DeleteAll(\Bitrix\Sale\Fuser::getId());
    }

    public function deleteFromHL($ids){
        foreach ($ids as $id){
            $hlbl = 68; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
            $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();
            global $USER;
            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();

            $rsData = $entity_data_class::getList(array(
                "select" => array("ID"),
                "order" => array("ID" => "ASC"),
                "filter" => array("UF_ITEM_ID"=>$id, "UF_USER_ID" => $USER->GetID())  // Задаем параметры фильтра выборки
            ));
            $rows = [];
            while($arData = $rsData->Fetch()){
                $rows[] = $arData['ID'];
            }

            if (!empty($rows)){
                foreach ($rows as $row){
                    $entity_data_class::Delete($row);
                }
            }

        }
    }

    /**
     * Получить коммерческое предложение из корзины
     *
     * @return string|void
     */
    public function getInvoice()
    {
        $params = Misc::getPostDataFromJson();
        if (!$params["userId"]) {
            return ['error' => 'Пользователь не найден!'];
        }

        //данные по персональному мененджеру
            $arFUser = \CSaleUser::GetList(['USER_ID' => $params['userId']]);
            $userData = \CUser::GetByID($params["userId"])->Fetch();
        //


        if (!empty($params['orderId'])) {
            $order = \Bitrix\Sale\Order::load($params['orderId']);
            $basket = $order->getBasket();
        } else {
            $basket = \Bitrix\Sale\Basket::loadItemsForFUser($arFUser['ID'], \Bitrix\Main\Context::getCurrent()->getSite());
        }

        if (count($basket->getQuantityList())) {

            //подготовка данных товаров в корзине
                $arBasketItems = [];
                foreach ($basket as $item) {
                    $itemData = [];

                    $arProduct = Builder::getProduct('', $item->getField("XML_ID"))[0];
                    $itemId = $item->getProductId();

                    $itemData = [
                        "ID" => $itemId,
                        "NAME" => $item->getField("NAME") ?? $arProduct["NAME"],
                        "DETAIL_TEXT" => $arProduct["DETAIL_TEXT"],
                        "DETAIL_PICTURE" => $arProduct["DETAIL_PICTURE"],
                        "QUANTITY" => $item->getQuantity(),
                        "MEASURE_NAME" => $item->getField("MEASURE_NAME"),
                        "OPTION" => [],
                        "PRICE" => $item->getPrice(),
                        "FPRICE" => $item->getFinalPrice(),
                        "PROPS" => $arProduct['TABS']['props']
                    ];
                    $basketPropRes = \Bitrix\Sale\Internals\BasketPropertyTable::getList(array(
                        'filter' => array(
                            "BASKET_ID" => $item->getId(),
                        ),
                    ));
                    while ($property = $basketPropRes->fetch()) {
                        if ($property['NAME'] == 'OPTION' && $property['VALUE']) {
                            $arSelect = array("ID", "NAME","DETAIL_TEXT","DETAIL_PICTURE", "XML_ID");
                            $buf_option_id = explode(';', $property['VALUE']);
                            foreach ($buf_option_id as $key => $value) {
                                $buf_val = explode('|', $value);
                                $buf_option_id[$key] = $buf_val[0];
                            }
                            $arFilter = array("IBLOCK_ID" => 5, 'XML_ID' => $buf_option_id, "ACTIVE" => "Y");
                            $res = \CIBlockElement::GetList(array(), $arFilter, false, array(), $arSelect);
                            while ($ob = $res->GetNextElement()) {
                                $arFields = $ob->GetFields();
                                $itemData['OPTION'][] = [
                                    "ID" => $arFields['ID'],
                                    "NAME" => $arFields['NAME'],
                                    "DETAIL_TEXT" => $arFields["DETAIL_TEXT"],
                                    "DETAIL_PICTURE" => $arFields["DETAIL_PICTURE"],
                                ];
                            }

                        }
                    }
                    $arBasketItems[] = $itemData;
                }
            //

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

            if($userData["PERSONAL_PHOTO"]){
                $path = $_SERVER["DOCUMENT_ROOT"] . \CFile::GetPath($userData["PERSONAL_PHOTO"]);
            }else{
                $path = $_SERVER["DOCUMENT_ROOT"] . '/upload/avatar/no_logo.png';
            }
            $info = getimagesize($path);

            $extension = image_type_to_extension($info[2]);
            if ($extension == '.jpeg') {
                $imgBarcode = imagecreatefromjpeg($path);
            } elseif ($extension == '.png') {
                $imgBarcode = imagecreatefrompng($path);
            } else {
                return ['error' => 'Автар имеет не верный формат! Допустим PNG или JPEG.'];
            }

            $objDrawing = new \PHPExcel_Worksheet_Drawing();
            $objDrawing->setPath($path);
            $objDrawing->setDescription('barcode');
            $objDrawing->setWidth(50);
            $objDrawing->setResizeProportional(true);
            $objDrawing->setCoordinates('A3');
//            $objPHPExcel->getActiveSheet()->getColumnDimension('A3')->setAutoSize(true);
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
            foreach ($arBasketItems as $item) {
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . $startRowId, $item["ID"]);
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('B' . $startRowId, $item["NAME"]);
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('C' . $startRowId, $item["QUANTITY"]);
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('D' . $startRowId, $item["MEASURE_NAME"]);
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('E' . $startRowId, $item["PRICE"]);
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('F' . $startRowId, $item["FPRICE"]);
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

            $objPHPExcel->getActiveSheet()->getStyle('A11:F' . $startRowId)->applyFromArray($styleArray);
            unset($styleArray);
            $startRowId = $startRowId + 5;

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $startRowId, 'Детализация комплектации указана в Приложении №1 к данному технико-коммерческому предложению (ТКП)');
            $objPHPExcel->getActiveSheet()->mergeCells('A' . $startRowId . ':G' . $startRowId);

            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $startRowId++, 'Ваш персональный менеджер:');

            $startRowId++;
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $startRowId, 'Ф.И.О');
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('B' . $startRowId, $userData["NAME"] . ' ' . $userData["LAST_NAME"]);

            if (!empty($userData["PERSONAL_PHONE"])) {
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
            $objPHPExcel->getActiveSheet()->mergeCells('A' . $startRowId . ':G' . $startRowId);

            $startRowId = $startRowId + 2;
               foreach ($arBasketItems as $item_key=>$item) {
                $startRowId = $startRowId + 4;
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A' . $startRowId, ''.($item_key+1).'. '.$item['NAME']);

                if (!empty($item["DETAIL_PICTURE"])) {
                    $startRowId++;
                    $pathImg = \Bitrix\Main\Application::getDocumentRoot() . $item["DETAIL_PICTURE"];

                    $infoImg = getimagesize($pathImg);
                    $extensionImg = image_type_to_extension($infoImg[2]);

                    if ($extensionImg == '.jpeg') {
                        $imgBarcodeImg = imagecreatefromjpeg($pathImg);
                    } elseif ($extensionImg == '.png') {
                        $imgBarcodeImg = imagecreatefrompng($pathImg);
                    } else {
                        return [
                            'extension' => $extensionImg,
                            'error' => 'DETAIL_PICTURE имеет не верный формат (ID товара ' . $item['ID'] . ' ! Допустим PNG или JPEG.'
                        ];
                    }
                    $mas_path = pathinfo($pathImg);
                    $origImgPath = $pathImg;
                    $tempFile = $mas_path['dirname'].'/'.$mas_path['filename'].'_small.'.$mas_path['extension'];
                    \CFile::ResizeImageFile(
                        $origImgPath,
                        $tempFile,
                        array('width'=>200,'height'=>150),
                        BX_RESIZE_IMAGE_PROPORTIONAL,
                        array(),
                        false,
                        false
                    );

                    $objDrawing = new \PHPExcel_Worksheet_Drawing();
                    $objDrawing->setPath($tempFile);
                    $objDrawing->setName('img ' . $item['ID']);
                    $objDrawing->setDescription('barcode' . $item['ID']);
                    $objDrawing->setCoordinates('A' . $startRowId);
                    $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
                    $objPHPExcel->setActiveSheetIndex(0)->getRowDimension($startRowId)->setRowHeight(-1);
                    $startRowId = $startRowId + 11;
                }

                if (!empty($item["DETAIL_TEXT"])) {
                    $startRowId++;
                    $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $startRowId, "Описание");
                    $startRowId++;
                    $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $startRowId, strip_tags($item['DETAIL_TEXT']));
                    $objPHPExcel->getActiveSheet()->mergeCells('A' . $startRowId . ':B' . $startRowId);
                    $objPHPExcel->setActiveSheetIndex(0)->getStyle('A' . $startRowId . ':B' . $startRowId)->getAlignment()->setWrapText(true);
                   // $objPHPExcel->setActiveSheetIndex(0)->getRowDimension($startRowId)->setRowHeight(100);
                }

                $ignore_prop = Element::getIgnoreElementProps();

                foreach ($item['PROPS'] as $k => $prop) {
                    if ($prop['CODE'] == 'CML2_ARTICLE' || empty($prop['VALUE']) || in_array($prop['CODE'], $ignore_prop)) {
                        unset($item['PROPS'][$prop['CODE']]);
                    }
                }

                foreach ($item['PROPS'] as $prop) {
                    $startRowId++;
                    $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $startRowId, $prop['NAME']);
                    $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('B' . $startRowId, $prop['VALUE']);
                }
                $startRowId++;
                if(count($item['OPTION'])>0){
                    $startRowId++;
                    $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $startRowId, "Опции:");
                    $startRowId++;
                }
                foreach ($item['OPTION'] as $option) {

                    $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValue('A' . $startRowId, $option['NAME']);
                    $startRowId++;
                    if($option["DETAIL_PICTURE"]){
                        $pathImg = \Bitrix\Main\Application::getDocumentRoot() . \CFile::GetPath($option["DETAIL_PICTURE"]);
                        $mas_path = pathinfo($pathImg);
                        $origImgPath = $pathImg;
                        $tempFile = $mas_path['dirname'].'/'.$mas_path['filename'].'_small.'.$mas_path['extension'];
                        \CFile::ResizeImageFile(
                            $origImgPath,
                            $tempFile,
                            array('width'=>200,'height'=>150),
                            BX_RESIZE_IMAGE_PROPORTIONAL,
                            array(),
                            false,
                            false
                        );

                        $objDrawing = new \PHPExcel_Worksheet_Drawing();
                        $objDrawing->setPath($tempFile);
                        $objDrawing->setName('img ' . $option['ID']);
                        $objDrawing->setDescription('barcode' . $option['ID']);
                        $objDrawing->setCoordinates('A' . $startRowId);
                        $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
                        $objPHPExcel->setActiveSheetIndex(0)->getRowDimension($startRowId)->setRowHeight(-1);
                        $startRowId = $startRowId + 7;
                    }

                }
            }
//            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(32);
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
//            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
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