<?

namespace Godra\Api\Notify;

use CUser,
    Godra\Api\Helpers\Order;

// вызывается при получении нового думента в справочник из 1С
class DocumentReceiveSender implements ISender 
{
    public function send(array $params): void
    {
        $mailText = $smsText = $profileText = [];
        
        //file_put_contents('/home/admin/web/sibir.fishlab.su/public_html/local/log.txt', print_r($params, 1)."\r\n", FILE_APPEND);
        
        /*
        $mailText    - [тип события, [текст сообщения в виде массива с макропеременными #MESSAGE#]]
        $smsText     - [номер телефона, текст смс]
        $profileText - [id пользователя, заголовок (тип уведомления), дата, текст сообщения]
        */
        
        /*
        Array
        (
			'XML_ID'     => $entityFields['UF_XML_ID'],
			'NAME'       => $entityFields['UF_NAME'],
			'TYPE'       => $entityFields['UF_TIPDOCUMENTA'],
			'CONTRAGENT' => $entityFields['UF_IDKONTRAGENTA'],
			'FILE'       => $entityFields['UF_FILE'],
			'DATE'       => $entityFields['UF_DATE']->toString(),
        )
        */
        
        // отправляем суперпользователю и всем привязанным к нему пользователям // данные берём по CONTRAGENT // нужно потом доделать фильтр по orderId, если он будет
        $users = [];
        
        if ($params['fields']['NAME'] && $params['fields']['CONTRAGENT'] && $params['fields']['FILE'])
        {
            // суперпользователь
            $rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['XML_ID' => $params['fields']['CONTRAGENT']]);

            while ($arUser = $rsUsers->Fetch())
            {
                $users[] = $arUser;
            }
            
            // подчинённые пользователи
            $rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['UF_PARENT_USER' => $params['fields']['CONTRAGENT'], 'ACTIVE' => 'Y']);

            while ($arUser = $rsUsers->Fetch())
            {
                $users[] = $arUser;
            }
            
            if ($users)
			{
				foreach ($users as $user)
				{
					$mailText = $smsText = $profileText = [];
					
					// почта
					$fields = 
					[
						'USER_NAME'     => $user['NAME'],
						'EMAIL'         => $user['EMAIL'],
						'DOCUMENT_TYPE' => $params['fields']['TYPE'],
						'DOCUMENT_NAME' => $params['fields']['NAME'],
						'DOCUMENT_LINK' => $params['fields']['FILE'],
						'ORDER_ID' 		=> (new Order())->getArrByXml($params['fields']['ORDER_ID'])["ID"],
					];
					
					if ($user['EMAIL'] && $fields['DOCUMENT_NAME'])
					{
						$mailText = ['eventType' => 'NEW_DOCUMENT_RECEIVED', 'fields' => $fields];
					}
					
                    /*
					// телефон
					$phone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['=USER_ID' => $user['ID'], '=CONFIRMED' => 'Y']])->fetch();
					
					if ($phone['PHONE_NUMBER'])
					{
						$smsText = ['phone' => $phone['PHONE_NUMBER'], 'message' => 'Получен новый документ '.$params['fields']['TYPE']];
					}
                    */
					
					// личный кабинет
					$profileText = 
					[
						'userId'  => $user['ID'], 
						//'header'  => 'Сформирован '.($params['fields']['TYPE'] == 'Акт сверки' ? 'Акт сверки' : $params['fields']['NAME']), 
						'header'  => 'Сформирован «'.$params['fields']['TYPE'].'»',
                        'date'    => date('d.m.Y H:i:s'), 
						//'message' => 'Получен новый документ '.$params['fields']['TYPE'].' - '.$params['fields']['NAME'].'. Скачать его можно в личном кабинете в разделе Документы',
                        'message' => 'Сформирован новый документ «'.$params['fields']['NAME'].'». Скачать его можно в личном кабинете в разделе «Документы»',
					];

					(new Sender)->sendInternal($mailText, $smsText, $profileText);
				}
			}
        }
    }
}
