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
            "NAME_V" => "TWc 50TS",
            "NAME_W" => "",
            "NAME_X" => "",
            "NAME_Y" => "TWc 50TS"
        ]
    ];

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
    public static function getBrandedProductName($product)
    {
        $name = '';
        return $name;
    }

    /**
     * получить название товара по ГОСТ
     * @return void
     */
    public static function getGostProductName($product)
    {
        $name = '';
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
        $product = self::getProductByCode($params['XML_ID']);

        if ($product[0]["TABS"]["props"]["VID_OPTSII"]["VALUE"] == "Базовый агрегат") {
            $gostName = self::getGostProductName($product[0]);
            $brandedName = self::getBrandedProductName($product[0]);
        } else {
            $gostName = $brandedName = $product[0]["NAME"];
        }

        $arNames = [
            "GOST_NAME" => $gostName,
            "BRANDED_NAME" => $brandedName
        ];

        return $arNames;
    }
}