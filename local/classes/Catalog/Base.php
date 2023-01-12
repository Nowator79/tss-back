<?
namespace Godra\Api\Catalog;

use Godra\Api\Helpers\Utility\Misc;

/**
 * Базовый абстрактный класс Каталога
 * @param int $id id Каталога
 */
abstract class Base extends \Godra\Api\Iblock\Base
{
    /**
     * Поля товара для выборки
     * name: имя поля, если нужно алиас => имя
     * method: метод для обработки поля псле получения
     * @var array
     */
    protected static $row_data    = [];
    protected static $select_rows = [];
    protected static $api_ib_code = false;
    protected $post_data;

    function __construct()
    {
        Misc::includeModules(['iblock', 'catalog', 'sale', 'currency']);
        $this->post_data = Misc::getPostDataFromJson();
    }

    protected function getBubble()
    {

        // получение сущности
        $entity = \Bitrix\Iblock\SectionTable::getEntity();
        $select = array_column(static::$select_rows, 'name');

        // получит id раздела
        $section_code = $this->post_data['section_code'];
        $section_id  = static::getSectionByFilter(['CODE' => $section_code]);

        // добавить поле URL для раздела
        $this->addSectionNameForEntity($entity);

        // код раздела родителя
        $filter = $section_id ?
            ['IBLOCK_SECTION_ID' => $section_id] : [];

        // выборка
        $query = new \Bitrix\Main\Entity\Query($entity);

        $collection = $query
        ->setSelect($select)
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
                    (
                        method_exists($field, 'getValue') ?
                            $field->getValue():
                            self::getAllValues($field)
                    ):
                    $field;

                /** Обработка переданных методов в поле method , заменяет $val на значение, если нужен просто результат, а не определённое поле результата, то можно передать только метод */
                if($method)
                    $new_item[$name] = self::executeMethod($method, $new_item[$name]);

                // получить кол-во элементов раздела
                $new_item['elements_count'] = self::GetCountChildrenByIblockSection($new_item['id'], $new_item['iblock_id']);
                $new_item['url'] = \preg_replace('/[\/]+/m', '/', $new_item['url']);
            }

            $result['items'][] = $new_item;
        }

        return count($result['errors']) ? $result['errors'] : $result['items'];
    }

    /**
     * URL поле для раздела, вложенность 5
     * @param [type] $entity
     */
    protected function addSectionNameForEntity(&$entity)
    {
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
                    'PARENT_SECTION.PARENT_SECTION.PARENT_SECTION.PARENT_SECTION.PARENT_SECTION.CODE',
                    'PARENT_SECTION.PARENT_SECTION.PARENT_SECTION.PARENT_SECTION.CODE',
                    'PARENT_SECTION.PARENT_SECTION.PARENT_SECTION.CODE',
                    'PARENT_SECTION.PARENT_SECTION.CODE',
                    'PARENT_SECTION.CODE',
                    'CODE',
                ]
            )
          );
    }

    /**
     * Получение остатков товара на складах
     * @return void
     */
    protected function getAmountById($product_id)
    {
        \Bitrix\Main\Loader::IncludeModule('catalog');

        $amount = \Bitrix\Catalog\StoreProductTable::getList([
            'filter' => ['PRODUCT_ID' => $product_id, 'STORE.ACTIVE'=>'Y']
        ])->fetch();

        return $amount['AMOUNT'];
    }

    public function getMap()
    {
        return static::$row_data;
    }

    /**
     * Получить цену товара
     * @return int
     */
    protected function getPrice($product_id, $pack_count = false)
    {
        # Возможно нужен будет GetOptimalPrice для цены со скидкой
        $rs_price = \Bitrix\Catalog\PriceTable::getList([
            'filter' => [
                'PRODUCT_ID' => $product_id,
            ]
        ])->fetch();

        // цена упаковки
        if($pack_count)
            $price['formating_pack_price'] = \CCurrencyLang::CurrencyFormat($rs_price['PRICE']*$pack_count, $rs_price['CURRENCY']);

        $price['formating_one_price'] = \CCurrencyLang::CurrencyFormat($rs_price['PRICE'], $rs_price['CURRENCY']);

        return $price;
    }
}
?>