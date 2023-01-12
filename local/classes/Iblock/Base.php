<?
namespace Godra\Api\Iblock;

use \Bitrix\Main\Entity\Query,
    \Bitrix\Catalog\PriceTable,
    \Bitrix\Iblock\SectionTable,
    \Godra\Api\Helpers\Utility\Misc,
    \Bitrix\Catalog\CatalogViewedProductTable,
    \Godra\Api\Iblock\IblockElementPropertyTable;

abstract class Base
{
    protected static $row_data = [];
    protected static $select_rows = [];
    protected static $api_ib_code = false;

    /**
     * Поля товара для выборки
     * * name: имя поля, если нужно алиас => имя
     * * method: метод для обработки поля псле получения
     * @var array
     */
    protected static $select_product_rows = [];

    /**
     * Получить товары по коду раздела
     * @param $data['code'] $section_id_or_code
     * @param $product_id Ид товара, если нужно получить именно товар под капотом апи
     * @return array
     */
    public static function get($product_id = false)
    {
        Misc::includeModules(['iblock', 'catalog', 'sale']);

        // получение данных из post
        $data = Misc::getPostDataFromJson();

        // Проверка входящих данных, отдаст ошибки 3й аргумент
        if(!$product_id)
            Misc::checkRowsV2($data, static::$row_data);

        // получение сущности
        $entity = static::getEntityName(static::$api_ib_code);
        $entity = $entity::getEntity();

        // поля для выборки
        $select = array_column(static::$select_rows, 'name');

        // Реализация новых полей
        self::addSectionCodeForEntity($entity);
        self::addSectionNameForEntity($entity);
        self::addDetailPageUrlForEntity($entity);

        // Добавление доп полей в выборку
        $select[] = 'URL';
        $select[] = 'IBLOCK_ID';
        $select[] = 'SECTION_NAME';
        $select[] = 'SECTION_CODE';

        $section_id  = static::getSectionByFilter(['CODE' => $data['section_code']]);
        $section = SectionTable::getList([
            'filter' => ['CODE' => $data['section_code']],
            'select' => ['ID', 'LEFT_MARGIN', 'DEPTH_LEVEL', 'RIGHT_MARGIN'],
            'limit'  => 1
        ])->fetch();

        $ids = \array_column(
           SectionTable::getList([
                'filter' => [
                    '>LEFT_MARGIN' => $section['LEFT_MARGIN'],
                    '>DEPTH_LEVEL' => $section['DEPTH_LEVEL'],
                    '<RIGHT_MARGIN'=> $section['RIGHT_MARGIN']
                ],
                'select' => ['ID']
            ])->fetchAll(),
            'ID'
        );

        $ids[] = $section['ID'];

        // код раздела
        $filter = $section_id ?
            ['IBLOCK_SECTION_ID' => $ids] : [];

        // код элемента
        $data['element_code'] ?
            $filter['CODE']  = $data['element_code'] : false;

        // id элемента
        $product_id ?
            $filter['ID']  = $product_id : false;

        // код элемента
        $data['code'] ?
            $filter['=CODE'] = $data['code'] : false;

        // выборка
        $query = new Query($entity);

        // добавил для сортировки по цене
        $query->registerRuntimeField('PRICE', [
                'data_type' => PriceTable::getEntity(),
                'reference' => [
                    '=this.ID' => 'ref.PRODUCT_ID',
                ],
                'join_type' => 'LEFT'
            ]
        );

        $filter['ACTIVE'] = 'Y';

        // Пагинация
        $limit  = $data['limit'] ?: 25;
        $offset = $data['page'] * $limit;

        // сортировка
        $order  = [ 'PREVIEW_PICTURE' => 'desc' ];
        $order  = [ 'SHOW_COUNTER' => 'asc' ];
        $order = [ 'SORT' => 'asc' ];
        // $order  = ['ACTIVE_FROM' => 'asc'];

        // Сортировка по order: { name: поле, direction: направление }
        if($data['order']['name'] and $data['order']['direction'])
                $order[\strtoupper($data['order']['name'])] = $data['order']['direction'];

        $collection = $query
            ->setSelect(array_merge($select, ['price']))
            ->setOrder($order)
            ->setLimit($limit)
            ->setOffset($offset)
            ->setFilter($filter)
            ->exec()
            ->fetchCollection();


        // обработка значений
        foreach ($collection as $item)
        {
            foreach($select as $key => $name)
            {
               $field  = $item->get($name);

                $name   = static::$select_rows[$key]['alias'] ?:  \strtolower($name);
                $method = static::$select_rows[$key]['method'];

                // перебор, для случая множественного значения
                $new_item[$name] = \is_object($field) ?
                    ($field->getValue() ?? self::getAllValues($field)):
                    $field;


                /** Обработка переданных методов в поле method , заменяет $val на значение, если нужен просто результат, а не определённое поле результата, то можно передать только метод */
                if($method)
                    $new_item[$name] = self::executeMethod($method, $new_item[$name]);

                $new_item['url'] = \preg_replace('/[\/]+/m', '/', $new_item['url']);
            }

            $result['items'][] = $new_item;
            unset($props);
            unset($new_item);
        }

        // Добавить товар в список просмотренных, если запросили деталку
        if($data['element_code'] AND static::$api_ib_code == IBLOCK_CATALOG_API)
        {
            self::addViewedProduct($result['items'][0]['id']);
            \CIBlockElement::CounterInc($result['items'][0]['id']);
        }

        // получить свойства
        self::getProps($result['items']);

        return count($result['errors']) ? $result['errors'] : $result['items'];
    }

