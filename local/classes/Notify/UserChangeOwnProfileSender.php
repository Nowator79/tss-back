<?

namespace Godra\Api\Notify;

use CUser,
    Godra\Api\Helpers\Order;

// вызывается после изменением личных данных пользователем
class UserChangeOwnProfileSender implements ISender 
{
    public function send(array $params): void
    {
        $mailText = $smsText = $profileText = [];
        
        /*
        $mailText    - [тип события, [текст сообщения в виде массива с макропеременными #MESSAGE#]]
        $smsText     - [номер телефона, текст смс]
        $profileText - [id пользователя, заголовок (тип уведомления), дата, текст сообщения]
        */
        
        // ['userId' => $USER->GetID(), 'messageArr' => $notifyText]
        
        if ($params['userId'] && $params['messageArr'])
        {
            $message = implode('<br>', $params['messageArr']);
            
            // почта
            $rsUser = CUser::GetByID($params['userId']);
            
            $arUser = $rsUser->Fetch();
            
            $fields = 
            [
                'NAME'    => $arUser['NAME'],
                'EMAIL'   => $arUser['EMAIL'],
                'MESSAGE' => $message,
            ];
            
            if ($arUser['EMAIL'])
            {
                $mailText = ['eventType' => 'CHANGE_OWN_PROFILE', 'fields' => $fields];
            }
            
            /*
            // телефон
            $phone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['=USER_ID' => $params['userId'], '=CONFIRMED' => 'Y']])->fetch();
            
            if ($phone['PHONE_NUMBER'])
            {
                $smsText = ['phone' => $phone['PHONE_NUMBER'], 'message' => 'Вы изменили свои личные данные: '. $message];
            }
            */
            
            // личный кабинет
            $profileText = 
            [
                'userId'  => $params['userId'], 
                'header'  => 'Ваши персональные данные были изменены', 
                'date'    => date('d.m.Y H:i:s'), 
                'message' => 'Вы изменили свои персональные данные: '. $message,
            ];
            
            (new Sender)->sendInternal($mailText, $smsText, $profileText);
        }
    }
}
