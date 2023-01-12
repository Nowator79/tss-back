<?
namespace Godra\Api;

class Contragent
{
    public function get()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        $contragent = new Helpers\Contragent();
        
        if ($bitrixUserApi->isAuth())
        {
            $result['isSuperUser'] = $bitrixUserApi->isSuperUser();
            
            $userId = $bitrixUserApi->getUserId();
            
            if ($userId)
            {
                $result['contragent'] = $contragent->getContragentByUserId($userId);
            }
            
            $status = 1;
        }
        else
        {
            $API_ERRORS[] = 'Необходимо авторизоваться';
            
            $apiErrorStatusCode = 401;
        }
        
        $result['status'] = $status;
        
        return $result;
    }
}