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
                if ($property['NAME'] == 'OPTION' && $property['VALUE']) {
                    $item_el['options'] = [];
                    $arSelect = array("ID", "NAME", "XML_ID");
                    $arFilter = array("IBLOCK_ID" => 5, 'XML_ID' => explode(';', $property['VALUE']), "ACTIVE" => "Y");
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
                            $item_el['origin_price'] += $ar_res["PRICE"];
                        }
                    }

                }
                if ($property['NAME'] == 'COMMENT') {
                    $item_el['comment'] = $property['VALUE'];
                }
                if ($property['NAME'] == 'PROPS') {
                    $item_el['comment'] = json_decode($property['VALUE']);
                }
            }
            if (!isset($item_el['options'])) {
                $db_res = \CPrice::GetList(
                    array(),
                    array(
                        "PRODUCT_ID" => $item_el['id'],
                        "CATALOG_GROUP_ID" => 496
                    )
                );
                if ($ar_res = $db_res->Fetch()) {
                    $item_el['origin_price'] = $ar_res["PRICE"];
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

    /**
     * Получить коммерческое предложение из корзины
     *
     * @return string|void
     */
    public function getInvoice()
    {
        global $USER;
        $useId = ($USER->GetID() == 0) ? 1 : $USER->GetID();
        $params = Misc::getPostDataFromJson();
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser($useId, \Bitrix\Main\Context::getCurrent()->getSite());

        if (count($basket->getQuantityList())) {
            $fileExt = 'xls';
            $fileName = "invoice_{$basket->getFUserId()}.{$fileExt}";
            $tempDir = $_SESSION['REPORT_EXPORT_TEMP_DIR'] = \CTempFile::GetDirectoryName(1, array('invoice', uniqid('basket_invoice_')));
            \CheckDirPath($tempDir);
            $filePath = "{$tempDir}{$fileName}";
            $logoSrc = "";
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
                            <td colspan="3">Коммерческое предложение от '.date("Y-m-d H:i:s").'</td>
                            <td colspan="3">'.$params["contragent"].'</td>
                        </tr>
                        <tr>
                            <td colspan="3"><img src="'.$logoSrc.'">Лого</td>
                            <td colspan="3">
                                <tr>'.$params["company"].'</tr>
                                <tr>'.$params["name"].'</tr>
                                <tr>'.$params["phone"].'</tr>
                                <tr>'.$params["email"].'</tr>
                            </td>
                        </tr>
                        <tr colspan="6">На ваш запрос предлагаем вам следующее решение под вашу индивидуальную потребность:</tr>
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
                $row = '<tr>
                                    <td>' . $item->getProductId() . '</td>
                                    <td>' . $item->getField("NAME") . '</td>
                                    <td>' . $item->getQuantity() . '</td>
                                    <td>шт</td>
                                    <td>' . $item->getPrice() . '</td>
                                    <td>' . $item->getFinalPrice() . '</td>
                                </tr>';
                file_put_contents($filePath, $row, FILE_APPEND);
            }

            $row =  '<tr colspan="6">Детализация комплектации указана в Приложении №1 к данному технико-коммерческому предложению (ТКП)</tr>
                     <tr colspan="6">Ваш персональный менеджер:</tr>
                     <tr colspan="6">ФИО_Пользователя_кабинета</tr>
                     <tr colspan="6">Телефон_Ползователя_кабинета</tr>
                     <tr colspan="6">Почта_ползователя_кабинетп</tr>
                     <tr colspan="6"></tr>
                     <tr colspan="6"></tr>
                     <tr colspan="6"></tr>
                     <tr colspan="6">Приложение 1</tr>
                     <tr colspan="6"></tr>';

            file_put_contents($filePath, $row, FILE_APPEND);
            foreach ($basket as $item) {
                $row =  '<tr colspan="6">'. $item->getField("NAME") .'</tr>
                         <tr colspan="6">'. $item->getField("DETAIL_PICTURE") .'</tr>
                         <tr colspan="6"></tr>';
            }

            file_put_contents($filePath, $row, FILE_APPEND);
            file_put_contents($filePath, '</table></body></html>', FILE_APPEND);

            return str_replace(\Bitrix\Main\Application::getDocumentRoot(), '', $filePath);
        }
    }
}

?>