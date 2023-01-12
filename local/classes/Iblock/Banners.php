<?
namespace Godra\Api\Iblock;

class Banners extends Base
{
    protected static $row_data = [
        'code' => [
            'mandatory' => false,
            'alias' => 'CODE',
            'description' => 'Символьный код баннера'
        ]
    ];

    protected static $select_rows = [
        [ 'name' => 'ID' ],
        [ 'name' => 'all_width_picture', 'method' => '\\CFile::GetPath'],
        [ 'name' => 'button_content', 'alias' => 'button_url'],
        [ 'name' => 'NAME'],
        [ 'name' => 'PREVIEW_TEXT'],
        [ 'name' => 'SORT'],
        [ 'name' => 'CODE', 'alias' => 'element_code'],
        
        ['name' => 'HIDE_HEADER', 'alias' => 'hide_header'],
        ['name' => 'COLOR_HEADER', 'alias' => 'color_header'],
        
        ['name' => 'BUTTON_CAPTION', 'alias' => 'button_caption'],
        ['name' => 'BUTTON_LINK', 'alias' => 'button_link'],
    ];

    protected static $api_ib_code = IBLOCK_BANNERS_API;

    public static function getList()
    {
        $result = self::get();
        
        if ($result)
        {
            foreach ($result as $key => $resultItem)
            {
                if (!$resultItem['hide_header'])
                {
                    $result[$key]['hide_header'] = false;
                }
                else
                {
                    $result[$key]['hide_header'] = true;
                }
                
                if ($resultItem['color_header'] == 4618)
                {
                    $result[$key]['color_header'] = 'white';
                }
                
                if ($resultItem['color_header'] == 4619)
                {
                    $result[$key]['color_header'] = 'black';
                }
                
                if (!$resultItem['color_header'])
                {
                    $result[$key]['color_header'] = '';
                }
            }
        }
        
        return $result;
    }
}
?>