    /**
     * Получить товары по коду раздела ( Получить кол-во )
     * @param $data['code'] $section_id_or_code
     * @return int
     */
    protected static function countElementsBySectionCode()
    {
        Misc::includeModules(['iblock', 'catalog', 'sale']);

        // получение данных из post
        $data = Misc::getPostDataFromJson();

        // Проверка входящих данных, отдаст ошибки 3й аргумент
        Misc::checkRowsV2($data, static::$row_data);

        // получение сущности
        $entity = static::getEntityName(static::$api_ib_code);
        $entity = $entity::getEntity();

        $section_id  = static::getSectionByFilter(['CODE' => $data['section_code']]);
        $section = \Bitrix\Iblock\SectionTable::getList([
            'filter' => ['CODE' => $data['section_code']],
            'select' => ['ID', 'LEFT_MARGIN', 'DEPTH_LEVEL', 'RIGHT_MARGIN'],
            'limit'  => 1
        ])->fetch();

        $ids = \array_column(
            \Bitrix\Iblock\SectionTable::getList([
                'filter' => [
                    '>LEFT_MARGIN' => $section['LEFT_MARGIN'],
                    '>DEPTH_LEVEL' => $section['DEPTH_LEVEL'],
                    '<RIGHT_MARGIN'=> $section['RIGHT_MARGIN']
                ],
                'select' => ['ID']
            ])->fetchAll(),
            'ID'
        );

        $ids[] = $section['ID'];

        // код раздела
        $filter = $section_id ?
            ['IBLOCK_SECTION_ID' => $ids] : [];

        // выборка
        $query = new \Bitrix\Main\Entity\Query($entity);


        $count = $query
            ->setFilter($filter)
            ->countTotal(true)
            ->exec()
            ->getCount();

        return $count;
    }

    /**
     * Добавить товар в список просмотренного
     */
    protected function addViewedProduct($id)
    {
        if($id AND \CSaleBasket::GetBasketUserID())
            CatalogViewedProductTable::refresh($id, \CSaleBasket::GetBasketUserID());
    }

    /**
     * Получить список просмотренных товаров
     * @return array|void
     */
    protected function getViewedProducts()
    {
        $basket_user_id = (int)\CSaleBasket::GetBasketUserID(false);

        if ($basket_user_id)
        {
            $viewedIterator = CatalogViewedProductTable::getList([
                'select' => ['PRODUCT_ID', 'ELEMENT_ID'],
                'filter' => ['=FUSER_ID' => $basket_user_id, '=SITE_ID' => SITE_ID],
                'order'  => ['DATE_VISIT' => 'DESC'],
                'limit'  => 10
            ]);

            while ($arFields = $viewedIterator->fetch())
                $arViewed[] = $arFields['ELEMENT_ID'];

            return $arViewed;
        }
    }

    /**
     * Получить свойства для списка эелементов полученого
     *  методом get текущего класса
     * @param array $items
     */
    protected function getProps(&$items)
    {
        // Тут хранятся наименования и типы свойств, нужно для кеша запросов
        $props_names = [];

        foreach ($items as &$item)
        {
            $collection_props = self::getPropertiesValuesByElementId($item['id']);

            foreach ($collection_props as $value)
            {
                // сокращаю кол-во запросов в бд путём кеширования знакомых имён ,
                // значительно даёт прировс в скорости
                self::propertyNamesCache($props_names, $value['IBLOCK_PROPERTY_ID']);
                self::preparePropertyValue($value['VALUE'], $props_names[$value['IBLOCK_PROPERTY_ID']]['TYPE']);

                $item['props'][$props_names[$value['IBLOCK_PROPERTY_ID']]['NAME']][] = [
                    'value' => $value['VALUE'],
                    'description' => $value['DESCRIPTION'],
                    'type' => $props_names[$value['IBLOCK_PROPERTY_ID']]['TYPE'],
                ];

            }

            unset($collection_props);
        }
    }

    /**
     * Получить массив значений свойств для элемента по его id
     * @param int $id
     * @return array
     */
    protected function getPropertiesValuesByElementId($id)
    {
        return IblockElementPropertyTable::getList([
            'filter' => ['IBLOCK_ELEMENT_ID' => $id],
        ])->fetchAll();
    }

    /**
     * Обработка значения свойства по ео типу
     * @param int|string $value значение свойства
     * @param string $type тип свойства ( S , F .. )
     */
    protected function preparePropertyValue(&$value, $type)
    {
        if($type == 'F')
            $value = \CFile::GetPath($value);
    }

