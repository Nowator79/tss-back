<?
namespace Godra\Api\HighloadBlock;

use \Bitrix\Main\Entity,
    \Bitrix\Highloadblock as HL,
    \Godra\Api\Helpers\Utility\Misc;

abstract class Base
{
    protected $row_data = [
        'code',
    ];

    protected $post_data;

    function __construct()
    {
        $this->post_data = Misc::getPostDataFromJson();
        Misc::includeModules(['highloadblock']);

        if($this->post_data['code'])
            $this->data_class = $this->geDataClassByCode(HIGHLOAD_MENU_ID);

        if($this->post_data['url'])
            $this->data_class = $this->geDataClassByCode(HIGHLOAD_BREADCRUMBS_ID);
    }

    /**
     * Получить датакласс хайлоад блока по его table_name
     * Записывает в $this->data_class
     * @return void
     */
    protected function geDataClassByCode($table_name)
    {
        $id = HL\HighloadBlockTable::getList([
            'filter' => ['TABLE_NAME' => $table_name],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch()['ID'];

        $entity = HL\HighloadBlockTable::compileEntity($id);
        return $entity->getDataClass();
    }

    public function getMap()
    {
        return $this->row_data;
    }
}
?>