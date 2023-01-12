<?php
	
namespace Godra\Api\Helpers;

use CFileMan;

class WysiwygEditorUserField
{
    public function GetUserTypeDescription()
    {
        return 
		[
            'CLASS_NAME' => __CLASS__,
            'BASE_TYPE' => 'string',
            'USER_TYPE_ID' => 'editor',
            'DESCRIPTION' => 'Визуальный редактор'
        ];
    }

    public function GetDBColumnType()
    {
        return 'text';
    }

    public function GetEditFormHTML($arUserField, $arHtmlControl)
    {
        ob_start();
        
        CFileMan::AddHTMLEditorFrame($arHtmlControl['NAME'], $arHtmlControl['VALUE'], false, 'html', ['height' => 450, 'width' => '100%']);
        
        $result = ob_get_contents();
        
        ob_end_clean();

        return $result;
    }
}
