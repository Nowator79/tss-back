<?
namespace Godra\Api\Catalog;

class Bubble extends Base
{
    protected static $row_data = [
        'section_code' => [
            'mandatory' => false,
            'alias' => 'SECTION_CODE',
            'description' => 'Символьный код раздела'
        ]
    ];

    protected static $api_ib_code = IBLOCK_CATALOG_API;

    protected static $select_rows = [
        [ 'name' => 'ID' , 'alias' => 'id'],
        [ 'name' => 'NAME'],
        [ 'name' => 'PICTURE', 'method' => '\\CFile::getPath'],
        [ 'name' => 'IBLOCK_ID'],
        [ 'name' => 'CODE'],
        [ 'name' => 'URL'],
    ];

    public function getList()
    {
        return $this->getBubble();
    }
}
?>