<?

namespace Godra\Api\Notify;

use CUser,
    Godra\Api\Helpers\Order;

// вызывается после изменения пароля пользователем
class UserChangePasswordSender implements ISender 
{
    public function send(array $params): void
    {
        $mailText = $smsText = $profileText = [];
        
        /*
        $mailText    - [тип события, [текст сообщения в виде массива с макропеременными #MESSAGE#]]
        $smsText     - [номер телефона, текст смс]
        $profileText - [id пользователя, заголовок (тип уведомления), дата, текст сообщения]
        */
        
        if ($params['userId'])
        {
            // почта
            $rsUser = CUser::GetByID($params['userId']);
            
            $arUser = $rsUser->Fetch();
            
            $fields = 
            [
                'NAME'    => $arUser['NAME'],
                'EMAIL'   => $arUser['EMAIL'],
                'MESSAGE' => '', // пока оставим на будущее
            ];
            
            if ($arUser['EMAIL'])
            {
                $mailText = ['eventType' => 'CHANGE_USER_PASSWORD', 'fields' => $fields];
            }
            
            /*
            // телефон
            $phone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['=USER_ID' => $params['userId'], '=CONFIRMED' => 'Y']])->fetch();
            
            if ($phone['PHONE_NUMBER'])
            {
                $smsText = ['phone' => $phone['PHONE_NUMBER'], 'message' => 'Вами был изменён пароль к аккаунту'];
            }
            */
            
            // личный кабинет
            $profileText = 
            [
                'userId'  => $params['userId'], 
                'header'  => 'Пароль успешно изменен', 
                'date'    => date('d.m.Y H:i:s'),
                'message' => 'Ваш пароль был успешно изменен. Воспользуйтесь новыми данными для входа.',
            ];

            (new Sender)->sendInternal($mailText, $smsText, $profileText);
        }
    }
}
