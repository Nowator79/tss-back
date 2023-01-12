<?
namespace Godra\Api\Page;

class Profile
{
    /**
     * личный кабинет, страница "Личные данные"
     */
    public function get()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $page = 1;
        
        if ((int)$_GET['page'])
        {
            $page = (int)$_GET['page'];
        }
        
        $bitrixUserApi = new \Godra\Api\Helpers\Auth\Bitrix();
        
        $notifications = new \Godra\Api\Helpers\Notifications();

        if ($bitrixUserApi->isAuth())
        {
            // принадлежность к суперпользователю
            $result['isSuperUser'] = $bitrixUserApi->isSuperUser();
            
            // данные пользователя для отображения в форме
            $result['userData'] = $bitrixUserApi->getUserData();
            
            // уведомления на первый экран
            $userId = $bitrixUserApi->getUserId();
            
            if ($userId)
            {
                $result['notifications'] = $notifications->getList($page, $userId);
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
