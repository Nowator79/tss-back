<?
namespace Godra\Api\Iblock;

use Bitrix\Main\Loader;
use Godra\Api\Helpers\Utility\Misc;

class Stock extends Base
{
    protected static $row_data = [
    ];

    protected static $select_rows = [
        [ 'name' => 'ID' ],
        [ 'name' => 'SORT'],
        [ 'name' => 'NAME'],
        [ 'name' => 'DETAIL_TEXT'],
        [ 'name' => 'PREVIEW_TEXT'],
        [ 'name' => 'CODE', 'alias' => 'element_code'],
        [ 'name' => 'PREVIEW_PICTURE', 'method' => '\\CFile::GetPath'],
        [ 'name' => 'DETAIL_PICTURE', 'method' => '\\CFile::GetPath'],
        
        ['name' => 'HIDE_HEADER', 'alias' => 'hide_header'],
        ['name' => 'COLOR_HEADER', 'alias' => 'color_header'],
    ];

    protected static $api_ib_code = IBLOCK_STOCK_API;

    public static function getList()
    {
        // return self::get();
        // нужно переделать

        // получение данных из post
        $params = Misc::getPostDataFromJson();

        if (!empty($params['code'])) {
            return ['error' => 'Для получения информации об акции используйте /api/stock/getByCode'];
        };

        $stocksData = self::getStocksData(['PREVIEW_TEXT'], false, ['SORT' => 'desc']);

        foreach ($stocksData as $key => $stockData) {
            if ($key == 0) {
                // удаляем лишнее
                unset($stocksData[$key]['additional_text']);
                unset($stocksData[$key]['content']);
                unset($stocksData[$key]['correction']);
                unset($stocksData[$key]['note']);
                unset($stocksData[$key]['contentType']);
                unset($stocksData[$key]['discount']);
            }
        }
        
        if ($stocksData)
        {
            foreach ($stocksData as $key => $stocksDataItem)
            {
                if (!$stocksDataItem['hide_header'])
                {
                    $stocksData[$key]['hide_header'] = false;
                }
                else
                {
                    $stocksData[$key]['hide_header'] = true;
                }
                
                if ($stocksDataItem['color_header'] == 4621)
                {
                    $stocksData[$key]['color_header'] = 'white';
                }
                
                if ($stocksDataItem['color_header'] == 4622)
                {
                    $stocksData[$key]['color_header'] = 'black';
                }
                
                if (!$stocksDataItem['color_header'])
                {
                    $stocksData[$key]['color_header'] = '';
                }
            }
        }

        return $stocksData;
    }

    // Метод для получения детальной страницы акции
    public static function getByCode() {
        //return self::get();

        // получение данных из post
        $params = Misc::getPostDataFromJson();

        if (!$params['code']) {
            return [
                'error' => 'Не передан код code акции'
            ];
        }

        return self::getStocksData(
            [
                'DETAIL_PICTURE',
                'DETAIL_TEXT'
            ],
            [
                'CODE' => $params['code']
            ]
        )[0];
    }

    /**
     * Метод для получения массива фильтра по умолчанию
     *
     * @return array
     */
    public static function getDefaultFilter() {
        return [
            'IBLOCK_ID' => 3,
            'ACTIVE' => 'Y'
        ];
    }

    /**
     * МЕтод для получения выбираемых по умолчанию полей
     *
     * @return string[]
     */
    public function getDefaultSelect() {
        return [
            'ID',
            'CODE',
            'NAME',
            'PREVIEW_PICTURE',

            // доп. текст
            'PROPERTY_ADDITIONAL_TEXT',
            // тип отображения
            'PROPERTY_TYPE',
            // Цена начинается от
            'PROPERTY_PRICE_FROM',
            // дата окончания активности
            'DATE_ACTIVE_TO',
            // Уточнение
            'PROPERTY_CORRECTION',
            // Сноска
            'PROPERTY_NOTE',

            // Скидка
            'PROPERTY_DISCOUNT',
            
            'PROPERTY_HIDE_HEADER',
            'PROPERTY_COLOR_HEADER',

            // множественный свойства
            // товары акции
            //'PROPERTY_PRODUCTS',
            // разделы акции
            //'PROPERTY_SECTIONS'
        ];
    }

