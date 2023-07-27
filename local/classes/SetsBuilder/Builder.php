<?php

namespace Godra\Api\SetsBuilder;

use Godra\Api\Catalog\Element;
use Godra\Api\Helpers\Utility\Misc;


use \Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

class Builder
{
    protected static $select_rows = [
        'ID',
        'IBLOCK_ID',
        'NAME',
        'CODE',
        'XML_ID',
        'IBLOCK_SECTION_ID',
        'DETAIL_TEXT',
        'PREVIEW_TEXT',
        'SHOW_COUNTER',
        'PREVIEW_PICTURE',
        'DETAIL_PICTURE',
        'DETAIL_PAGE_URL',
        'PROPERTY_CML2_ARTICLE',
    ];

    /**
     * метод для получения товаров по артиклу
     *
     * @param $arArticles
     * @return array
     */
    public static function GetByArticle($arArticles): array
    {
        global $DB;
        $results = [];

        if (is_array($arArticles)) {
            $arArticles = $arArticles[0];
        }

        $sql = "SELECT IBLOCK_ELEMENT_ID, VALUE FROM b_iblock_element_property WHERE IBLOCK_PROPERTY_ID = " . ARTICLE_PROP_ID . " AND VALUE IN (" . $arArticles . ")";
        $dbRes = $DB->Query($sql);
        while ($res = $dbRes->Fetch()) {
            $results[] = $res["IBLOCK_ELEMENT_ID"];
        }

        return $results;
    }

    /**
     * метод для получения товаров входящих в опцию
     *
     * @param $optionsString
     * @return array
     */
    public static function makeOptionsArray($optionsString): array
    {
        $arArticles = explode(';', $optionsString);
        foreach ($arArticles as $article) {
            $arComaArticle[] = "'" . $article . "'";
        }
        $arArticles = implode(',', $arComaArticle);

        $arOptionsIds = self::GetByArticle($arArticles);

        if (empty($arOptionsIds)) return [];

        $arFilter = [
            'IBLOCK_ID' => IBLOCK_CATALOG,
            'ID' => $arOptionsIds
        ];

        $db_res = \CIBlockElement::GetList(
            false,
            $arFilter,
            false,
            false,
            [
                'ID',
                'IBLOCK_ID'
            ]
        );

        while ($ar_res = $db_res->GetNextElement()) {
            $ar_fields = $ar_res->GetFields();
            $ar_props = $ar_res->GetProperties([], ['ACTIVE' => 'Y', 'EMPTY' => 'N']);
            $arOptions[$ar_props['VID_OPTSII']['VALUE']][] = $ar_fields['ID'];
        }

        return $arOptions;
    }

    /**
     * метод для получения цен товара
     *
     * @param $productId
     * @return array
     */
    public static function getPrices($productId): array
    {
        $allProductPrices = \Bitrix\Catalog\PriceTable::getList([
            "select" => ["CATALOG_GROUP_ID", "PRICE", "CURRENCY"],
            "filter" => [
                "=PRODUCT_ID" => $productId,
                "CATALOG_GROUP_ID" => PRICE_TYPE_IDS
            ],
            "order" => ["CATALOG_GROUP_ID" => "ASC"]
        ])->fetchAll();

        global $USER;
        $quantity = 1;
        $renewal = 'N';
        $arPrice = \CCatalogProduct::GetOptimalPrice(
            $productId,
            $quantity,
            $USER->GetUserGroupArray(),
            $renewal
        );
        $allProductPrices[] = [
            "CATALOG_GROUP_ID" => "496",
            "PRICE" => $arPrice['PRICE']['PRICE'],
            "CURRENCY" => "RUB"
        ];

        return $allProductPrices;
    }

