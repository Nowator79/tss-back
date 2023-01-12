<?
namespace Godra\Api\Services;

use \Godra\Api\Services\Form,
    \Godra\Api\Helpers\Utility\Misc;

class PrepareFormStatic
{
    protected static $data = [];

    public static function addResult()
    {
        self::$data = Misc::getPostDataFromJson();

        $form = new Form(CALLBACK_FORM_SID, self::$data);
        $form->addResult();
    }
}