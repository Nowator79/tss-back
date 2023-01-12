<?
namespace Godra\Api\Admin;

use \Bitrix\Main\Config\Option,
    \Godra\Api\Helpers\Utility\Misc;

abstract class BaseData
{
    /** Свойства каталога */
    public $catalog_props;

    function __construct()
    {
        $this->catalog_props = Misc::getCatalogProperties();
    }

    /**
     * Настройки для класса Settings (основные настройки АПИ)
     */
    protected function setSettingsData()
    {
        $this->data = [
            'Контакты' => [
                [
                    'title' => 'Номер телефона',
                    'tag' => 'input',
                    'type' => 'text',
                    'name' => 'phone',
                    'value' => Option::get('main', 'api_phone') ?: '',
                ],
                [
                    'title' => 'Адрес',
                    'tag' => 'input',
                    'type' => 'text',
                    'name' => 'address',
                    'value' => Option::get('main', 'api_address') ?: '',
                ],
                [
                    'title' => 'Время работы',
                    'tag' => 'input',
                    'type' => 'text',
                    'name' => 'work_time',
                    'value' => Option::get('main', 'api_work_time') ?: '',
                ],
                [
                    'title' => 'Электронная почта',
                    'tag' => 'input',
                    'type' => 'text',
                    'name' => 'email',
                    'value' => Option::get('main', 'api_email') ?: '',
                ],
            ],
            'Реквизиты' => [
                [
                    'title' => 'Краткое название',
                    'tag' => 'input',
                    'type' => 'text',
                    'name' => 'short_name',
                    'value' => Option::get('main', 'api_short_name') ?: '',
                ],
                [
                    'title' => 'полное название',
                    'tag' => 'input',
                    'type' => 'text',
                    'name' => 'full_name',
                    'value' => Option::get('main', 'api_full_name') ?: '',
                ],
                [
                    'title' => 'фактический адрес',
                    'tag' => 'input',
                    'type' => 'text',
                    'name' => 'origin_address',
                    'value' => Option::get('main', 'api_origin_address') ?: '',
                ],
                [
                    'title' => 'юридический адрес',
                    'tag' => 'input',
                    'type' => 'text',
                    'name' => 'ur_address',
                    'value' => Option::get('main', 'api_ur_address') ?: '',
                ],
                [
                    'title' => 'ИНН',
                    'tag' => 'input',
                    'type' => 'text',
                    'name' => 'inn',
                    'value' => Option::get('main', 'api_inn') ?: '',
                ],
                [
                    'title' => 'КПП',
                    'tag' => 'input',
                    'type' => 'text',
                    'name' => 'kpp',
                    'value' => Option::get('main', 'api_kpp') ?: '',
                ],
                [
                    'title' => 'ОГРН',
                    'tag' => 'input',
                    'type' => 'text',
                    'name' => 'ogrn',
                    'value' => Option::get('main', 'api_ogrn') ?: '',
                ],
                [
                    'title' => 'ОКПО',
                    'tag' => 'input',
                    'type' => 'text',
                    'name' => 'okpo',
                    'value' => Option::get('main', 'api_okpo') ?: '',
                ],
            ],
            'Каталог' => [
                [
                    'title' => 'Свойство с единицами измерения',
                    'tag' => 'select',
                    'type' => '',
                    'required' => true,
                    'name' => 'measures_property_code',
                    'value' => Option::get('main', 'api_measures_property_code') ?: '',
                    'multiselect_values' => $this->catalog_props,
                ]
            ],
            'Фильтр каталога' => [
                [
                    'title' => 'Свойства выводимые в каталог',
                    'tag' => 'select',
                    'tag_data' => 'multiple',
                    'type' => '',
                    'required' => true,
                    'name' => 'filter_props[]',
                    'value' => \unserialize(Option::get('main', 'api_filter_props')) ?: '',
                    'multiselect_values' => $this->catalog_props,
                ]
            ]
        ];
    }

    abstract protected function setData();
    abstract protected function setTabs($bEdit);
    abstract protected function preparePostData();
}
?>