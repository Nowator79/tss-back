<?

namespace Godra\Api\Notify;

use CUser,
    Godra\Api\Helpers\Order;

// вызывается при смене статуса заказа
class OrderStatusChangeSender implements ISender 
{
    // получаем текст уведомления для почты, смс и highload-блока уведомлений в личном кабинете (подготавливаем все данные и отправляем отдельным методом)
    public function send(array $params): void
    {
        $mailText = $smsText = $profileText = [];
        
        //file_put_contents('/home/admin/web/sibir.fishlab.su/public_html/local/log.txt', print_r($params, 1)."\r\n", FILE_APPEND);
        
        /*
        $mailText    - [тип события, [текст сообщения в виде массива с макропеременными #MESSAGE#]]
        $smsText     - [номер телефона, текст смс]
        $profileText - [id пользователя, заголовок (тип уведомления), дата, текст сообщения]
        */
        
        $statusName = '';
        
        if ($params['statusId'])
        {
            $statuses = (new Order)->getStatuses();
            
            $statusName = $statuses[$params['statusId']];
        }
        
        if ($params['orderNum'] && $params['userId'] && $statusName)
        {
            // почта
            $rsUser = CUser::GetByID($params['userId']);
            
            $arUser = $rsUser->Fetch();
            
            $fields = 
            [
                'ORDER_ID'     => $params['orderNum'],
                'ORDER_DATE'   => $params['orderDate'],
                'ORDER_STATUS' => $statusName,
                'NAME'         => $arUser['NAME'],
                'EMAIL'        => $arUser['EMAIL'],
            ];
            
            if ($arUser['EMAIL'] && $params['orderDate'])
            {
                $mailText = ['eventType' => 'ORDER_STATUS_CHANGE', 'fields' => $fields];
            }

            // телефон
            $phone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['=USER_ID' => $params['userId'], '=CONFIRMED' => 'Y']])->fetch();
            
            if ($phone['PHONE_NUMBER'])
            {
                $smsText = ['phone' => $phone['PHONE_NUMBER'], 'message' => 'Статус вашего заказа №'.$params['orderNum'].' изменён на «'.$statusName.'»'];
            }
            
            // личный кабинет
            $profileText = ['userId' => $params['userId'], 'header' => 'Изменился статус вашего заказа', 'date' => date('d.m.Y H:i:s'), 'message' => 'Статус вашего заказа №'.$params['orderNum'].' изменён на «'.$statusName.'»'];

            (new Sender)->sendInternal($mailText, $smsText, $profileText);
        }
    }
}
