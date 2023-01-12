<?
namespace Godra\Api;

class Documents
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
            
            $result['isSuperUser'] = $bitrixUserApi->isSuperUser();
            
            if ($userId)
            {
                $contragentCode = (new Helpers\Contragent)->getContragentCodeByUser($userId, $result['isSuperUser']);
                
                if ($contragentCode)
                {
                    $result['documents'] = (new Helpers\Documents)->getList((int)$_GET['page'], $_GET['dates'], $_GET['type'], $contragentCode);
                }
                else
                {
                    $API_ERRORS[] = 'Доступ запрещён';
                    
                    $apiErrorStatusCode = 403;
                }
            }
            else
            {
                $API_ERRORS[] = 'Доступ запрещён';
                
                $apiErrorStatusCode = 403;
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
    
    public function download()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        $id = (int)$_GET['id'];
        
        if ($id)
        {
            if ($bitrixUserApi->isAuth())
            {
                $userId = $bitrixUserApi->getUserId();
                
                $result['isSuperUser'] = $bitrixUserApi->isSuperUser();

                if ($userId)
                {
                    $contragentCode = (new Helpers\Contragent)->getContragentCodeByUser($userId, $result['isSuperUser']);
                    
                    if ($contragentCode)
                    {
                        $resultApi = (new Helpers\Documents)->download($id, $contragentCode);
                        
                        if (!$resultApi)
                        {
                            $API_ERRORS[] = 'Доступ запрещён';
                            
                            $apiErrorStatusCode = 403;
                        }
                    }
                    else
                    {
                        $API_ERRORS[] = 'Доступ запрещён';
                        
                        $apiErrorStatusCode = 403;
                    }
                }
                else
                {
                    $API_ERRORS[] = 'Доступ запрещён';
                    
                    $apiErrorStatusCode = 403;
                }
                
                $status = 1;
            }
            else
            {
                $API_ERRORS[] = 'Необходимо авторизоваться';
                
                $apiErrorStatusCode = 401;
            }
        }
        else
        {
            $API_ERRORS[] = 'Не заполнены обязательные поля';
            
            $apiErrorStatusCode = 422;
        }
        
        $result['status'] = $status;
        
        return $result;
    }
}
