<?

namespace Godra\Api\Helpers;

use Godra\Api\Helpers\Utility\Misc;
use Godra\Api\SetsBuilder\Builder;
use Bitrix\Main\Loader;

class Nomenclature
{
    public static $arXls = [
        "0" => [
            "XML_ID" => "26032d02-4d89-11ea-80dd-a672da4d5bad",
            "NAME" => "АД-360С-Т400-1РМ6",
            "CUSTOM_NAME" => "Дизельный генератор",
            "NAMENCLATURE" => "Дизельный генератор ТСС АД-360С-Т400-1РМ6",
            "CODE1" => "ТСС",
            "CODE2" => "АД",
            "CODE3" => "360",
            "CODE4" => "С",
            "CODE5" => "Т",
            "CODE6" => "400",
            "CODE7" => "1",
            "CODE8" => "Р",
            "CODE9" => "",
            "CODE10" => "М",
            "CODE11" => "6",
            "CODE12" => "",
            "NOCODE" => "Нет",
            "NAME_V" => "TDz 500TS",
            "NAME_W" => "",
            "NAME_X" => "",
            "NAME_Y" => ""
        ],
        "1" => [
            "XML_ID" => "b9aacea9-5ea1-11ed-80fa-bc8c5a150f9b",
            "NAME" => "АД-36С-Т400-1РМ7",
            "CUSTOM_NAME" => "Дизельный генератор",
            "NAMENCLATURE" => "Дизельный генератор ТСС АД-36С-Т400-1РМ7",
            "CODE1" => "ТСС",
            "CODE2" => "АД",
            "CODE3" => "36",
            "CODE4" => "С",
            "CODE5" => "Т",
            "CODE6" => "400",
            "CODE7" => "1",
            "CODE8" => "Р",
            "CODE9" => "",
            "CODE10" => "М",
            "CODE11" => "7",
            "CODE12" => "",
            "NOCODE" => "Нет",
            "NAME_V" => "TWc 50TS", //Наименование фирменное часть1
            "NAME_W" => "", //Наименование фирменное (исполнение)
            "NAME_X" => "", //Наименование фирменное часть2
            "NAME_Y" => "TWc 50TS" //Фирменное наименование
        ]
    ];


    /**
     * получить товар из справочника шифра
     * временно поиск по массиву $arXls
     * @param $xmlId
     * @return void
     */
    public function getProductFromHL($xmlId) {
        $key = array_search($xmlId, array_column(self::$arXls, 'XML_ID'));
        return self::$arXls[$key];
    }

    /**
     * получить товар по внешнему коду
     *
     * @param $ID
     * @return void
     */
    public function getProductByCode($ID) {
        Loader::includeModule('iblock');
        $arFilter = [
            "XML_ID" => $ID
        ];

        $arFields = \CIBlockElement::GetList([],$arFilter,false,[] ,["ID", "CODE"])->Fetch();

        if ($arFields["CODE"]) {
            $res = Builder::getProduct($arFields["CODE"]);
        } else {
            $res = ['error' => 'Товар не найден'];
        }

        return $res;
    }

    /**
     * получить фирменное название товара
     * @return void
     */
    public static function getBrandedProductName($product, $hlProduct)
    {
        $name = $hlProduct['CUSTOM_NAME'];

        if (!empty($hlProduct["NAME_V"])) {
            $name = $name . ' ' . $hlProduct["NAME_V"];
        }

        if (!empty($hlProduct["NAME_W"])) {
            $name = $name . ' ' . $hlProduct["NAME_W"];
        }

        if (!empty($hlProduct["NAME_X"])) {
            $name = $name . ' ' . $hlProduct["NAME_X"];
        }

        return $name;
    }

    /**
     * получить название товара по ГОСТ
     * @return void
     */
    public static function getGostProductName($product, $hlProduct)
    {
        $name = $hlProduct['CUSTOM_NAME'];

        if (!empty($hlProduct["CODE1"])) {
            $name = $name . ' ' . $hlProduct["CODE1"];
        }

        if (!empty($hlProduct["CODE2"])) {
            $name = $name . ' ' . $hlProduct["CODE2"];
        }

        if (!empty($hlProduct["CODE3"])) {
            $name = $name . ' ' . $hlProduct["CODE3"];
        }

        if (!empty($hlProduct["CODE4"])) {
            $name = $name . ' ' . $hlProduct["CODE4"];
        }

        if (!empty($hlProduct["CODE5"])) {
            $name = $name . ' ' . $hlProduct["CODE5"];
        }

        if (!empty($hlProduct["CODE6"])) {
            $name = $name . ' ' . $hlProduct["CODE6"];
        }

        if (!empty($hlProduct["CODE7"])) {
            $name = $name . ' ' . $hlProduct["CODE7"];
        }

        if (!empty($hlProduct["CODE8"])) {
            $name = $name . ' ' . $hlProduct["CODE8"];
        }

        if (!empty($hlProduct["CODE9"])) {
            $name = $name . ' ' . $hlProduct["CODE9"];
        }

        if (!empty($hlProduct["CODE10"])) {
            $name = $name . ' ' . $hlProduct["CODE10"];
        }

        if (!empty($hlProduct["CODE11"])) {
            $name = $name . ' ' . $hlProduct["CODE11"];
        }

        if (!empty($hlProduct["CODE12"])) {
            $name = $name . ' ' . $hlProduct["CODE12"];
        }

        return $name;
    }

    /**
     * получить варианты названий товара
     *
     * @return void
     */
    public static function getCustomNames()
    {
        $params = Misc::getPostDataFromJson();

        if (empty($params['XML_ID'])) return ['error' => 'Не передан XML_ID'];

        //получаем товар
        $product = self::getProductByCode($params['XML_ID'])[0];
        $hlProduct = self::getProductFromHL($product['XML_ID']);

        if ($product["TABS"]["props"]["VID_OPTSII"]["VALUE"] == "Базовый агрегат" && $hlProduct) {
            $gostName = self::getGostProductName($product, $hlProduct);
            $brandedName = self::getBrandedProductName($product, $hlProduct);
        } else {
            $gostName = $brandedName = $product["NAME"];
        }

        $arNames = [
            "GOST_NAME" => $gostName,
            "BRANDED_NAME" => $brandedName
        ];

        return $arNames;
    }
}