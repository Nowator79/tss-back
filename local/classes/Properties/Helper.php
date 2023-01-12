<?
namespace Godra\Api\Properties;

class Helper extends Base
{

    function __construct()
    {
        parent::__construct();
    }

    /**
     * Меняет всем свойствам показатель вывода в смарт фильтре
     * @var array $ids Массив id Свойств которые учавствуют в фильтрации
     */
    public function updateFilterableProps($ids)
    {
        $this->disableFilterableProps();

        foreach ($ids as $id)
            \CIBlockSectionPropertyLink::Add(0, $id, ['SMART_FILTER' => "Y"]);
    }
}