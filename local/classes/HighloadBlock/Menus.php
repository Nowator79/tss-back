<?
namespace Godra\Api\HighloadBlock;

use Godra\Api\Iblock,
    \Bitrix\Iblock\SectionTable,
    CIBlockSection,
    CFile;

class Menus extends Base
{
    protected $row_data = [
        'code' => 'UF_CLASS нужного меню, можно посмотреть в highload блоке MENUS',
    ];

    public function get()
    {
        $headers = apache_request_headers();
        $availableProductsXmlId =  \Godra\Api\Catalog\Element::getAvailableProductsId($headers);

        // получаю элементы
        $res = $this->data_class::getList([
            'select' => ['*'],
            'filter' => [ 'UF_CLASS' => $this->post_data['code'] ]
        ])->fetchAll();

        // перевожу названия
        foreach ($res as &$item)
            foreach ($item as $row_name => $row)
            {
                $item[str_replace('UF_', '', $row_name)] = $row;
                unset($item[$row_name]);
            }

        // получаю структуру инфоблока
        foreach ($res as &$item)
            if((bool) $item['IS_IBLOCK'])
                $item['items'] = $this->getIblockMenu(trim($item['URL'], '/'));
                
        if ($availableProductsXmlId) 
		{
            $availableSectionsId = $this->getAvailableSectionsId($availableProductsXmlId, $this->getSectionsId($res[0]['items']));
			
			$res = $this->filterMenu($res, $availableSectionsId);
        }
		
		//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/local/log.txt', print_r($res, 1)."\r\n", FILE_APPEND);

        return $res;
    }

    protected function getIblockMenu($api_code)
    {
        $iblock_id = \Bitrix\Iblock\IblockTable::getList([
            'filter' => ['API_CODE' => $api_code],
            'limit'  => 1,
            'select' => ['ID']
        ])->fetch()['ID'];

        $three = $this->getSectionsTree($iblock_id);

        return $three;
    }

    protected function getSectionsTree($iblock_id)
    {
        $filter = [
            'ACTIVE' => 'Y',
            'IBLOCK_ID' => $iblock_id,
            'GLOBAL_ACTIVE'=>'Y',
        ];
        $order  = [ 'DEPTH_LEVEL' => 'ASC','SORT' => 'ASC'];
        $select = ['IBLOCK_ID', 'ID','NAME', 'CODE','IBLOCK_SECTION_ID', 'URL' => 'IBLOCK.SECTION_PAGE_URL', 'UF_MENU_ICON', 'UF_MENU_ICON_HOVER', 'SECTION_PAGE_URL'];

        /*
        $rs_sections = SectionTable::GetList([
            'order'  => $order,
            'select' => $select,
            'filter' => $filter,
        ])->fetchAll();    
        */
        
        $section_link = [];
        $result = [];
        $section_link[0] = &$result;
        
        //
        $res = CIBlockSection::GetList($order, $filter, false, $select);
        
        while ($section = $res->GetNext())
        {
            $section_link[intval($section['IBLOCK_SECTION_ID'])]['items'][$section['ID']] = [
                'name'       => $section['NAME'],
                //'url'        => \CIBlock::ReplaceDetailUrl($section['URL'], $section, true, 'S'),
                'url'        => $section['SECTION_PAGE_URL'],
                'icon'       => ($section['UF_MENU_ICON'] ? CFile::GetPath($section['UF_MENU_ICON']) : ''),
                'icon_hover' => ($section['UF_MENU_ICON_HOVER'] ? CFile::GetPath($section['UF_MENU_ICON_HOVER']) : ''),
            ];
            $section_link[$section['ID']] = &$section_link[intval($section['IBLOCK_SECTION_ID'])]['items'][$section['ID']];
        }
        //

        /*
        foreach($rs_sections as $section)
        {
            $section_link[intval($section['IBLOCK_SECTION_ID'])]['items'][$section['ID']] = [
                'name'       => $section['NAME'],
                'url'        => \CIBlock::ReplaceDetailUrl($section['URL'], $section, true, 'S'),
                'icon'       => $section['UF_MENU_ICON'],
                'icon_hover' => $section['UF_MENU_ICON_HOVER'],
            ];
            $section_link[$section['ID']] = &$section_link[intval($section['IBLOCK_SECTION_ID'])]['items'][$section['ID']];
        }
        */

        unset($section_link);

        return $result['items'];
    }

