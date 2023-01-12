<?

namespace Godra\Api\Notify;

use CUser,
    Godra\Api\Helpers\Order;

// вызывается после изменением личных данных пользователя суперпользователем
class UserChangeLinkedProfileSender implements ISender 
{
    public function send(array $params): void
    {
        $mailText = $smsText = $profileText = [];
        
        /*
        $mailText    - [тип события, [текст сообщения в виде массива с макропеременными #MESSAGE#]]
        $smsText     - [номер телефона, текст смс]
        $profileText - [id пользователя, заголовок (тип уведомления), дата, текст сообщения]
        */
        
        if ($params['userId'] && $params['superuserId'] && $params['messageArr'])
        {
            $message = implode('<br>', $params['messageArr']);
            
            // почта
            $rsSuperUser = CUser::GetByID($params['superuserId']);
            
            $arSuperUser = $rsSuperUser->Fetch();
            
            $rsUser = CUser::GetByID($params['userId']);
            
            $arUser = $rsUser->Fetch();
            
            $fields = 
            [
                'NAME'           => $arUser['NAME'],
                'EMAIL'          => $arUser['EMAIL'],
                'MESSAGE'        => $message,
                'SUPERUSER_NAME' => $arSuperUser['NAME'],
            ];
            
            if ($arUser['EMAIL'])
            {
                $mailText = ['eventType' => 'CHANGE_LINKED_PROFILE', 'fields' => $fields];
            }
            
            /*
            // телефон
            $phone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['=USER_ID' => $params['userId'], '=CONFIRMED' => 'Y']])->fetch();
            
            if ($phone['PHONE_NUMBER'])
            {
                $smsText = ['phone' => $phone['PHONE_NUMBER'], 'message' => 'Пользователь '.$arSuperUser['NAME'].' изменил ваши личные данные: '. $message];
            }
            */
            
            // личный кабинет
            $profileText = 
            [
                'userId'  => $params['userId'], 
                'header'  => 'Изменилась информация в личном кабинете', 
                'date'    => date('d.m.Y H:i:s'), 
                //'message' => 'Пользователь '.$arSuperUser['NAME'].' изменил ваши личные данные: '.$message,
                'message' => 'Данные были изменены: '.$message,
            ];

            (new Sender)->sendInternal($mailText, $smsText, $profileText);
        }
    }
}
