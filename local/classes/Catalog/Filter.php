<?
namespace Godra\Api\Catalog;

use \Bitrix\Main\Loader,
    \Bitrix\Iblock\IblockTable,
    \Bitrix\Iblock\ElementTable,
    \Bitrix\Iblock\SectionTable,
    \Godra\Api\Properties;
use Godra\Api\Helpers\Utility\Misc;

class Filter extends Base
{
    protected static $row_data = [
        'section_code' => [
            'mandatory' => false,
            'alias' => 'CODE',
            'description' => 'Символьный код раздела'
        ],
    ];

    protected $post_data;
    protected $catalog_id;
    protected $filter_data;
    protected $filterable_props_ids;

    function __construct()
    {
        parent::__construct();

        $this->catalog_id = $this->getCatalogId();
        $this->filterable_props_ids = array_column((new Properties\Helper)->getFilterableProps(), 'PROPERTY_ID');
        $this->filter_data = $this->getPropsValues();
    }

    /**
     * Получить данные по фильтру
     * @return array|void|bool
     */
    public function getData()
    {
        return $this->filter_data;
    }

    /**
     * Получить id Каталога
     * @return int
     */
    protected function getCatalogId()
    {
        return IblockTable::getList([
            'select' => ['ID'],
            'filter' => ['CODE' => IBLOCK_CATALOG_API],
            'limit'  => 1
        ])->fetch()['ID'];
    }
    public function getFilterProperty()
    {
        $params = Misc::getPostDataFromJson();

        $IBLOCK_ID = 5;
        $SECTION_ID = $this->SECTION_ID;
        $mas_prop = [];
        $mas_type_prop = ['N'=>'DIAPASON','S'=>'BUTTON','L'=>'LIST'];

        if($params['code']){
            $res = \CIBlockSection::GetList(array(), array('IBLOCK_ID' => $IBLOCK_ID, 'CODE' => $params['code']));
            if($section = $res->Fetch())$SECTION_ID=$section["ID"];
        }

        foreach (\CIBlockSectionPropertyLink::GetArray($IBLOCK_ID, $SECTION_ID) as $PID => $arLink) {
            if ($arLink["SMART_FILTER"] !== "Y") {
                continue;
            }
            $rsProperty = \CIBlockProperty::GetByID($PID);
            $arProperty = $rsProperty->Fetch();
            if ($arProperty) {
                $mas_prop[$arProperty['CODE']]['NAME'] = $arProperty['NAME'];
                $mas_prop[$arProperty['CODE']]['CODE'] = $arProperty['CODE'];
                $mas_prop[$arProperty['CODE']]['PROPERTY_TYPE'] = $mas_type_prop[$arProperty['PROPERTY_TYPE']];
            }
        }

        $arSelect = Array("ID");
        foreach (array_keys($mas_prop) as $value){
            $arSelect[] = 'PROPERTY_'.$value;
        }
        $arFilter = Array("IBLOCK_ID"=>$IBLOCK_ID, "ACTIVE"=>"Y");
        if($params['code']){
            $arFilter["SECTION_ID"]= $SECTION_ID;
        }

        $res = \CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelect);
        while($ob = $res->Fetch()){
            foreach ($ob as $key => $value){
                $test_mas = $ob;
                $key =  mb_substr($key,9,-6);
                if($value&& in_array($key, array_keys($mas_prop))){
                        if($mas_prop[$key]['PROPERTY_TYPE']=='DIAPASON'){
                            if(!$mas_prop[$key]['VALUE_MIN']||$value<$mas_prop[$key]['VALUE_MIN'])$mas_prop[$key]['VALUE_MIN'] = $value;
                            if(!$mas_prop[$key]['VALUE_MIN']||$value>$mas_prop[$key]['VALUE_MAX'])$mas_prop[$key]['VALUE_MAX'] = $value;
                        }else{
                            if(!in_array($value, $mas_prop[$key]['VALUE'])) {
                                $mas_prop[$key]['VALUE'][] = $value;
                            }
                        }
                }
            }
        }

        foreach ($mas_prop as $key => $value){
            if(!$value['VALUE']&&!$value['VALUE_MIN']){
                unset($mas_prop[$key]);
            }else{

                if($value['VALUE']){
                    sort($value['VALUE']);
                    $mas_prop[$key]['VALUE']= $value['VALUE'];
                }
            }

        }

        //return '<pre>'.Print_r($mas_prop).'</pre>';
        return $mas_prop;
    }
    protected function getAllProps($prop_ids, $elem_ids)
    {
        $res = \Godra\Api\Iblock\IblockElementPropertyTable::getList([
            'filter' => [
                'IBLOCK_PROPERTY_ID' => $prop_ids,
                'IBLOCK_ELEMENT_ID' => $elem_ids,
                '!VALUE' => false,
            ],
            'select' => [
                'VALUE_TYPE',
                'IBLOCK_PROPERTY_ID',
                # id товаров в которых заполнено свойство
                new \Bitrix\Main\Entity\ExpressionField(
                    'ELEMENTS_IDS',
                    "GROUP_CONCAT(DISTINCT %s SEPARATOR '|')",
                    ['IBLOCK_ELEMENT_ID']
                ),
                # значения свойства в пределах выбранного раздела и глубже
                new \Bitrix\Main\Entity\ExpressionField(
                    'VALUES',
                    "GROUP_CONCAT(DISTINCT %s SEPARATOR '|')",
                    ['VALUE']
                ),
            ],
            'group' => ['IBLOCK_PROPERTY_ID'],
            'data_doubling' => false,
            'cache' => [
                'ttl' => 60,
                'cache_joins' => false
            ],
        ])->fetchAll();

        /** Получаю имена свойств */
        Properties\Helper::getPropsFields(
            $res, ['NAME', 'ID'],
            \array_column($res, 'IBLOCK_PROPERTY_ID')
        );

        /** Привожк значения к массиву значений */
        foreach ($res as $value)
        {
            $result[$value['NAME']] = $value;
            $result[$value['NAME']]['VALUES'] = explode('|', $value['VALUES']);
            $result[$value['NAME']]['ELEMENTS_IDS'] = explode('|', $value['ELEMENTS_IDS']);
        }

        return $result;
    }




    /**
     * Получить товары по коду раздела
     * @param $data['code'] $section_id_or_code
     * @param $product_id Ид товара, если нужно получить именно товар под капотом апи
     * @return array
     */
    public function getPropsValues()
    {

        $section_id = SectionTable::getList([
            'filter' => ['CODE' => $this->post_data['section_code']],
            'select' => ['ID'],
            'limit'  => []
        ])->fetch()['ID'];

        if(strlen($this->post_data['section_code']) AND !$section_id)
        {
            Misc::setHeaders('500');
            return;
        }

        $section = SectionTable::getList([
            'filter' => ['ID' => $section_id, 'IBLOCK_ID' => $this->catalog_id],
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
        $filter = $ids[0]?
            ['IBLOCK_SECTION_ID' => $ids, 'IBLOCK_ID' => $this->catalog_id]:
            ['IBLOCK_ID' => $this->catalog_id];

        $element_ids = \array_column(ElementTable::getList([
            'filter' => $filter,
            'select' => ['ID']
        ])->fetchAll(), 'ID');


        return $this->getAllProps($this->filterable_props_ids ,$element_ids);
    }
}