    /**
     * Метод для получения информации об акции
     *
     * @param false $select
     * @param false $filter
     * @param false $order
     * @param false $nav
     * @return array
     */
    public static function getStocksData(
        $select = false,
        $filter = false,
        $order = false,
        $nav = false
    ) {
        Loader::includeModule('iblock');

        $stocksData = [];

        $defaultFilter = self::getDefaultFilter();
        $defaultSelect = self::getDefaultSelect();

        $stocksObjs = \CIBlockElement::GetList(
            $order ? $order : false,
            $filter ? array_merge($defaultFilter, $filter) : $defaultFilter,
            false,
            $nav ? $nav : false,
            $select ? array_merge($select, $defaultSelect) : $defaultSelect
        );
        
        while ($row = $stocksObjs->Fetch()) 
        {
            if ($row['PROPERTY_TYPE_VALUE'] == 'Цена начинается от') {
                // цена начинается от
                $content = $row['PROPERTY_PRICE_FROM_VALUE'];
                $contentType = 'price';
            } else {
                // таймер
                $content = $row['DATE_ACTIVE_TO'];
                $contentType = 'date';
            }

            if ($row['PROPERTY_DISCOUNT_VALUE'] !== ''
                && $row['PROPERTY_DISCOUNT_VALUE'] !== 'null'
                && $row['PROPERTY_DISCOUNT_VALUE'] !== null) {
                $discountRaw = explode(' ', trim($row['PROPERTY_DISCOUNT_VALUE']));
            }

            // для анонса
            if (in_array('PREVIEW_TEXT', $select)) {
                $stocksData[] = [
                    'id' => (int) $row['ID'],
                    'code' => $row['CODE'],
                    'name' => $row['NAME'],
                    'picture' => \CFile::GetPath($row['PREVIEW_PICTURE']) ?? '',
                    'description' => $row['PREVIEW_TEXT'] ?? '',
                    // доп. текст
                    'additional_text' => $row['PROPERTY_ADDITIONAL_TEXT_VALUE'] ?? '',
                    'contentType' => $contentType,
                    // либо "Цена начинается от", либо "Дата окончания активности"
                    'content' => $contentType == 'date' ? strtotime($content ) : $content,
                    // скидка
                    'discount' => $discountRaw ? [
                        'name' => $discountRaw[0],
                        'value' => $discountRaw[1],
                    ] : [],
                    // Уточнение
                    'correction' => $row['PROPERTY_CORRECTION_VALUE'] ?? '',
                    // Сноска
                    'note' => $row['PROPERTY_NOTE_VALUE'] ?? '',
                    'color_header' => $row['PROPERTY_COLOR_HEADER_ENUM_ID'] ?? '',
                    'hide_header' => $row['PROPERTY_HIDE_HEADER_VALUE'] ?? '',
                ];
            } elseif (in_array('DETAIL_TEXT', $select) || in_array('DETAIL_PICTURE', $select)) {
                // для деталки
                // файлы
                $files = self::getFileData((int) $row['ID']);

                $propuctsIds = self::getEnumPropertyId((int) $row['ID'], 'PRODUCTS');
                $sectionsIds = self::getEnumPropertyId((int) $row['ID'], 'SECTIONS');

                // информация о товарах акции
                $stockProds = \Godra\Api\Catalog\Element::getElements(
                    \Godra\Api\Catalog\Element::getSelectFields(),
                    [
                        [
                            'LOGIC' => 'OR',
                            [
                                'SECTION_ID' => $sectionsIds,
                                'INCLUDE_SUBSECTIONS' => 'Y'
                            ],
                            ['ID' => $propuctsIds]
                        ]
                    ],
                    [
                        'PROPERTY_POPULAR_OFFER' => 'asc,nulls'
                    ]
                );

                $stocksData[] = [
                    'id' => (int) $row['ID'],
                    'code' => $row['CODE'],
                    'name' => $row['NAME'],
                    'picture' => \CFile::GetPath($row['DETAIL_PICTURE']) ?? '',
                    'description' => $row['DETAIL_TEXT'] ?? '',
                    'files' => $files ?? [],
                    'stockProds' => $stockProds ?? [],
                ];
            } else {
                // для плиточек, превью
                $stocksData[] = [
                    'id' => (int) $row['ID'],
                    'code' => $row['CODE'],
                    'name' => $row['NAME'],
                    'picture' => \CFile::GetPath($row['PREVIEW_PICTURE']),
                    // доп. текст
                    'additional_text' => $row['PROPERTY_ADDITIONAL_TEXT_VALUE'] ?? '',
                    'contentType' => $contentType,
                    // либо "Цена начинается от", либо "Дата окончания активности"
                    'content' => $contentType == 'date' ? strtotime($content ) : $content,
                    // скидка
                    'discount' => $discountRaw ? [
                        'name' => $discountRaw[0],
                        'value' => $discountRaw[1],
                    ] : [],
                    // Уточнение
                    'correction' => $row['PROPERTY_CORRECTION_VALUE'] ?? '',
                    // Сноска
                    'note' => $row['PROPERTY_NOTE_VALUE'] ?? '',
                    'color_header' => $row['PROPERTY_COLOR_HEADER_ENUM_ID'] ?? '',
                    'hide_header' => $row['PROPERTY_HIDE_HEADER_VALUE'] ?? '',
                ];
            }
        }

        return $stocksData;
    }

