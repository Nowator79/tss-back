<?
namespace Godra\Api;

class Notification
{
    public function list()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        if ($bitrixUserApi->isAuth())
        {
            $userId = $bitrixUserApi->getUserId();
            
            if ($userId)
            {
                $result['notifications'] = (new Helpers\Notifications)->getList((int)$_GET['page'], $userId);
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
    
    public function markAsRead()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        if ($bitrixUserApi->isAuth())
        {
            // state должен меняться от POST-запросов, не от GET
            if ($_SERVER['REQUEST_METHOD'] === 'POST')
            {
                $userId = $bitrixUserApi->getUserId();
            
                if ($userId)
                {
                    (new Helpers\Notifications)->markAsRead($userId, Helpers\Utility\Misc::getPostDataFromJson()['id']);
                }
                
                $status = 1;
            }
            else
            {
                $API_ERRORS[] = 'Неподдерживаемый метод запроса';
                
                $apiErrorStatusCode = 405;
            }
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
