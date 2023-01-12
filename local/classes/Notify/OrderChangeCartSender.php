<?

namespace Godra\Api\Notify;

use CUser,
    Godra\Api\Helpers\Order;

// вызывается после изменением личных данных пользователем
class OrderChangeCartSender implements ISender 
{
    public function send(array $params): void
    {
        $mailText = $smsText = $profileText = [];
        
        /*
        $mailText    - [тип события, [текст сообщения в виде массива с макропеременными #MESSAGE#]]
        $smsText     - [номер телефона, текст смс]
        $profileText - [id пользователя, заголовок (тип уведомления), дата, текст сообщения]
        */
        
        if ($params['userId'] && $params['orderId'] && $params['orderNum']) // $params['oldCart'] // $params['newCart']
        {
            // почта
            $rsUser = CUser::GetByID($params['userId']);
            
            $arUser = $rsUser->Fetch();
            
            $fields = 
            [
                'NAME'      => $arUser['NAME'],
                'EMAIL'     => $arUser['EMAIL'],
                'ORDER_NUM' => $params['orderNum'],
            ];
            
            if ($arUser['EMAIL'])
            {
                $mailText = ['eventType' => 'CHANGE_ORDER_CART', 'fields' => $fields];
            }
            
            // телефон
            $phone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['=USER_ID' => $params['userId'], '=CONFIRMED' => 'Y']])->fetch();
            
            if ($phone['PHONE_NUMBER'])
            {
                $smsText = ['phone' => $phone['PHONE_NUMBER'], 'message' => 'Состав вашего заказа №'.$params['orderNum'].' изменен. Подробности в личном кабинете'];
            }
            
            // личный кабинет
            $profileText = 
            [
                'userId'  => $params['userId'], 
                'header'  => 'Состав вашего заказа изменен', 
                'date'    => date('d.m.Y H:i:s'), 
                'message' => 'В состав заказа №'.$params['orderNum'].' внесены изменения',
            ];
            
            (new Sender)->sendInternal($mailText, $smsText, $profileText);
        }
    }
}
