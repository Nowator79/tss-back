<?php

namespace Godra\Api\SetsBuilder;

use Godra\Api\Catalog\Element;
use Godra\Api\Helpers\Utility\Misc;

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
        'DETAIL_PAGE_URL'
    ];

    /**
     * метод для получения товаров по артиклу
     *
     * @param $arArticles
     * @return array
     */
    public static function GetByArticle($arArticles)
    {
        global $DB;
        $sql = "SELECT IBLOCK_ELEMENT_ID FROM b_iblock_element_property WHERE IBLOCK_PROPERTY_ID = 1097 and VALUE IN(" . $arArticles . ")";
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
    public static function makeOptionsArray($optionsString)
    {
        $arArticles = explode(';', $optionsString);
        $arOptionsIds = self::GetByArticle(implode(',', $arArticles));

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

    public static function getPrices($productId)
    {
        $db_res = \CPrice::GetList(
            [],
            [
                "PRODUCT_ID" => (int)$productId,
                "CATALOG_GROUP_ID" => PRICE_TYPE_IDS
            ]
        );
        while ($ar_res = $db_res->Fetch())
        {
            $price[]=$ar_res["PRICE"];
        }

        return $price;
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
    )
    {
        global $USER;
        $arProducts = [];
        \CModule::IncludeModule("iblock");

        $dbProduct = \CIBlockElement::GetList(
            ['NAME'=>'ASC'],
            $filter,
            false,
            false,
            $select ?? ['*']
        );

        while ($ar_res = $dbProduct->GetNextElement()) {
            $ar_fields = $ar_res->GetFields();
            $ar_props = $ar_res->GetProperties(array(), array('ACTIVE' => 'Y', 'EMPTY' => 'N'));

            if(!empty($ar_fields['PREVIEW_PICTURE'])) $ar_fields['PREVIEW_PICTURE'] = \CFile::GetByID($ar_fields['PREVIEW_PICTURE'])->Fetch()['SRC'];
            if(!empty($ar_fields['DETAIL_PICTURE'])) $ar_fields['DETAIL_PICTURE'] = \CFile::GetByID($ar_fields['DETAIL_PICTURE'])->Fetch()['SRC'];

            if(isset($ar_props['MORE_PHOTO'])) {
                foreach ($ar_props['MORE_PHOTO']['VALUE'] as $photo) {
                    $arPhoto[] = \CFile::GetByID($photo)->Fetch()['SRC'];
                }
                $ar_props['MORE_PHOTO'] = $arPhoto;
            }

            if ($withOptions) {
                if (isset($ar_props['DOP_KOMPLEKTATSIYA']['VALUE'])) {
                    $ar_fields['OPTIONS_LIST'] = self::makeOptionsArray($ar_props['DOP_KOMPLEKTATSIYA']['VALUE']);
                }
            }

            // Формируем выходной массив
            $ar_fields['TABS']['description'] = !empty($product['PREVIEW_TEXT']) ? $product['PREVIEW_TEXT'] : '';
            $ar_fields['TABS']['props'] = $ar_props;
            $ar_fields['TABS']['delivery'] = 'Доставка осуществляется курьером или возможен самовывоз';
            $ar_fields['TABS']['stocks'] = Element::getProductStocks($ar_fields['ID']);

            // Цены
            $ar_fields['PRICES'] = self::getPrices($ar_fields['ID']);

            $arProducts[] = $ar_fields;
        }

        return $arProducts;
    }

    /**
     * метод GET API
     *
     * @return array|string[]|null
     */
    public static function getProduct()
    {
        $params = Misc::getPostDataFromJson();

        if (empty($params['code']) || !isset($params['code'])) {
            return ['error' => 'Пустой поле code'];
        }

        $filter = [
            'IBLOCK_ID' => IBLOCK_CATALOG,
            'ACTIVE' => 'Y',
            'INCLUDE_SUBSECTIONS' => 'Y',
            'CODE' => $params['code']
        ];

        $product = self::getElement(
            self::$select_rows,
            $filter
        );

        return $product;
    }

    /**
     * метод получения опций
     * @return array|string[]
     */
    public static function getOptions(): array
    {
        $params = Misc::getPostDataFromJson();

        if (empty($params['ids']) || !isset($params['ids'])) {
            return ['error' => 'Пустое поле ids'];
        }

        $arOptions = [];
        $filter = ['ID' => $params['ids']] ;

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
?>