    /**
     * Заполняет массив кеша свойств
     * @param array $props_names
     * @param int $prop_id
     */
    protected function propertyNamesCache(&$props_names, $prop_id)
    {
        if(!$props_names[$prop_id])
        {
            $this_prop = \Bitrix\Iblock\PropertyTable::getList([
                'filter' => ['ID' => $prop_id],
                'select' => ['NAME', 'PROPERTY_TYPE'],
                'limit'  => 1
            ])->fetch();

            $props_names[$prop_id] = [
                'NAME' =>  $this_prop['NAME'],
                'TYPE' =>  $this_prop['PROPERTY_TYPE']
            ];
        }
    }

    /**
     * Добавляет сущности новое поля URL
     * @param [type] $entity
     */
    protected function addDetailPageUrlForEntity(&$entity)
    {
        // на прозапас сделал вложенность
        $entity->addField(
            new \Bitrix\Main\Entity\ExpressionField(
                'URL',
                '
                CONCAT(
                    "/", COALESCE(%s,""), "/",
                    COALESCE(%s,""), "/",
                    COALESCE(%s,""), "/",
                    COALESCE(%s,""), "/",
                    COALESCE(%s,""), "/",
                    COALESCE(%s,""), "/",
                    COALESCE(%s,""), "/"
                )',
                [
                    'IBLOCK.CODE',
                    'IBLOCK_SECTION.PARENT_SECTION.PARENT_SECTION.PARENT_SECTION.PARENT_SECTION.CODE',
                    'IBLOCK_SECTION.PARENT_SECTION.PARENT_SECTION.PARENT_SECTION.CODE',
                    'IBLOCK_SECTION.PARENT_SECTION.PARENT_SECTION.CODE',
                    'IBLOCK_SECTION.PARENT_SECTION.CODE',
                    'IBLOCK_SECTION.CODE',
                    'CODE',
                ]
            )
          );
    }

    /**
     * Получить кол-во элементов раздела
     * @return int|void
     */
    protected function GetCountChildrenByIblockSection($section_id = 0, $iblock_id = false)
    {
        $iblock_id ? $filter['IBLOCK_ID'] = $iblock_id : false;
        $section_id ? $filter['SECTION_ID'] = $section_id : false;
        $filter['ACTIVE'] = 'Y';
        $filter['INCLUDE_SUBSECTIONS'] = 'Y';

        return \CIBlockElement::GetList(
            [],
            $filter,
            false,
            false,
            ['ID'],
        )->SelectedRowsCount();
    }

    protected function addSectionNameForEntity(&$entity)
    {
        $entity->addField(
            new \Bitrix\Main\Entity\ExpressionField(
                'SECTION_NAME',
                '
                CONCAT(
                    COALESCE(%s,"")
                )',
                [
                    'IBLOCK_SECTION.NAME',
                ]
            )
          );
    }

    protected function addSectionCodeForEntity(&$entity)
    {
        $entity->addField(
            new \Bitrix\Main\Entity\ExpressionField(
                'SECTION_CODE',
                '
                CONCAT(
                    COALESCE(%s,"")
                )',
                [
                    'IBLOCK_SECTION.CODE',
                ]
            )
          );
    }







    protected static function getAllValues($field)
    {
        foreach ($field as $val)
            $res[] = $val->getValue();

        return $res;
    }

    /**
     * Исполнят метод переданный в виде строки с меткой для данных $val
     * * Пример:
     * * *     $method = "str_replace('world', '', $val)";
     * * *     $value = 'Hello world'
     * *    вернёт 'Hello';
     * @param string $method
     * @param [type] $value
     */
    protected static function executeMethod($method, $value)
    {
            if(\is_array($value))
            {
                foreach ($value as $val)
                    strpos($method, '$val')?
                        eval('$arr[] = '.\str_replace('$val', $val , $method ).';'):
                        $arr[] = $method($val);
            }
            else
                strpos($method, '$val')?
                    eval('$arr = '.\str_replace('$val', $value , $method ).';'):
                    $arr = $method($value);

        return $arr;
    }

    /**
     * Простой конструктор entity name по Апи коду инфоблока
     * @param string $iblock_api_code Апи код инфоблока
     * @return string
     */
    public static function getEntityName($iblock_api_code)
    {
        return '\\Bitrix\\Iblock\\Elements\\Element'.ucfirst($iblock_api_code).'Table';
    }


    /**
     * Получить id раздела по фильтру D7
     * @param array $filter
     * @return array|void
     */
    protected static function getSectionByFilter($filter)
    {
        Misc::includeModules(['iblock']);

        return SectionTable::getList([
            'filter' => $filter,
            'select' => ['ID'],
            'limit'  => []
        ])->fetch()['ID'] ?: false;
    }

    /**
     * Получить инфоблок по символьному коду
     * @param string $code
     * @return array|void
     */
    public static function getIblockByCode($code)
    {
        return \Bitrix\Iblock\IblockTable::getList([
            'filter' => ['CODE' => $code],
            'limit' => 1,
            'select' => ['ID', 'NAME', 'LIST_PAGE_URL']
        ])->fetch();
    }
}
?>