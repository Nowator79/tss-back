<?

namespace Godra\Api\Notify;

use CUser,
    Godra\Api\Helpers\Order;

// вызывается после изменения привязки торговых точек/логоворов пользователя суперпользователем
class UserChangeOutletSender implements ISender 
{
    public function send(array $params): void
    {
        $mailText = $smsText = $profileText = [];
        
        /*
        $mailText    - [тип события, [текст сообщения в виде массива с макропеременными #MESSAGE#]]
        $smsText     - [номер телефона, текст смс]
        $profileText - [id пользователя, заголовок (тип уведомления), дата, текст сообщения]
        */
        
        if ($params['userId'] && $params['superuserId'])
        {
            // если $params['outlets'] пуст, то все привязки у пользователя были удалены
            
            // почта
            $rsSuperUser = CUser::GetByID($params['superuserId']);
            
            $arSuperUser = $rsSuperUser->Fetch();
            
            $rsUser = CUser::GetByID($params['userId']);
            
            $arUser = $rsUser->Fetch();
            
            $fields = 
            [
                'NAME'           => $arUser['NAME'],
                'EMAIL'          => $arUser['EMAIL'],
                'MESSAGE'        => '', // пока оставим на будущее, так как текстов нет, возможно нужно будет передать список новых точек с договорами или что-то подобное
                'SUPERUSER_NAME' => $arSuperUser['NAME'],
            ];
            
            if ($arUser['EMAIL'])
            {
                $mailText = ['eventType' => 'CHANGE_LINKED_OUTLET', 'fields' => $fields];
            }
            
            /*
            // телефон
            $phone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['=USER_ID' => $params['userId'], '=CONFIRMED' => 'Y']])->fetch();
            
            if ($phone['PHONE_NUMBER'])
            {
                $smsText = ['phone' => $phone['PHONE_NUMBER'], 'message' => 'Пользователь '.$arSuperUser['NAME'].' изменил ваши привязки к договорам и торговым точкам'];
            }
            */
            
            // личный кабинет
            $profileText = 
            [
                'userId'  => $params['userId'], 
                'header'  => 'Изменение состава торговых точек и договоров', 
                'date'    => date('d.m.Y H:i:s'),
                //'message' => 'Пользователь '.$arSuperUser['NAME'].' изменил доступным вам договора и торговые точкам',
                'message' => 'Изменился состав назначенных вам договоров и торговых точек',
            ];
            
            (new Sender)->sendInternal($mailText, $smsText, $profileText);
        }
    }
}
