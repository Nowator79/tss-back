<?

namespace Godra\Api\Notify;

use \Bitrix\Main\Loader,
    Godra\Api\Integration\SMSSeveren\Send;

class Sender
{
    public function send(array $params, ISender $sender): void
    {
        $sender->send($params);
    }
    
    // основной метод отправки уведомлений, в него передаются уже окончательно подготовленные к отправке данные
    public function sendInternal(array $mailText, array $smsText, array $profileText): void
    {
        // $mailText    - [тип события, [текст сообщения в виде массива с макропеременными #MESSAGE#]]
        // $smsText     - [номер телефона, текст смс]
        // $profileText - [id пользователя, заголовок (тип уведомления), дата, текст сообщения]
        
        // для теста
        //$smsText = [];
        //$profileText = [];
        //
        
        if ($mailText)
        {
            $resultSend = \Bitrix\Main\Mail\Event::send([
                'EVENT_NAME' => $mailText['eventType'],
                'LID'        => 's1', // SITE_ID в админке выдаёт ru
                'C_FIELDS'   => $mailText['fields'],
            ]);
        }
        
        // телефон
        if ($smsText['phone'] && $smsText['message'])
        {
            $sendResult = (new Send([
                'phone' => $smsText['phone'],
                'text'  => $smsText['message'],
            ]))->send();
        }

        // личный кабинет
        if ($profileText['userId'] && $profileText['header'] && $profileText['date'] && $profileText['message'])
        {
            $arFields = 
            [
                'UF_USER_ID' => $profileText['userId'],
                'UF_TYPE'    => $profileText['header'],
                'UF_DATE'    => $profileText['date'],
                'UF_TEXT'    => $profileText['message'],
                'UF_IS_NEW'  => true,
            ];
            
            $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById(HIGHLOAD_BLOCK_NOTIFICATION_ID)->fetch(); 

			$entityDataClass = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock)->getDataClass();

            $resultAdd = $entityDataClass::add($arFields);
					
            if (!$resultAdd->isSuccess()) 
            {
                // можно добавить логирование в будущем
            }
        }
    }
}
