<?
namespace Godra\Api\Properties;

abstract class Base
{
    /** Обьект для работы со свойствами */
    protected $CAllIBlockProperty;

    function __construct()
    {
        $this->catalog_id = \Bitrix\Iblock\IblockTable::getList([
            'select' => ['ID'],
            'filter' => ['CODE' => IBLOCK_CATALOG_API],
            'limit'  => 1
        ])->fetch()['ID'];
    }

    /**
     * Отключить участие в фильтрации у всех свойств
     */
    protected function disableFilterableProps()
    {
        \CIBlockSectionPropertyLink::DeleteByIBlock($this->catalog_id);
    }

    /**
     * Получить все свойства у которых SMART_FILTER = 'Y'
     * @return array
     */
    public function getFilterableProps()
    {
        $props = \Bitrix\Iblock\SectionPropertyTable::getList([
            'filter' => [ 'IBLOCK_ID' => $this->catalog_id, 'SMART_FILTER' => 'Y' ],
            'select' => ['PROPERTY_ID']
        ])->fetchAll();

        return $props ?: [];
    }

    /**
     * Получить названия свойств, принимает массив свойств с PROPERTY_ID
     * Дополняет NAME
     *
     * @param array $props_array массив метода getAllProps
     * @param array $fields Массив полей которые надо получать
     * @param array $ids Массив id свойств
    */
    public static function getPropsFields(&$props_array, $fields, $ids)
    {
        $db_props = \Bitrix\Iblock\PropertyTable::getList([
            'select' => array_merge($fields, ['ID']),
            'filter' => ['ID' => $ids],
        ])->fetchAll();

        foreach ($db_props as $prop)
            $res_props[$prop['ID']] = $prop;

        foreach ($props_array as $key => $prop)
            $props_array[$key] = array_merge($res_props[$prop['IBLOCK_PROPERTY_ID']], $prop);
    }
}