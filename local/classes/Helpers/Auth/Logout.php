<?
namespace Godra\Api\Helpers\Auth;

use \Bitrix\Main\Context;

class Logout extends Base
{
    public function logoutSelfUser()
    {
        $this->cuser->Logout();
        Context::getCurrent()->getResponse()->writeHeaders();
    }
}