<?php

namespace Godra\Api\SetsBuilder;

use Godra\Api\Catalog\Element;
use Godra\Api\Helpers\Utility\Misc;

class Builder
{
    protected static $select_rows = [
        'ID',
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
            ['*']
        );

        while ($ar_res = $db_res->GetNextElement()) {
            $ar_fields = $ar_res->GetFields();
            $ar_props = $ar_res->GetProperties(array(), array('ACTIVE' => 'Y', 'EMPTY' => 'N'));
            $arOptions[$ar_props['VID_OPTSII']['VALUE']][] = $ar_fields['ID'];
        }
        return $arOptions;
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
        $priceType = false,
        $withOptions = true
    )
    {
        global $USER;
        $arProducts = [];
        \CModule::IncludeModule("iblock");

        $db_res = \CIBlockElement::GetList(
            false,
            $filter,
            false,
            false,
            $select ?? ['*']
        );

        while ($ar_res = $db_res->GetNextElement()) {
            $ar_fields = $ar_res->GetFields();
            $ar_props = $ar_res->GetProperties(array(), array('ACTIVE' => 'Y', 'EMPTY' => 'N'));

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
            $certificates = Element::getPropertyFiles($ar_fields['ID'], 'CERTIFICATES');

            // Цены
            $ar_fields['PRICES'] = \CCatalogProduct::GetOptimalPrice($ar_fields['ID'], 1, $USER->GetUserGroupArray(), 'N');

            $arProducts[] = $ar_fields;
        }

        return $arProducts;
    }

    public static function getProduct()
    {
        $params = Misc::getPostDataFromJson();

        if (empty($params['code']) || !isset($params['code'])) {
            return ['error' => 'Пустой поле code'];
        }

        // @TODO определить дефолтный тип цен
        $priceTypeXmlId = (new \Godra\Api\Helpers\Contract)->getPriceTypeByUserId(\Bitrix\Main\Engine\CurrentUser::get()->getId()) ?? '3f0fb9d3-7b9f-11e4-aa20-e0db5502c576';
        //

        $filter = ['CODE' => $params['code']];

        $product = self::getElement(
            self::$select_rows,
            $filter,
            $priceTypeXmlId,
        );

        return $product;
    }

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
            false,
            true
        );

        return $arOptions;
    }

    public static function getMainProduct(): array
    {
        $arMainProduct = self::getProduct() ?? [];

        return $arMainProduct;
    }
}
?>