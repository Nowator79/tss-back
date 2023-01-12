<?
namespace Godra\Api;

class User
{
    public function update()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 1;
        
        // редактирование профиля в личном кабинете, при этом пользователь и суперпользователь имеют единую форму и единые обязательные поля
        $resultUpdate = (new \Godra\Api\Helpers\Auth\Bitrix)->update(Helpers\Utility\Misc::getPostDataFromJson());
        
        if (!$resultUpdate['status'])
        {
            $API_ERRORS[] = $resultUpdate['apiErrorText'];
            
            if ($resultUpdate['apiErrorStatusCode'])
            {
                $apiErrorStatusCode = $resultUpdate['apiErrorStatusCode'];
            }
            
            $status = 0;
        }
        
        $result = ['status' => $status];
        
        return $result;
    }

}
