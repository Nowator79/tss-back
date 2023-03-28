<?

namespace Godra\Api\Helpers;

use Godra\Api\Helpers\Utility\Misc;
use Godra\Api\SetsBuilder\Builder;
use Bitrix\Main\Loader;
use Shuchkin\SimpleXLSX;
use Bitrix\Highloadblock as HL,
    Bitrix\Main\Entity;

class Nomenclature
{
    public static $customName = "Дизельный генератор";
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

    public static function importFromXls($path)
    {
        if ($xlsx = SimpleXLSX::parse($path)) {
            Loader::includeModule("highloadblock");

            $hlblock = HL\HighloadBlockTable::getById(HIGHLOAD_PRODUCT_CODE_ID)->fetch();
            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();

            $arRows = $xlsx->rows();
            foreach ($arRows as $row => $r) {
                if ($row === 0 || empty($r[3])) {
                    continue;
                }

                $data = [
                    "UF_XML_ID" => $r[5],
                    "UF_NAME" => $r[4],
                    "UF_CUSTOM_NAME" => self::$customName,
                    "UF_NAMENCLATURE" => $r[4],
                    "UF_CODE1" => $r[10],
                    "UF_CODE2" => $r[11],
                    "UF_CODE3" => $r[12],
                    "UF_CODE4" => $r[13],
                    "UF_CODE5" => $r[14],
                    "UF_CODE6" => $r[15],
                    "UF_CODE7" => $r[16],
                    "UF_CODE8" => $r[17],
                    "UF_CODE9" => $r[18],
                    "UF_CODE10" => $r[19],
                    "UF_CODE11" => $r[20],
                    "UF_CODE12" => $r[21],
                    "UF_NOCODE" => $r[22],
                    "UF_NAME_V" => $r[23],
                    "UF_NAME_W" => $r[24],
                    "UF_NAME_X" => $r[25],
                    "UF_NAME_Y" => $r[26]
                ];

                $entity_data_class::add($data);
            }
            return $xlsx;
        }
    }

