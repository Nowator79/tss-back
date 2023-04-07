<?
namespace Godra\Api\Helpers\Auth;

use Bitrix\Main\Mail\Event;
use Godra\Api\Helpers\Auth\Authorisation;
use Godra\Api\Helpers\Utility\Misc;
use \Godra\Api\Integration\SMSSeveren\Send;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

class Restore extends Base
{
    protected $data_rows = [
        'login',
        'password',
        'code'
    ];

    public function setConfirmCodeByLogin($email_or_phone)
    {
        $type = \strpos($email_or_phone, '@') ?
            'EMAIL':
            'PHONE_NUMBER';

        $id    = $this->getDataByLogin($email_or_phone, 'ID');

//        return json_encode([
//            'type' => $type,
//            'id' => $id,
//        ]);

        $phone = $type == 'EMAIL' ? $this->getDataByLogin($email_or_phone, 'PHONE_NUMBER') : $email_or_phone;

        $_SESSION['CONFIRM_CODE'] = rand(1000, 9999);

        $user = new \CUser;
        $user->Update($id, ['UF_CONFIRM_CODE' => $_SESSION['CONFIRM_CODE']]);

        // формируем сообщение и отправляем
        $sms  = (new Send([
                    'phone' => $phone,
                    'text' => 'Ваш проверочный код : '.$_SESSION['CONFIRM_CODE']
                ]))->send();

        return $sms;
    }
    public function emailSend()
    {
        $params = Misc::getPostDataFromJson();
        $siteId = \Bitrix\Main\Context::getCurrent()->getSite();

        $arEventFields = [];
        $arEventFields['SUBJECT']=$params['subject'];
        $eventTempl = 'SEND_MES_LD';

        if($params['subject']=='Личные данные'){
            $arEventFields['NAME']=$params['name'];
            $arEventFields['TEL']=$params['tel'];
            $arEventFields['EMAIL']=$params['email'];
            $arEventFields['PASS']=$params['pass'];
        }else
        if($params['subject']=='Обратная связь'){
            $arEventFields['NAME']=$params['name'];
            $arEventFields['EMAIL']=$params['email'];
            $arEventFields['COMMENT']=$params['comment'];
            $eventTempl = 'SEND_MES_BACK_CALL';
        }else{
            $arEventFields['MESSAGE']=$params['text'];
            $eventTempl = 'SEND_MES_UD';
        }
        $mes_send =  \CEvent::Send($eventTempl, $siteId, $arEventFields);

        return $mes_send;
    }
    public function getBanner()
    {
        $iblock_id = 1;
        $mas_banner = [];
        Loader::includeModule('iblock');
        $arFilter = Array("IBLOCK_ID"=>$iblock_id, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
        $res = \CIBlockElement::GetList(Array("SORT"=>"ASC"), $arFilter, false, Array(), Array('*'));
        while($ob = $res->GetNextElement()){
            $fields = $ob->GetFields();
            $props = $ob->GetProperties();
            $mas_banner[] = [
                'NAME'=>$fields['NAME'],
                'TEXT'=>$fields['PREVIEW_TEXT'],
                'BUTTON_LINK'=>$props['BUTTON_LINK']['VALUE'],
                'BUTTON_CAPTION'=>$props['BUTTON_CAPTION']['VALUE'],
                'HIDE_HEADER'=>$props['HIDE_HEADER']['VALUE'],
                'COLOR_HEADER'=>$props['COLOR_HEADER']['VALUE'],
                'PICTURE'=>\CFile::GetPath($props['all_width_picture']['VALUE']),
            ];
        }
        return $mas_banner;
    }
    public function getDoc()
    {
        $params = Misc::getPostDataFromJson();
        $mas_doc =[];

        if($params['user_id']){
            $user_id = $params['user_id'];
        }else{
            global $USER;
            $user_id = $USER->GetID();
        }
        $rsUser = \CUser::GetByID($user_id);
        $arUser = $rsUser->Fetch();

        Loader::includeModule("highloadblock");

        $hlbl = 65; // Указываем ID нашего highloadblock блока к которому будет делать запросы.
        $hlblock = HL\HighloadBlockTable::getById($hlbl)->fetch();

        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        $rsData = $entity_data_class::getList(array(
            "select" => array("*"),
            "order" => array("ID" => "ASC"),
            "filter" => array("UF_KONTRAGENT"=>$arUser['XML_ID']),  // Задаем параметры фильтра выборки
        ));

        while($arData = $rsData->Fetch()){
            $date = $arData['UF_DATADOKUMENTA']->toString();
            $date = strtotime($date);
            $date = date('d.m.Y', $date);
            $arData['UF_FILE'] = $arData["UF_SSYLKANADOKUMENT"];
            $arData['UF_DATE'] = $date;
            if(!$arData['UF_NAME'])$arData['UF_NAME']=$arData['UF_VIDDOKUMENTA'];
            $mas_doc[] =[
                'ID'=>$arData['ID'],
                'UF_NAME'=>$arData['UF_NAME'],
                'UF_XML_ID'=>$arData['UF_XML_ID'],
                'UF_TIPDOCUMENTA'=>$arData['UF_VIDDOKUMENTA'],
                'UF_IDKONTRAGENTA'=>$arData['UF_KONTRAGENT'],
                'UF_DATE'=>$arData['UF_DATE'],
                'UF_USER'=>$user_id,
                'UF_FILE'=>$arData['UF_FILE'],
            ];
        }
        return $mas_doc;
    }
    public function forEmailOrPhone()
    {
        $this->setConfirmCodeByLogin($this->data['login']);
    }

    /**
     * метод для отправки кода на номер телефона и / или логин
     *
     * @return string[]
     */
    public function sendLogin() 
    {
		$status = 0;
		
		$userId = $phone = $userEmail = $errorText = $successText = '';
		
		$confirmCode = rand(1000, 9999);
		
		if ($this->data['login'])
		{
			$loginType = Authorisation::defineLoginType($this->data['login']);
			
			if ($loginType == 'phone') 
			{
				$this->data['login'] = '+'.NormalizePhone($this->data['login']);

				// по телефону из таблицы телефонов (там лежат очищенные от посторонних символов телефоны и готовые к смс) вынимаем id пользователя
				$userPhone = Authorisation::getUserAuthByPhone($this->data['login']);
				
				if ($userPhone['USER_ID'] && $userPhone['PHONE_NUMBER'])
				{
					// проверим существование и активность пользователя
					$rsUsers = \CUser::GetList(($by = 'id'), ($order = 'asc'), ['ID' => $userPhone['USER_ID'], 'ACTIVE' => 'Y']);

					while ($arUser = $rsUsers->Fetch())
					{
						$userId = $arUser['ID'];
					}
					
					if ($userId)
					{
						$status = 1;
						
						$_SESSION['CONFIRM_CODE'] = $confirmCode;
						
						$sms = (new Send([
							'phone' => $userPhone['PHONE_NUMBER'],
							'text'  => 'Ваш проверочный код: '.$confirmCode
						]))->send();
						
						$successText = 'Проверочный код был отправлен на номер телефона';
					}
					else
					{
						$errorText = 'Пользователь не найден';
					}
				}
				else
				{
					$errorText = 'Пользователь не найден';
				}
			}
			else
			{
				$rsUsers = \CUser::GetList(($by = 'id'), ($order = 'asc'), ['=EMAIL' => $this->data['login'], 'ACTIVE' => 'Y']);

				while ($arUser = $rsUsers->Fetch())
				{
					$userId = $arUser['ID'];
					
					$userEmail = $arUser['EMAIL'];
				}
				
				if ($userId && $userEmail)
				{
					$status = 1;
					
					$_SESSION['CONFIRM_CODE'] = $confirmCode;
					
					$_SESSION['RESTORE_USER_ID'] = $userId;
					
					Event::send(
					[
						'EVENT_NAME' => 'RECOVERY_CONFIRM_CODE',
						'MESSAGE_ID' => 92,
						'LID' => 's1',
						'C_FIELDS' => 
						[
							'CODE'     => $confirmCode,
							'EMAIL_TO' => $userEmail,
						],
					]);
					
					$successText = 'Проверочный код был отправлен на электронную почту';
				}
				else
				{
					$errorText = 'Пользователь не найден';
				}
			}
		}
		else
		{
			$errorText = 'Пользователь не найден';
		}
		
		if ($status)
		{
			if ($userId && $confirmCode)
			{
				(new \CUser)->Update($userId, ['UF_CONFIRM_CODE' => $confirmCode]);
			}
			
			http_response_code(200);
			
			return ['status' => 1, 'successText' => $successText];
		}
		else
		{
			http_response_code(422);
			
			return ['status' => 0, 'errorText' => $errorText];
		}
    }

    /**
     * Метод для сверки проверочного кода
     *
     * @return string[]
     */
    public function sendCode() 
    {    
        /*
        $headers = apache_request_headers();

        if (empty($this->data['code']) || !isset($this->data['code'])) {
            http_response_code(200);
            return [ 'error' => 'Не указан код'];
        }

        // проверка по токену
        if (Authorisation::checkToken($headers)) {
            // id пользваотеля
            $tokenUserId = Authorisation::getUserId($headers);
        }

        if (!$tokenUserId) {
            http_response_code(200);
            return [ 'error' => 'Пользователь не найден' ];
        }
        */
        
        $status = 0;

        if ($_SESSION['RESTORE_USER_ID'])
        {
            $confirmCode = (int) \Bitrix\Main\UserTable::getList([
                'select' => ['ID', 'UF_CONFIRM_CODE'],
                'filter' => [
                    'ID' => $_SESSION['RESTORE_USER_ID'],
                    //'=EMAIL' => $this->data['login'],
                    'ACTIVE' => 'Y'
                ]
            ])->Fetch()['UF_CONFIRM_CODE'];

            if (!$confirmCode) 
            {
                //http_response_code(200);
                //return [ 'error' => 'Отсутствует проверочный код' ];
                $errorText = 'Отсутствует проверочный код';
            }

            if ($confirmCode == $this->data['code']) 
            {
                $status = 1;
                //http_response_code(200);
                //return [ 'message' => 'OK' ];
            }
            else
            {
                $errorText = 'Проверочный код не совпадает';
            }
        }
        else
        {
            $errorText = 'Ваша сессия истекла, повторите процедуру восстановления пароля';
        }

        if ($status)
        {
            http_response_code(200);
        }
        else
        {
            http_response_code(422);
        }
        
        return ['status' => $status, 'error' => $errorText];
    }

    // метод для обновления пароля
    public function sendPassword() 
    {
        if (empty($this->data['password']) || !isset($this->data['password'])) {
            http_response_code(422);
            return [ 'error' => 'Не указан пароль'];
        }

        /*
        $headers = apache_request_headers();
        
        // проверка по токену
        if (Authorisation::checkToken($headers)) {
            // id пользваотеля
            $tokenUserId = Authorisation::getUserId($headers);
        }

        if (!$tokenUserId) {
            http_response_code(200);
            return [ 'error' => 'Пользователь не найден' ];
        }
        
        // обновление пароля
        global $USER;
        $arResult = $USER->Update($tokenUserId, [
            'ACTIVE' => 'Y',
            'PASSWORD' => htmlspecialchars(strip_tags($this->data['password'])),
            'CONFIRM_PASSWORD' => htmlspecialchars(strip_tags($this->data['password'])),
        ]);

        if (!$arResult) {
            http_response_code(400);
            return [ 'error' => $USER->LAST_ERROR ];
        }

        http_response_code(200);
        return [ 'message' => 'OK' ];
        */
        
        if ($_SESSION['RESTORE_USER_ID'])
        {
            global $USER;
            
            $arResult = $USER->Update($_SESSION['RESTORE_USER_ID'], [
                'ACTIVE' => 'Y',
                'PASSWORD' => htmlspecialchars(strip_tags($this->data['password'])),
                'CONFIRM_PASSWORD' => htmlspecialchars(strip_tags($this->data['password'])),
            ]);
            
            if (!$arResult) 
            {
                http_response_code(422);
                
                return ['status' => 0, 'error' => $USER->LAST_ERROR];
            }
            else
            {
                http_response_code(200);
            
                return ['status' => 1];
            }
        }
        else
        {
            $errorText = 'Ваша сессия истекла, повторите процедуру восстановления пароля';
            
            return ['status' => $status, 'error' => $errorText];
        }
    }
}
?>