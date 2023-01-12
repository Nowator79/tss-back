<?
namespace Godra\Api;

class Outlets
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
                $result['outlets'] = (new Helpers\Outlets)->getList($userId, (int)$_GET['page']);
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
    
    public function all()
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
                //$result['outlets'] = (new Helpers\Outlets)->getList($userId, false, true); // первоначальный вариант
                
                // фронтендер попросил второй уровень (договора) вынести наверх, так как ему сложно работать со вторым уровнем массива, в итоге у нас теперь дублирование данных
                $outlets = (new Helpers\Outlets)->getList($userId, false, true);
                
                if ($outlets['items'])
                {
                    foreach ($outlets['items'] as $outlet)
                    {
                        foreach ($outlet['contracts'] as $contract)
                        {
                            $result['outlets'][] = 
                            [
                                'outlet_id'      => $outlet['id'],
                                'outlet_address' => $outlet['address'],
                                'outle_name'     => $outlet['name'],
                                'contract_id'    => $contract['id'],
                                'contract_name'  => $contract['name'],
                                'contract_date'  => $contract['date'],
                                'selected'       => $contract['selected'],
                            ];
                        }
                    }
                }
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
    
    public function set()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        if ($bitrixUserApi->isAuth())
        {
            $userId = $bitrixUserApi->getUserId();
            
            $data = Helpers\Utility\Misc::getPostDataFromJson();

            if ($userId && $data['outlet'] && $data['contract'])
            {
                (new Helpers\Outlets)->set($userId, $data['outlet'], $data['contract']);
                
                $status = 1;
            }
            else
            {
                $API_ERRORS[] = 'Не заполнены обязательные поля';
                
                $apiErrorStatusCode = 422;
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
