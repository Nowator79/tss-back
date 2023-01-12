<?

namespace Godra\Api\Helpers\Auth;

use CUser,
    Godra\Api\Helpers\Auth\Authorisation;

class Bitrix
{
    public function isAuth()
    {
        global $USER;
        
        // в битриксе в ядре бывают ошибки, когда $USER не найден
        if (!is_object($USER))
        {
            $USER = new CUser;
        }
        
        $result = false;
        
        if ($USER->IsAuthorized())
        {
            $result = true;
        }

        return $result;
		}
    
    public function getUserId()
    {
        global $USER;
        
        if (!is_object($USER))
        {
            $USER = new CUser;
        }
        
        $result = 0;
        
        if ($USER->IsAuthorized())
        {
            $result = $USER->GetID();
        }

        return $result;
    }
    
    public function isSuperUser($userId = null)
    {
        global $USER;
        
        if (!is_object($USER))
        {
            $USER = new CUser;
        }
        
        $result = false;
        
        if ($USER->IsAuthorized())
        {
            if (!$userId)
            {
                $userId = $USER->GetID();
            }
            
            if ($userId && SUPER_USER_GROUP)
            {
                if (in_array(SUPER_USER_GROUP, $USER->GetUserGroupArray()))
                {
                    $result = true;
                }
            }
        }

        return $result;
    }
    
    public function getUserData()
    {
        global $USER;
        
        if (!is_object($USER))
        {
            $USER = new CUser;
        }
        
        $result = [];
        
        if ($USER->IsAuthorized())
        {
            $name = $USER->GetFirstName();
            
            $login = $USER->GetLogin();
            
            $phone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['USER_ID' => $USER->GetID()]])->fetch()['PHONE_NUMBER'];

