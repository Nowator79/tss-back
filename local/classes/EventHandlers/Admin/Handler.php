<?
namespace Godra\Api\EventHandlers\Admin;

class Handler
{
    /**
     * даёт возможность хранить админский файл в /local/
     *
     * @param array $aModuleMenu
     * @return void
     */
    private static function recursive_array(&$aModuleMenu)
    {
        foreach ($aModuleMenu as &$item)
        {
            if($item['url'] and strlen($item['url']))
                $item['url'] = '/bitrix/admin/'.$item['url'];

            if($item['items'])
                self::recursive_array($item['items']);
        }
    }

    public static function addMenuItem(&$aGlobalMenu, &$aModuleMenu)
    {

        global $USER;

        if ($USER->IsAdmin())
        {
            self::recursive_array($aModuleMenu);
            $aGlobalMenu['global_menu_api'] = [
                'menu_id' => 'api',
                    "icon" => "default_menu_icon",
                    "page_icon" => "default_page_icon",
                'text' => 'Настройки Api',
                'title' => 'Настройки Api',
                'url' => 'settings.php?lang=ru',
                'sort' => 0,
                'items_id' => 'global_menu_api',
                'help_section' => 'api',
                'items' => [
                    [
                        'parent_menu' => 'global_menu_api',
                        'sort'        => 10,
                        'url'         => '/local/admin/settings.php?lang=ru',
                        'text'        => 'Настройки',
                        'title'       => 'Настройки',
                        'icon'        => 'fav_menu_icon',
                        'page_icon'   => 'fav_menu_icon',
                        'items_id'    => 'menu_api',
                    ],
                ],
            ];
        }
    }
}
?>