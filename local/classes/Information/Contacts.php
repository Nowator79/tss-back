<?
namespace Godra\Api\Information;

use Bitrix\Main\Config\Option;

class Contacts
{
    /**
     * Поля из настроек которые следует стянуть
     *
     * @var array
     */
    protected $rows = [
        'phone',
        'address',
        'work_time',
        'email',
        'short_name',
        'full_name',
        'origin_address',
        'ur_address',
        'inn',
        'kpp',
        'ogrn',
        'okpo',
    ];

    /**
     * Наполняет массив значениями из админки (настройки апи)
     *
     * @return void
     */
    public function getList()
    {
        foreach ($this->rows as $val)
            $result[$val] = Option::get('main', 'api_'.$val);

        return $result;
    }
}
?>