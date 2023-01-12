<?
namespace Godra\Api;

class UserManagement
{
    public function list()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        $result['isSuperUser'] = $bitrixUserApi->isSuperUser();
        
        if ($bitrixUserApi->isAuth())
        {
            if ($bitrixUserApi->isSuperUser())
            {
                $userId = $bitrixUserApi->getUserId();
                
                if ($userId)
                {
                    $result['users'] = (new Helpers\UserManagement)->getList($userId, (int)$_GET['page']);
                    
                    $status = 1;
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
            $API_ERRORS[] = 'Необходимо авторизоваться';
            
            $apiErrorStatusCode = 401;
        }
        
        $result['status'] = $status;
        
        return $result;
    }
    
    public function switch()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        if ($bitrixUserApi->isAuth())
        {
            if ($bitrixUserApi->isSuperUser())
            {
                $userId = $bitrixUserApi->getUserId();
                
                $inputData = Helpers\Utility\Misc::getPostDataFromJson();
            
                if ($userId && $inputData['id'] && $inputData['type'])
                {
                    $userManagement = new Helpers\UserManagement();
                    
                    // проверим, принадлежит ли пользователь нашему суперпользователю
                    if ($userManagement->userInSuperUserAccount($userId, $inputData['id']))
                    {
                        $userManagement->switch($inputData['id'], $inputData['type']);
                        
                        $status = 1;
                    }
                    else
                    {
                        $API_ERRORS[] = 'Доступ запрещён';
                        
                        $apiErrorStatusCode = 403;
                    }
                }
                else
                {
                    $API_ERRORS[] = 'Не заполнены обязательные поля';
                    
                    $apiErrorStatusCode = 422;
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
            $API_ERRORS[] = 'Необходимо авторизоваться';
            
            $apiErrorStatusCode = 401;
        }
        
        $result['status'] = $status;
        
        return $result;
    }
    
    public function update()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        if ($bitrixUserApi->isAuth())
        {
            if ($bitrixUserApi->isSuperUser())
            {
                $userId = $bitrixUserApi->getUserId();
                
                $inputData = Helpers\Utility\Misc::getPostDataFromJson();
            
                if ($userId && $inputData['id'])
                {
                    $userManagement = new Helpers\UserManagement();
                    
                    // проверим, принадлежит ли пользователь нашему суперпользователю
                    if ($userManagement->userInSuperUserAccount($userId, $inputData['id']))
                    {
                        $resultUpdate = (new \Godra\Api\Helpers\Auth\Bitrix)->updateBySuperUser(Helpers\Utility\Misc::getPostDataFromJson());
                        
                        if ($resultUpdate['status'])
                        {
                            $status = 1;
                        }
                        else
                        {
                            $API_ERRORS[] = $resultUpdate['apiErrorText'];
                            
                            if ($resultUpdate['apiErrorStatusCode'])
                            {
                                $apiErrorStatusCode = $resultUpdate['apiErrorStatusCode'];
                            }
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
                    $API_ERRORS[] = 'Не заполнены обязательные поля';
                    
                    $apiErrorStatusCode = 422;
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
            $API_ERRORS[] = 'Необходимо авторизоваться';
            
            $apiErrorStatusCode = 401;
        }

        $result = ['status' => $status];
        
        return $result;
    }
    
    public function add()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        if ($bitrixUserApi->isAuth())
        {
            if ($bitrixUserApi->isSuperUser())
            {
                $userId = $bitrixUserApi->getUserId();
                
                $inputData = Helpers\Utility\Misc::getPostDataFromJson();
            
                if ($userId)
                {
                    $userManagement = new Helpers\UserManagement();
                    
                    $resultAdd = (new \Godra\Api\Helpers\Auth\Bitrix)->addBySuperUser(Helpers\Utility\Misc::getPostDataFromJson());
                    
                    if ($resultAdd['status'])
                    {
                        $status = 1;
                    }
                    else
                    {
                        $API_ERRORS[] = $resultAdd['apiErrorText'];
                        
                        if ($resultAdd['apiErrorStatusCode'])
                        {
                            $apiErrorStatusCode = $resultAdd['apiErrorStatusCode'];
                        }
                    }
                }
                else
                {
                    $API_ERRORS[] = 'Не заполнены обязательные поля';
                    
                    $apiErrorStatusCode = 422;
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
            $API_ERRORS[] = 'Необходимо авторизоваться';
            
            $apiErrorStatusCode = 401;
        }

        $result = ['status' => $status];
        
        return $result;
    }
    
}