    /**
     * метод для получения товара
     *
     * @param false $select
     * @param false $filter
     * @return array|void
     */
    public static function getElement(
        $select = false,
        $filter = false,
        $withOptions = true
    ): array
    {
        global $USER;
        $arProducts = [];
        \CModule::IncludeModule("iblock");

        $dbProduct = \CIBlockElement::GetList(
            ['NAME' => 'ASC'],
            $filter,
            false,
            ["nPageSize" => 50],
            $select ?? ['*']
        );

        while ($ar_res = $dbProduct->GetNextElement()) {
            $ar_fields = $ar_res->GetFields();
            $ar_props = $ar_res->GetProperties([], ['ACTIVE' => 'Y', 'EMPTY' => 'N']);
            unset($ar_props["CML2_TRAITS"]);
            unset($ar_props["CML2_TAXES"]);
            unset($ar_props["FILES"]);
            unset($ar_props["YAVLYAETSYA_DGU"]);

            $all_props = $ar_props;
            $ignore_prop = Element::getIgnoreElementProps();

            foreach ($ar_props as $k => $prop) {
                if ($prop['CODE'] == 'CML2_ARTICLE' || empty($prop['VALUE']) || in_array($prop['CODE'], $ignore_prop)) {
                    unset($ar_props[$prop['CODE']]);
                }
            }

            if (!empty($ar_fields['DETAIL_PICTURE'])) $ar_fields['PREVIEW_PICTURE'] = \CFile::GetByID($ar_fields['DETAIL_PICTURE'])->Fetch()['SRC'];
            if (!empty($ar_fields['DETAIL_PICTURE'])) $ar_fields['DETAIL_PICTURE'] = \CFile::GetByID($ar_fields['DETAIL_PICTURE'])->Fetch()['SRC'];

            if (isset($all_props['MORE_PHOTO'])) {
                foreach ($all_props['MORE_PHOTO']['VALUE'] as $photo) {
                    $arPhoto[] = \CFile::GetByID($photo)->Fetch()['SRC'];
                }
                $all_props['MORE_PHOTO'] = $arPhoto;
            }

            if ($withOptions) {
                if (isset($all_props['DOP_KOMPLEKTATSIYA']['VALUE'])) {
                    $ar_fields['OPTIONS_LIST'] = self::makeOptionsArray($all_props['DOP_KOMPLEKTATSIYA']['VALUE']);
                }
            }

            // Формируем выходной массив
            $ar_fields['TABS']['description'] = !empty($product['PREVIEW_TEXT']) ? $product['PREVIEW_TEXT'] : '';
            $ar_fields['TABS']['props'] = $all_props;
            $ar_fields['TABS']['filtered_props'] = $ar_props;
            $ar_fields['TABS']['delivery'] = 'Доставка осуществляется курьером или возможен самовывоз';
            $ar_fields['TABS']['stocks'] = Element::getProductStocks($ar_fields['ID']);

            // Цены
            $ar_fields['PRICES'] = self::getPrices($ar_fields['ID']);

            // получение скидки
            global $USER;
            $rsUser = \CUser::GetByID($USER->GetID());
            $arUser = $rsUser->Fetch();
            Loader::includeModule("highloadblock");
            $hlbl = 72; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
            $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entity_data_class = $entity->getDataClass();

            $rsData = $entity_data_class::getList(array(
                "select" => array("ID", "UF_SKIDKA"),
                "order" => array("ID" => "ASC"),
                //"filter" => array("UF_USER_ID"=>$arUser['XML_ID'], "<UF_DATE_END" => date("d.m.Y H:i:s")),  // Задаем параметры фильтра выборки
                "filter" => array("UF_PRODUCT_ID"=>$ar_fields['PROPERTY_CML2_ARTICLE_VALUE'], "UF_USER_ID"=>$arUser['XML_ID'],">UF_DATE_END" => date("d.m.Y H:i:s")),
            ));
            $cont_discount = false;
            while($arData = $rsData->Fetch()){
                $cont_discount =  $arData['UF_SKIDKA'];
            }

            if($cont_discount) {
                $ar_fields['PRICES'][1]['PRICE'] = $ar_fields['PRICES'][0]['PRICE'] - ($ar_fields['PRICES'][0]['PRICE'] * $cont_discount / 100);
            }

            $arProducts[] = $ar_fields;
        }

        return $arProducts;
    }

    /**
     * метод GET API
     *
     * @return array|string[]|null
     */
    public static function getProduct($code = '', $xmlId = '')
    {
        $params = Misc::getPostDataFromJson();
        if (!empty($code)) $params["code"] = $code;

        //harcode пока нету инфы с фронта
//        $params['mainCode'] = 'dizelnyy_generator_tss_ad_1000s_t400_1rm9';


        $filter = [
            'IBLOCK_ID' => IBLOCK_CATALOG,
            'ACTIVE' => 'Y',
            'INCLUDE_SUBSECTIONS' => 'Y',
        ];

        if (!empty($xmlId)) {
            $filter['XML_ID'] = $xmlId;
        } else {

            if (isset($params['options'])) {
                $filter['ID'] = $params['options'];
            }

            if (isset($params['query'])) {
                array_push(self::$select_rows, 'PROPERTY_CML2_ARTICLE');

                if (is_numeric($params['query'])) {
                    $filter['PROPERTY_CML2_ARTICLE'] = $params['query'];
                } else {
                    $filter['NAME'] = '%' . $params['query'] . '%';
                }
            } else {
                if (empty($params['code']) || !isset($params['code'])) {
                    return ['error' => 'Пустой поле code'];
                }

                $filter['CODE'] = $params['code'];
            }
        }
        $products = self::getElement(
            self::$select_rows,
            $filter
        );

        $IBLOCK_SECTION_IDsAVR = [1175, 1176, 1180,1177, 1178, 1179,1181, 1182];
        $IBLOCK_SECTION_IDsKon = [1103, 1104, 1105, 1106];
        if (!empty($params['code']) && !empty($params['mainCode'])){
            $filter['CODE'] = $params['mainCode'];
            $mainProduct = self::getElement(
                self::$select_rows,
                $filter
            );
            if ($mainProduct[0]['IBLOCK_SECTION_ID'] == 1062){
                if (in_array($products[0]['IBLOCK_SECTION_ID'], $IBLOCK_SECTION_IDsAVR)){
                    $filter['CODE'] ='podgotovka_pod_avr';
                    $AVR_SUB = self::getElement(
                        self::$select_rows,
                        $filter
                    );
                }
                if (in_array($products[0]['IBLOCK_SECTION_ID'], $IBLOCK_SECTION_IDsKon)){
                    $filter['CODE'] =['montazhnyy_komplekt_avr_1000_kvt', 'semnaya_tortsevaya_stenka_pbk'];
                    $AVR_SUB = self::getElement(
                        self::$select_rows,
                        $filter
                    );
                }

            }
        }
        if (!empty($AVR_SUB)){
            $products[0]['SUB_ITEM'] = $AVR_SUB;
        }

        return $products;
    }

    /**
     * метод получения опций
     * @return array|string[]
     */
    public static function getOptions($arOptionsIds = []): array
    {
        $params = Misc::getPostDataFromJson();

        if (!empty($arOptionsIds)) {
            $params['ids'] = $arOptionsIds;
        }

        if (empty($params['ids']) || !isset($params['ids'])) {
            return ['error' => 'Пустое поле ids'];
        }

        $filter = ['ID' => $params['ids']];

        $arOptions = self::getElement(
            self::$select_rows,
            $filter,
            true
        );

        return $arOptions;
    }

    /**
     * метод получения основного товара
     *
     * @return array|string[]
     */
    public static function getMainProduct(): array
    {
        $arMainProduct = self::getProduct() ?? [];

        return $arMainProduct;
    }
}