    /**
     * получить товар из справочника шифра
     * временно поиск по массиву $arXls
     * @param $xmlId
     * @return void
     */
    public function getProductFromHL($xmlId)
    {
        Loader::includeModule("highloadblock");

        $hlblock = HL\HighloadBlockTable::getById(HIGHLOAD_PRODUCT_CODE_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        $rsData = $entity_data_class::getList(array(
            "select" => array("*"),
            "order" => array("ID" => "ASC"),
            "filter" => array("UF_XML_ID" => $xmlId)  // Задаем параметры фильтра выборки
        ));

        return $rsData->Fetch();
    }

    /**
     * получить товар по внешнему коду
     *
     * @param $ID
     * @return void
     */
    public function getProductByCode($ID)
    {
        Loader::includeModule('iblock');
        $arFilter = [
            "XML_ID" => $ID
        ];

        $arFields = \CIBlockElement::GetList([], $arFilter, false, [], ["ID", "CODE"])->Fetch();

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
    public static function getBrandedProductName($product, $hlProduct, $arOptionsParams)
    {
        $name = $hlProduct['UF_CUSTOM_NAME'];

        if (!empty($hlProduct["UF_NAME_V"])) {
            $name = $name . ' ' . $hlProduct["UF_NAME_V"];
        }

        if (!empty($hlProduct["UF_NAME_W"])) {
            $name = $name . ' ' . $hlProduct["UF_NAME_W"];
        }

        if (!empty($hlProduct["UF_NAME_X"])) {
            $name = $name . ' ' . $hlProduct["UF_NAME_X"];
        }

        return $name;
    }

    /**
     * получить свойства опций
     *
     * @return void
     */
    public static function getOptionsParams($selectedOptions)
    {
        $options = [];
//        $arOptionsIds = Builder::makeOptionsArray($selectedOptions);

        foreach ($selectedOptions as $vidOpcii => $optionID) {
            $arProduct = Builder::getElement(false, ['ID' => $optionID])[0];

            $options["VID"][] = $arProduct["TABS"]["props"]["VID_OPTSII"]["VALUE"];
            $options["ART"][] = $arProduct["TABS"]["props"]["CML2_ARTICLE"]["VALUE"];
            $options["STA"][] = $arProduct["TABS"]["props"]["STEPEN_AVTOMATIZATSII"]["VALUE"];
        }

        return $options;
    }

    /**
     * получить название товара по ГОСТ
     * @return void
     */
    public static function getGostProductName($product, $hlProduct, $arOptionsParams)
    {
        $name = $hlProduct['UF_CUSTOM_NAME'] ?? $product['NAME'];

        $is_complex = false;

        if (!empty($hlProduct["UF_CODE1"])) {
            $name = $name . ' ' . $hlProduct["UF_CODE1"];
        }

//        if (in_array("прицеп", $arOptionsParams["VID"]) || in_array("прицеп для контейнера", $arOptionsParams["VID"])) {
//            $hlProduct["UF_CODE2"] = "ЭД";
//        }

        foreach ($arOptionsParams["VID"] as $value){
            if(stripos($value, 'Прицеп')!==false){
                $hlProduct["UF_CODE2"] = "ЭД";
            }
        }

        if (!empty($hlProduct["UF_CODE2"])) {
            $name = $name . ' ' . $hlProduct["UF_CODE2"].'-';
        }

        if (!empty($hlProduct["UF_CODE3"])) {
            $name = $name . '' . $hlProduct["UF_CODE3"];
        }

        if (!empty($hlProduct["UF_CODE4"])&&$hlProduct["UF_CODE2"]!="ЭД") {
            $name = $name . '' . $hlProduct["UF_CODE4"];
        }

        if (!empty($hlProduct["UF_CODE5"])) {
            $name = $name . '-' . $hlProduct["UF_CODE5"];
        }

        if (!empty($hlProduct["UF_CODE6"])) {
            $name = $name . '' . $hlProduct["UF_CODE6"];
        }


        if (in_array("Блок АВР", $arOptionsParams["VID"])) {
            $hlProduct["UF_CODE7"] = 2;
        }


        if (in_array("231020", $arOptionsParams["ART"])) {
            $hlProduct["UF_CODE7"] = 3;
        }

        if (!empty($hlProduct["UF_CODE7"])) {
            $name = $name . '-' . $hlProduct["UF_CODE7"];
        }

        if (!empty($hlProduct["UF_CODE8"])) {
            $name = $name . '' . $hlProduct["UF_CODE8"];
        }

        //9
        if (in_array("Контейнер", $arOptionsParams["VID"])) {
            $hlProduct["UF_CODE9"] = "Н";
        }


        if (in_array("капот", $arOptionsParams["VID"])) {
            $hlProduct["UF_CODE9"] = "П";
        }

        if (in_array("кожух", $arOptionsParams["VID"])) {
            $hlProduct["UF_CODE9"] = "К";
        }

        if (!empty($hlProduct["UF_CODE9"])) {
            $name = $name . '' . $hlProduct["UF_CODE9"];
        }

        if (!empty($hlProduct["UF_CODE10"])) {
            $name = $name . '' . $hlProduct["UF_CODE10"];
        }

        if (!empty($hlProduct["UF_CODE11"])) {
            $name = $name . '' . $hlProduct["UF_CODE11"];
        }

        //12
        if (in_array("ПЖД", $arOptionsParams["VID"])) {
            $hlProduct["UF_CODE12"] = "ПЖД";
        }

        if (in_array("ПОЖ", $arOptionsParams["VID"])) {
            if (in_array("2", $arOptionsParams["STA"]) || in_array("Блок АВР", $arOptionsParams["VID"])) {
                $hlProduct["UF_CODE12"] = ($hlProduct["UF_CODE12"] == "ПЖД") ? "ПЖД" : "";
            } else {
                $hlProduct["UF_CODE12"] = "ПОЖ";
            }
        }

        if (!empty($hlProduct["UF_CODE12"])) {
            $name = $name . '-' . $hlProduct["UF_CODE12"];
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

        if (trim($product["TABS"]["props"]["VID_OPTSII"]["VALUE"]) == "Базовый агрегат" && trim($hlProduct["UF_NOCODE"]) == "Нет") {
            $arOptionsParams = [];
            if (!empty($params['SELECTED_OPTIONS'])) {
                $arOptionsParams = self::getOptionsParams($params['SELECTED_OPTIONS']);
            }

            $gostName = self::getGostProductName($product, $hlProduct, $arOptionsParams);
            $brandedName = self::getBrandedProductName($product, $hlProduct, $arOptionsParams);
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