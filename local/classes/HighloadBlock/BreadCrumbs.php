<?
namespace Godra\Api\HighloadBlock;

use \Godra\Api\Iblock,
    \Bitrix\Iblock\SectionTable,
    \Godra\Api\Helpers\Utility\Misc;

class BreadCrumbs extends Base
{
    protected $row_data = [
        'url' => 'урл'
    ];

    public function get()
    {
        $this->getСrumbsByUrl($this->post_data['url'], $res);

        if(!$res)
        {
            $iblock = Iblock\Base::getIblockByCode(
                array_values(Misc::clearArrayOfEmptyValues(explode('/', $this->post_data['url'])))[0]
            );

            $this->sections_tree = $this->getSectionsTree($iblock['ID']);
            $this->getСrumbsByIblockId($this->post_data['url'], $iblock, $res);
        }

        return $res;
    }

    protected function getСrumbsByUrl($url, &$res)
    {
        if(count(Misc::clearArrayOfEmptyValues(explode('/', $url)))>1)
            $this->getСrumbsByUrl(\preg_replace('/[0-9\w\-_.!~\`%*]+\/?$/m', '', $url), $res);

        // получение элемента
        $item = $this->data_class::getList([
            'select' => ['*'],
            'filter' => [ 'UF_URL' => $url ],
            'limit'  => 1
        ])->fetch();

        if($item)
            $res[] = [ 'name' => $item['UF_TITLE'], 'url' => $item['UF_URL'][0] ];
    }

    protected function getСrumbsByIblockId($url, $iblock, &$res)
    {
        if(count(Misc::clearArrayOfEmptyValues(explode('/', $url)))>1)
        {
            // Тут ещё возможно нужен будет нейм деталки элемента, надо узнать.

            $this->getСrumbsByIblockId(\preg_replace('/[0-9\w\-_.!~\`%*]+\/?$/m'    , '', $url), $iblock, $res);
            $res[] = [ 'name' => $this->sections_tree[$url], 'url' => $url ];
        }
        else
        {
            $res[] = [ 'name' => $iblock['NAME'], 'url' => $url ];
        }

    }

    protected function getSectionsTree($iblock_id)
    {
        $filter = [
            'ACTIVE' => 'Y',
            'IBLOCK_ID' => $iblock_id,
            'GLOBAL_ACTIVE'=>'Y',
        ];

        $order  = [ 'DEPTH_LEVEL' => 'ASC','SORT' => 'ASC'];
        $select = ['IBLOCK_ID', 'ID','NAME', 'CODE','IBLOCK_SECTION_ID', 'SECTION_PAGE_URL_RAW' => 'IBLOCK.SECTION_PAGE_URL'];

        $rs_sections = SectionTable::GetList([
            'order'  => $order,
            'select' => $select,
            'filter' => $filter,
        ])->fetchAll();


        foreach ($rs_sections as $section)
        {
            $link = \CIBlock::ReplaceDetailUrl($section['SECTION_PAGE_URL_RAW'], $section, true, 'S');

            $links[$link] = $section['NAME'];
        }


        return $links;
    }
}
?>