    /**
     * метод для получения id разделов
     *
     * @param $menuItems
     * @return array
     */
    public function getSectionsId($menuItems) {
        $sectionsId = [];

        foreach ($menuItems as $kFirst => $iFirst) {
            $sectionsId[] = $kFirst;

            if (isset($iFirst['items'])) {
                foreach ($iFirst['items'] as $kSecond => $iSecond) {
                    $sectionsId[] = $kSecond;
                }
            }
        }

        return $sectionsId;
    }

    /**
     * Метод для получения только тех разделов, товары которых есть в ассортименте
     *
     * @param $availableProductsXmlId
     * @param $sectionsIdRaw
     * @return int[]|string[]
     */
    public function getAvailableSectionsId($availableProductsXmlId, $sectionsIdRaw) {
        $sectionsId = [];

        $objs = \CIBlockElement::GetList(
            false,
            [
                'IBLOCK_ID' => 5,
                'ACTIVE' => 'Y',
                [
                    'LOGIC' => 'AND',
                    [
                        'SECTION_ID' => $sectionsIdRaw,
                        'INCLUDE_SUBSECTIONS' => 'Y'
                    ],
                    [ 'XML_ID' => $availableProductsXmlId ]
                ]
            ],
            false,
            false,
            [
                'ID',
                'SECTION_ID',
                'NAME',
                'IBLOCK_SECTION_ID'
            ]
        );

        while ($row = $objs->Fetch()) {
            $sectionsId[ (int) $row['IBLOCK_SECTION_ID']][] = $row;
        }

        $sectionsId = array_keys($sectionsId);
        sort($sectionsId);
        return $sectionsId;
    }

    /**
     * метод для фильтрации разделов меню
     *
     * @param $res
     * @param $availableSectionItems
     * @return mixed
     */
    public function filterMenu($res, $availableSectionItems) {

        // удаление
        foreach ($res[0]['items'] as $kFirst => $iFirst) {
            if (isset($iFirst['items'])) {
                foreach ($iFirst['items'] as $kSecond => $iSecond) {
                    if (isset($iSecond['items'])) {
                        foreach ($iSecond['items'] as $kThird => $itemThird) {
                            if (!in_array($kThird, $availableSectionItems)) {
                                unset($res[0]['items'][$kFirst]['items'][$kSecond]['items'][$kThird]);
                            }
                        }
                    } else {
                        if (!in_array($kSecond, $availableSectionItems)) {
                            unset($res[0]['items'][$kFirst]['items'][$kSecond]);
                        }
                    }
                }
            } else {
                if (!in_array((int) $kFirst, $availableSectionItems)) {
                    unset($res[0]['items'][$kFirst]);
                }
            }
        }

        // очистка от пустых разделов
        foreach ($res[0]['items'] as $kFirst => $iFirst) {
            if (isset($iFirst['items']) && !empty($iFirst['items'])) {
                foreach ($iFirst['items'] as $kSecond => $iSecond) {
                    if (isset($iSecond['items']) && empty($iSecond['items'])) {
                        unset($res[0]['items'][$kFirst]['items'][$kSecond]);
                    } elseif (isset($iSecond['items']) && !empty($iSecond['items'])) {
                        foreach ($iSecond['items'] as $kThird => $iThird) {
                            if (isset($iThird['items']) && empty($iThird['items'])) {
                                unset($res[0]['items'][$kFirst]['items'][$kSecond]['items'][$kThird]);
                            }
                        }
                    }
                }
            } elseif (isset($iFirst['items']) && empty($iFirst['items'])) {
                unset($res[0]['items'][$kFirst]);
            }
        }

        foreach ($res[0]['items'] as $kFirst => $iFirst) {
            if (isset($iFirst['items']) && empty($iFirst['items'])) {
                unset($res[0]['items'][$kFirst]);
            }
        }

        return $res;
    }
}
?>