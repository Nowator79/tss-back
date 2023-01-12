<?
namespace Godra\Api;

class Order
{
    public function list()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        $order = new Helpers\Order();
        
        if ($bitrixUserApi->isAuth())
        {
            $result['isSuperUser'] = $bitrixUserApi->isSuperUser();
            
            $result['statuses'] = $order->getStatusesForOrdersPage();
            
            $userId = $bitrixUserApi->getUserId();
            
            if ($userId)
            {
                $result['orders'] = $order->getOrders($userId, $_GET['page'], $_GET['status'], $_GET['dates']);
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
    
    public function get()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        $order = new Helpers\Order();

        if ($bitrixUserApi->isAuth())
        {
            $userId = $bitrixUserApi->getUserId();
            
            if ($userId)
            {
                $resultOrder = $order->getOrder($userId, $_GET['id']);

                if ($resultOrder['status'])
                {
                    $status = 1;
                    
                    $result['order'] = $resultOrder['order'];
                }
                else
                {
                    if ($resultOrder['errorText'])
                    {
                        $API_ERRORS[] = $resultOrder['errorText'];
                    }
                    
                    if ($resultOrder['errorCode'])
                    {
                        $apiErrorStatusCode = $resultOrder['errorCode'];
                    }
                }
            }
            else
            {
                // такого в теории быть не может, но всё же..
                $API_ERRORS[] = 'Не заполнены обязательные поля';
                
                $apiErrorStatusCode = 422;
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
    
    public function repeat()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        $order = new Helpers\Order();

        if ($bitrixUserApi->isAuth())
        {
            $userId = $bitrixUserApi->getUserId();
            
            $orderId = Helpers\Utility\Misc::getPostDataFromJson()['id'];
            
            if ($userId && $orderId)
            {
                $resultOrder = $order->copy($userId, $orderId);

                if ($resultOrder['status'])
                {
                    $status = 1;
                }
                else
                {
                    if ($resultOrder['errorText'])
                    {
                        $API_ERRORS[] = $resultOrder['errorText'];
                    }
                    
                    if ($resultOrder['errorCode'])
                    {
                        $apiErrorStatusCode = $resultOrder['errorCode'];
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
            $API_ERRORS[] = 'Необходимо авторизоваться';
            
            $apiErrorStatusCode = 401;
        }
        
        $result['status'] = $status;
        
        return $result;
    }
    
    public function delete()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $bitrixUserApi = new Helpers\Auth\Bitrix();
        
        $order = new Helpers\Order();

        if ($bitrixUserApi->isAuth())
        {
            $userId = $bitrixUserApi->getUserId();
            
            $orderId = Helpers\Utility\Misc::getPostDataFromJson()['id'];
            
            if ($userId && $orderId)
            {
                $resultOrder = $order->delete($userId, $orderId);

                if ($resultOrder['status'])
                {
                    $status = 1;
                }
                else
                {
                    if ($resultOrder['errorText'])
                    {
                        $API_ERRORS[] = $resultOrder['errorText'];
                    }
                    
                    if ($resultOrder['errorCode'])
                    {
                        $apiErrorStatusCode = $resultOrder['errorCode'];
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
            $API_ERRORS[] = 'Необходимо авторизоваться';
            
            $apiErrorStatusCode = 401;
        }
        
        $result['status'] = $status;
        
        return $result;
    }
}