            $result = 
            [
                'phone' => ($phone ? $phone : ''),
                'login' => ($login ? $login : ''),
                'name'  => ($name ? $name : ''),
            ];
        }
        
        return $result;
    }
    
    public function getExternalCode($userId)
    {
        $result = '';
        
        if ($userId)
        {
            $rsUser = CUser::GetByID($userId);
        
            $arUser = $rsUser->Fetch();
            
            $result = $arUser['XML_ID'];
        }
        
        return $result;
    }
    
    public function getParentUserId($userId)
    {
        $result = '';
        
        if ($userId)
        {
            $rsUser = CUser::GetByID($userId);
        
            $arUser = $rsUser->Fetch();
            
            $result = $arUser['UF_PARENT_USER'];
        }
        
        return $result;
    }
    
    /**
     *
     * Проверка Email на уникальность
     *
     * @return bool
     */
    public function isEmailUnique($email)
    {
        $result = true;

        if ($email)
        {
            $rsUsers = CUser::GetList($o, $b, ['=EMAIL' => $email], ['ID']);

            while ($arUser = $rsUsers->Fetch())
            {
                if ($arUser['ID'])
                {
                    $result	= false;
                }
            }
        }
        else
        {
            $result = false;
        }

        return $result;
    }

    public function update($data)
    {
        global $USER;
        
        if (!is_object($USER))
        {
            $USER = new CUser;
        }
        
        $isErrorPassword = false;
        
        $apiErrorText = '';
        
        $apiErrorStatusCode = $status = 0;
        
        $result = $arFields = [];
        
        if ($data['name'] && $data['phone'] && $data['login'])
        {
            if ($data['password'] && $data['confirmPassword'])
            {
                if ($data['password'] == $data['confirmPassword'])
                {
                    if (mb_strlen($data['password']) >= 8)
                    {
                        $arFields['PASSWORD'] = $arFields['CONFIRM_PASSWORD'] = $data['password'];
                    }
                    else
                    {
                        $apiErrorText = 'Минимальная длина пароля 8 символов';
                        
                        $apiErrorStatusCode = 422;
                        
                        $isErrorPassword = true;
                    }
                }
                else
                {
                    $apiErrorText = 'Пароль и подтверждение пароля не совпадают';
                    
                    $apiErrorStatusCode = 422;
                    
                    $isErrorPassword = true;
                }
            }
            
            // если были ошибки в задании нового пароля, то сохранять остальную информацию не будем 
            if (!$isErrorPassword)
            {
                $isDuplicate = false;

                // проверим на дубликат (текущего пользователя нужно исключить)
                $rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['!ID' => $USER->GetID(), '=EMAIL' => $data['login']]);

                while ($arUser = $rsUsers->Fetch())
                {
                    $isDuplicate = true;
                    
                    break;
                }

                if (!$isDuplicate)
                {
					$beforeUpdate = [];

					$rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['ID' => $USER->GetID()]);

					while ($arUser = $rsUsers->Fetch())
					{
						$beforeUpdate = $arUser;
						
						break;
					}
                    
                    // текущий телефон (его нет в $arFields)
                    $oldPhone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['=USER_ID' => $USER->GetID()]])->fetch();
                    
                    $beforeUpdate['PHONE_NUMBER'] = $oldPhone['PHONE_NUMBER'];

                    $arFields['NAME'] = $data['name'];
                    
                    $arFields['LOGIN'] = $arFields['EMAIL'] = $data['login'];
                    
                    $arFields['PHONE_NUMBER'] = '+'.NormalizePhone($data['phone']);
                    
                    if ($arFields['PHONE_NUMBER'])
                    {
                        $isExistPhone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['!USER_ID' => $USER->GetID(), 'PHONE_NUMBER' => $arFields['PHONE_NUMBER']]])->fetch();
                    }
                    
                    if (!$isExistPhone)
                    {
                        (new CUser)->Update($USER->GetID(), $arFields);
                    
                        if ($USER->LAST_ERROR)
                        {
                            $apiErrorText = $USER->LAST_ERROR;
                            
                            $apiErrorStatusCode = 422;
                        }
                        else
                        {
                            $status = 1;
                            
							$notifyText = [];
							
							if ($beforeUpdate['NAME'] != $data['name'])
							{
								$notifyText[] = 'Изменено ФИО. Новое значение: '.$data['name'];
							}
							
							if ($arFields['LOGIN'] != $data['login'])
							{
								$notifyText[] = 'Изменён Email. Новое значение: '.$data['login'];
							}
							
							if ($arFields['PHONE_NUMBER'] != $beforeUpdate['PHONE_NUMBER'])
							{
								$notifyText[] = 'Изменён номер телефона. Новое значение: '.$arFields['PHONE_NUMBER'];
                                
                                // подтвердим телефон, интерфейсов на фронте всё равно нет
                                $phoneData = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['USER_ID' => $USER->GetID()]])->fetch();
                                
                                if ($phoneData['USER_ID'])
                                {
                                    \Bitrix\Main\UserPhoneAuthTable::update($phoneData['USER_ID'], ['CONFIRMED' => 'Y']);
                                }
							}
                            
							if ($notifyText)
							{
								(new \Godra\Api\Notify\Sender)->send(['userId' => $USER->GetID(), 'messageArr' => $notifyText], new \Godra\Api\Notify\UserChangeOwnProfileSender());
							}
                        }
                    }
                    else
                    {
                        $apiErrorText = 'Пользователь с таким телефоном уже зарегистрирован в системе';
                        
                        $apiErrorStatusCode = 422;
                    }  
                }
                else
                {
                    $apiErrorText = 'Пользователь с таким Email уже зарегистрирован в системе';
                    
                    $apiErrorStatusCode = 422;
                }  
            }
        }
        else
        {
            $apiErrorText = 'Не заполнены обязательные поля';
            
            $apiErrorStatusCode = 422;
        }
        
        $result['apiErrorText'] = $apiErrorText;
        
        $result['apiErrorStatusCode'] = $apiErrorStatusCode;
        
        $result['status'] = $status;
        
        return $result;
    }
    
    /**
     *
     * событие перед добавлением пользователя методом CUser::Add (форма заказа)
     *
     * @return array
     */
    public function OnBeforeUserAddHandler(&$arFields)
    {
        global $APPLICATION;

        // события вызываются статично
        $userBitrix = new Bitrix();

        $arFields['LOGIN'] = $arFields['EMAIL'];

        // sale.order.ajax не проверяет на уникальность Email даже при выставленной галочке в главном модуле, нужно это делать вручную
        if (!$userBitrix->isEmailUnique($arFields['EMAIL']))
        {
            $APPLICATION->throwException('Данный Email уже зарегистрирован в системе. Авторизуйтесь, либо воспользуйтесь формой восстановления пароля.');

            return false;
        }

        return $arFields;
    }

    /**
     *
     * событие перед регистрацией пользователя методом CUser::Register (стандартная регистрация на странице сайта)
     *
     * @return array
     */
    public function OnBeforeUserRegisterHandler(&$arFields)
    {
        global $APPLICATION;

        $arFields['LOGIN'] = $arFields['EMAIL'];

        $userBitrix = new Bitrix();

        if (!$userBitrix->isEmailUnique($arFields['EMAIL']))
        {
            $APPLICATION->throwException('Данный Email уже зарегистрирован в системе. Авторизуйтесь, либо воспользуйтесь формой восстановления пароля.');

            return false;
        }

        return $arFields;
    }

    /**
     *
     * событие перед обновлением пользователя
     *
     * @return array
     */
    public function OnBeforeUserUpdateHandler(&$arFields) 
    {
        global $APPLICATION;
        
        $isDuplicate = false;

        if ($arFields['EMAIL'])
        {
            $arFields['LOGIN'] = $arFields['EMAIL'];
        }
        
        // на форме задания нового пароля есть только ID и PASSWORD
        if ($arFields['ID'] && $arFields['EMAIL'])
        {
            // проверим на дубликат (текущего пользователя нужно исключить)
            $rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['!ID' => $arFields['ID'], '=EMAIL' => $arFields['EMAIL']]);

            while ($arUser = $rsUsers->Fetch()) 
            {
                $isDuplicate = true;
                
                break;
            }

            if ($isDuplicate)
            {
                $APPLICATION->throwException('Данный Email уже зарегистрирован в системе другим пользователем.');
                
                return false;
            }
        }

        return $arFields;
    }
    
    public function authByToken()
    {
        global $USER;
        
        if (!is_object($USER))
        {
            $USER = new CUser;
        }

        if (!$USER->IsAuthorized())
        {
            $headers = apache_request_headers();
            
            if (Authorisation::checkToken($headers)) 
            {
                $tokenUserId = Authorisation::getUserId($headers);
                
                if ($tokenUserId) 
                {
                    $USER->Authorize($tokenUserId, true);
                }
            }
        }
    }
    
    public function updateBySuperUser($data)
    {
        global $USER;
        
        if (!is_object($USER))
        {
            $USER = new CUser;
        }
        
        $isErrorPassword = false;
        
        $apiErrorText = '';
        
        $apiErrorStatusCode = $status = 0;
        
        $result = $arFields = [];
        
        if ($data['id'] && $data['name'] && $data['phone'] && $data['login'])
        {
            if ($data['password'] && $data['confirmPassword'])
            {
                if ($data['password'] == $data['confirmPassword'])
                {
                    if (mb_strlen($data['password']) >= 8)
                    {
                        $arFields['PASSWORD'] = $arFields['CONFIRM_PASSWORD'] = $data['password'];
                    }
                    else
                    {
                        $apiErrorText = 'Минимальная длина пароля 8 символов';
                        
                        $apiErrorStatusCode = 422;
                        
                        $isErrorPassword = true;
                    }
                }
                else
                {
                    $apiErrorText = 'Пароль и подтверждение пароля не совпадают';
                    
                    $apiErrorStatusCode = 422;
                    
                    $isErrorPassword = true;
                }
            }
            
            // если были ошибки в задании нового пароля, то сохранять остальную информацию не будем 
            if (!$isErrorPassword)
            {
                $isDuplicate = false;

                // проверим на дубликат (текущего пользователя нужно исключить)
                $rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['!ID' => $data['id'], '=EMAIL' => $data['login']]);

                while ($arUser = $rsUsers->Fetch())
                {
                    $isDuplicate = true;
                    
                    break;
                }

                if (!$isDuplicate)
                {
                    $arFields['NAME'] = $data['name'];
                    
                    $arFields['LOGIN'] = $arFields['EMAIL'] = $data['login'];
                    
                    $arFields['PHONE_NUMBER'] = '+'.NormalizePhone($data['phone']);
                    
                    $arFields['UF_CONTRACTS'] = $data['outlets'];
                    
                    $beforeUpdate = [];

					$rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['ID' => $data['id']]);

					while ($arUser = $rsUsers->Fetch())
					{
						$beforeUpdate = $arUser;
						
						break;
					}
                    
                    // текущий телефон (его нет в $arFields)
                    $oldPhone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['=USER_ID' => $data['id']]])->fetch();
                    
                    $beforeUpdate['PHONE_NUMBER'] = $oldPhone['PHONE_NUMBER'];
                    
                    // если такой телефон уже есть в системе у другого пользователя, то битрикс не выдаёт никаких ошибок и при этом данные не сохраняет
                    $isExistPhone = false;
                    
                    if ($arFields['PHONE_NUMBER'])
                    {
                        $isExistPhone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['!USER_ID' => $data['id'], 'PHONE_NUMBER' => $arFields['PHONE_NUMBER']]])->fetch();
                    }
                    
                    if (!$isExistPhone)
                    {
                        if ($beforeUpdate['UF_CONTRACTS'] != $data['outlets'])
                        {
                            // при изменении привязки нужно сбросить торговую точку пользователя (а по хорошему проверить сперва, осталась ли она доступна и не трогать её в этом случае)
                            $arFields['UF_ID_DOGOVOR'] = $arFields['UF_SALE_POINT'] = '';
                        }
                        
                        $resultUpdate = (new CUser)->Update($data['id'], $arFields);
                        
                        if ($USER->LAST_ERROR)
                        {
                            $apiErrorText = $USER->LAST_ERROR;
                            
                            $apiErrorStatusCode = 422;
                        }
                        else
                        {
                            if ($resultUpdate)
                            {
                                $status = 1;
                            
                                $notifyText = [];
                                
                                if ($beforeUpdate['NAME'] != $data['name'])
                                {
                                    $notifyText[] = 'Изменено ФИО. Новое значение: '.$data['name'];
                                }
                                
                                if ($arFields['LOGIN'] != $data['login'])
                                {
                                    $notifyText[] = 'Изменён Email. Новое значение: '.$data['login'];
                                }
                                
                                if ($arFields['PHONE_NUMBER'] != $beforeUpdate['PHONE_NUMBER'])
                                {
                                    $notifyText[] = 'Изменён номер телефона. Новое значение: '.$arFields['PHONE_NUMBER'];
                                    
                                    // подтвердим телефон, интерфейсов на фронте всё равно нет
                                    $phoneData = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['USER_ID' => $data['id']]])->fetch();
                                    
                                    if ($phoneData['USER_ID'])
                                    {
                                        \Bitrix\Main\UserPhoneAuthTable::update($phoneData['USER_ID'], ['CONFIRMED' => 'Y']);
                                    }
                                }
                                
                                if ($notifyText)
                                {
                                    (new \Godra\Api\Notify\Sender)->send(['userId' => $data['id'], 'superuserId' => $USER->GetID(), 'messageArr' => $notifyText], new \Godra\Api\Notify\UserChangeLinkedProfileSender());
                                }
                                
                                if ($beforeUpdate['UF_CONTRACTS'] != $data['outlets'])
                                {
                                    (new \Godra\Api\Notify\Sender)->send(['userId' => $data['id'], 'superuserId' => $USER->GetID(), 'outlets' => $data['outlets']], new \Godra\Api\Notify\UserChangeOutletSender());
                                }
                            }
                            else
                            {
                                $apiErrorText = 'При изменении пользователя произошла ошибка. Проверьте введённый номер телефона.';
                                
                                $apiErrorStatusCode = 422;
                            }
                        }
                    }
                    else
                    {
                        $apiErrorText = 'Пользователь с таким телефоном уже зарегистрирован в системе';
                        
                        $apiErrorStatusCode = 422;
                    }
                }
                else
                {
                    $apiErrorText = 'Пользователь с таким Email уже зарегистрирован в системе';
                    
                    $apiErrorStatusCode = 422;
                }  
            }
        }
        else
        {
            $apiErrorText = 'Не заполнены обязательные поля';
            
            $apiErrorStatusCode = 422;
        }
        
        $result['apiErrorText'] = $apiErrorText;
        
        $result['apiErrorStatusCode'] = $apiErrorStatusCode;
        
        $result['status'] = $status;
        
        return $result;
    }
    
    public function addBySuperUser($data)
    {
        global $USER;
        
        if (!is_object($USER))
        {
            $USER = new CUser;
        }
        
        $isErrorPassword = false;
        
        $apiErrorText = '';
        
        $apiErrorStatusCode = $status = 0;
        
        $result = $arFields = [];
        
        $userId = $USER->GetID();
        
        if ($data['name'] && $data['phone'] && $data['login'] && $data['password'] == $data['confirmPassword'])
        {
            if ($data['password'] == $data['confirmPassword'])
            {
                if (mb_strlen($data['password']) >= 8)
                {
                    $arFields['PASSWORD'] = $arFields['CONFIRM_PASSWORD'] = $data['password'];
                }
                else
                {
                    $apiErrorText = 'Минимальная длина пароля 8 символов';
                    
                    $apiErrorStatusCode = 422;
                    
                    $isErrorPassword = true;
                }
            }
            else
            {
                $apiErrorText = 'Пароль и подтверждение пароля не совпадают';
                
                $apiErrorStatusCode = 422;
                
                $isErrorPassword = true;
            }
            
            // если были ошибки в задании нового пароля, то сохранять остальную информацию не будем 
            if (!$isErrorPassword)
            {
                $isDuplicate = false;

                // проверим на дубликат email/логина
                $rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['=EMAIL' => $data['login']]);

                while ($arUser = $rsUsers->Fetch())
                {
                    $isDuplicate = true;
                    
                    break;
                }

                if (!$isDuplicate)
                {
                    $arFields['NAME'] = $data['name'];
                    
                    $arFields['LOGIN'] = $arFields['EMAIL'] = $data['login'];
                    
                    $arFields['PHONE_NUMBER'] = '+'.NormalizePhone($data['phone']);
                    
                    $arFields['UF_CONTRACTS'] = rtrim($data['outlets'], ',');
                    
                    $arFields['ACTIVE'] = 'Y';
                    
                    // привязка по id
                    // $arFields['UF_PARENT_USER'] = $userId;
                    
                    // привязка по внешнему коду
                    $arFields['UF_PARENT_USER'] = $this->getExternalCode($userId);
                    
                    // если такой телефон уже есть в системе у другого пользователя, то битрикс не выдаёт никаких ошибок и при этом данные не сохраняет
                    $isExistPhone = false;
                    
                    if ($arFields['PHONE_NUMBER'])
                    {
                        $isExistPhone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['PHONE_NUMBER' => $arFields['PHONE_NUMBER']]])->fetch();
                    }
                    
                    if (!$isExistPhone)
                    {
                        $resultUserId = (new CUser)->Add($arFields);

                        if ($USER->LAST_ERROR)
                        {
                            $apiErrorText = $USER->LAST_ERROR;
                            
                            $apiErrorStatusCode = 422;
                        }
                        else
                        {
                            if ($resultUserId)
                            {
                                $status = 1;
                                
                                // подтвердим телефон, интерфейсов на фронте всё равно нет
                                if ($arFields['PHONE_NUMBER'])
                                {
                                    $phoneData = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['USER_ID' => $resultUserId]])->fetch();
                                    
                                    if ($phoneData['USER_ID'])
                                    {
                                        \Bitrix\Main\UserPhoneAuthTable::update($phoneData['USER_ID'], ['CONFIRMED' => 'Y']);
                                    }
									
									(new \Godra\Api\Notify\Sender)->send(['phone' => $arFields['PHONE_NUMBER'], 'email' => $arFields['LOGIN'], 'password' => $arFields['PASSWORD']], new \Godra\Api\Notify\UserAddLinkedProfileSender());
                                }
                            }
                            else
                            {
                                $apiErrorText = 'При создании пользователя произошла ошибка. Проверьте введённый номер телефона.';
                                
                                $apiErrorStatusCode = 422;
                            }
                        }
                    }
                    else
                    {
                        $apiErrorText = 'Пользователь с таким телефоном уже зарегистрирован в системе';
                        
                        $apiErrorStatusCode = 422;
                    }
                }
                else
                {
                    $apiErrorText = 'Пользователь с таким Email уже зарегистрирован в системе';
                    
                    $apiErrorStatusCode = 422;
                }  
            }
        }
        else
        {
            $apiErrorText = 'Не заполнены обязательные поля';
            
            $apiErrorStatusCode = 422;
        }
        
        $result['apiErrorText'] = $apiErrorText;
        
        $result['apiErrorStatusCode'] = $apiErrorStatusCode;
        
        $result['status'] = $status;
        
        return $result;
    }
    
    public function getUserSelectedContract($userId)
    {
        $result = '';

        if ($userId)
        {
            $rsUsers = CUser::GetList(($by = 'id'), ($order = 'asc'), ['ID' => $userId], ['SELECT' => ['UF_*']]);

            while ($arUser = $rsUsers->Fetch()) 
            {
                $result = $arUser['UF_ID_DOGOVOR'];
            }
        }
        
        return $result;
    }
	
    public function addUserFromContragent($xmlId, $email, $name, $phone, $statusName)
    {
        if ($xmlId && $email)
        {
            if ($this->isEmailUnique($email))
            {
                $phoneFormatted = '';
                
                if ($phone)
                {
					$phone = preg_replace('/[^0-9]/', '', $phone);
					
					if (strlen($phone) == 11)
					{
						$phone = '+'.NormalizePhone($phone);
                    
						$isExistPhone = \Bitrix\Main\UserPhoneAuthTable::getList($parameters = ['filter' => ['PHONE_NUMBER' => $phone]])->fetch();
						
						if (!$isExistPhone)
						{
							$phoneFormatted = $phone;
						}
					}
                }

                $password = randString(8);

                $arFields = 
                [
                    'NAME'             => $name,
                    'XML_ID'           => $xmlId,
                    'LOGIN'            => $email,
                    'EMAIL'            => $email,
                    'PHONE_NUMBER'     => $phoneFormatted,
                    'ACTIVE'           => 'Y',
                    'GROUP_ID'         => [6],
                    'PASSWORD'         => $password, 
                    'CONFIRM_PASSWORD' => $password,
					'TITLE'            => $statusName,
                ];

                $result = (new CUser)->Add($arFields);
				
				global $USER;
				
				//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/local/log.txt', print_r($arFields, 1)."\r\n", FILE_APPEND);
				
				if ($USER->LAST_ERROR)
				{
					//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/local/log.txt', 'error - '.$USER->LAST_ERROR."\r\n", FILE_APPEND);
				}
				else
				{
					if ($result)
					{
						if ($phoneFormatted)
						{
							(new \Godra\Api\Notify\Sender)->send(['phone' => $phoneFormatted, 'email' => $email], new \Godra\Api\Notify\UserAddFromContragentSender());
						}
					}
					else
					{
						//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/local/log.txt', 'canceled by phone auth module'."\r\n", FILE_APPEND);
					}
				}
            }
        }
    }
    
	public function OnAfterUserUpdateHandler(&$arFields)
	{
		//
	}

}