    /**
     * Метод для получения данных о файле
     *
     * @param $fileId
     * @return array
     */
    public static function getFileData($stockEntityId) {
        $files = [];

        $filesObj = \CIBlockElement::GetProperty(
            3,
            (int) $stockEntityId,
            ['ACTIVE' => 'Y'],
            ['CODE' => 'FILES']
        );

        while ($row = $filesObj->Fetch()) {
            if ((int) $row['VALUE'] !== 0) {
                $fileArray = \CFile::GetFileArray($row['VALUE']);

                $files[] = [
                    'id' => (int) $row['VALUE'],
                    'name' => $fileArray['ORIGINAL_NAME'],
                    'type' => strtoupper( pathinfo($fileArray['ORIGINAL_NAME'], PATHINFO_EXTENSION)) ?? '',
                    'url' => \CFile::GetPath($row['VALUE']),
                    'size' => number_format((float) (((int) $fileArray['FILE_SIZE'] / 1024) / 1024), 2, '.', '') . ' МБ' ?? '',
                ];
            }
        }

        return $files;
    }

    // метод для получения множественный свойств
    public static function getEnumPropertyId($stockEntityId, $propCode) {
        $values = [];

        $filesObj = \CIBlockElement::GetProperty(
            3,
            (int) $stockEntityId,
            ['ACTIVE' => 'Y'],
            ['CODE' => $propCode]
        );

        while ($row = $filesObj->Fetch()) {
            if ((int) $row['VALUE'] !== 0) {
                $values[] = (int) $row['VALUE'];
            }
        }

        return $values;
    }

    /**
     * Метод для получения товаров акции
     *
     * @return array
     */
    public static function getDetailStocksData() {
        $stocks = [];

        $stocksObjs = \CIBlockElement::GetList(
            false,
            self::getDefaultFilter(),
            false,
            false,
            [
                'ID',
                'NAME',

                // множественные свойства
                // 'PROPERTY_PRODUCTS',
                // 'PROPERTY_SECTIONS'
            ]
        );

        while ($row = $stocksObjs->Fetch()) {
            $productsIds = [];
            $sections = [];

            // id разделов
            $sectionsObj = \CIBlockElement::GetProperty(
                3,
                (int) $row['ID'],
                ['ACTIVE' => 'Y'],
                ['CODE' => 'SECTIONS']
            );

            while ($row2 = $sectionsObj->Fetch()) {
                if ((int) $row2['VALUE'] !== 0) {
                    $sections[] = (int) $row2['VALUE'];
                }
            }

            // получение всех id элементов (товаров) раздела
            $sectionElementsIds = [];
            $sectionElementsIdsObjs = \CIBlockElement::GetList(
                false,
                [
                    'IBLOCK_ID' => 5,
                    'ACTIVE' => 'Y',
                    'SECTION_ID' => $sections,
                    'INCLUDE_SUBSECTIONS' => 'Y'
                ],
                false,
                false,
                [
                    'ID'
                ]
            );
            while ($row4 = $sectionElementsIdsObjs->Fetch()) {
                $sectionElementsIds[] = (int) $row4['ID'];
            }

            // id товаров
            $productsObj = \CIBlockElement::GetProperty(
                3,
                (int) $row['ID'],
                ['ACTIVE' => 'Y'],
                ['CODE' => 'PRODUCTS']
            );

            while ($row3 = $productsObj->Fetch()) {
                if ((int) $row3['VALUE'] !== 0) {
                    $productsIds[] = (int) $row3['VALUE'];
                }
            }

            // все товары, относящиеся к определенной акции
            $allStockProductsIds = $sectionElementsIds;

            foreach ($productsIds as $prodId) {
                if (!in_array($prodId, $allStockProductsIds)) {
                    $allStockProductsIds[] = $prodId;
                }
            }

            $stocks[] = [
                'id' => (int) $row['ID'],
                'code' => $row['CODE'],
                'name' => $row['NAME'],
                //'sections' => $sections,
                //'sectionElementsIds' => $sectionElementsIds,
                //'products' => $productsIds,
                'products_ids' => $allStockProductsIds
            ];
        }

        return $stocks;
    }
}
?>