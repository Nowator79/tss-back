<?php

namespace Godra\Api;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Highloadblock as HL;

use Bitrix\Main\UserPhoneAuthTable;
use Godra\Api\Helpers\Utility\Misc;
use Godra\Api\Helpers\Auth\Authorisation;

class UserRequests
{
    public $post_data;
    public $errors;
    public $user_id;

    public function __construct() {
        Loader::includeModule('form');
        $this->post_data = Misc::getPostDataFromJson();
        $this->errors = [];

        $headers = apache_request_headers();
        $this->user_id = Authorisation::getUserId($headers);

        if (!$this->user_id) {
            $this->errors[] = 'Пользователь не найден';
        }

        if (!empty($this->errors)) {
            return [ 'errors' => $this->errors ];
        }
    }

    /**
     * Метод для получения имени или логина пользователя
     *
     * @return mixed
     */
    public function getUserData() {
        return \CUser::GetByID($this->user_id)->Fetch();
    }

    /**
     * Метод для получения текущего даты и времени
     *
     * @return string
     */
    public function getCurrentDateTimeStr() {
        $objDateTime = new DateTime();
        return $objDateTime->toString();
    }

    /**
     * Метод для получения ид контрагента
     *
     * @return mixed
     */
    public function getContragentId() {
        return \Bitrix\Main\UserTable::getList([
            'filter' => [ 'ID' => $this->user_id ],
            'select' => [
                'ID',
                'UF_CONTRAGENT_ID'
            ]
        ])->Fetch()['UF_CONTRAGENT_ID'];
    }

    /**
     * Метод для получения торгового представителя(ей)
     *
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public function getSaleRepresentative() {
        $saleRepresentatives = [];

        $saleRepresentativesOuterIds = [];

        $contragentId = $this->getContragentId();

        $deals = $this->getDealsByContragentId($contragentId);

        foreach ($deals as $deal) {
            if (strpos($deal['UF_TORGOVYJPREDSTAVITEL'], ',')) {
                $arr = explode(',', $deal['UF_TORGOVYJPREDSTAVITEL']);

                foreach ($arr as $item) {
                    if (trim($item) !== '') {
                        $saleRepresentativesOuterIds[] = trim($item);
                    }
                }
            } else {
                if (trim($deal['UF_TORGOVYJPREDSTAVITEL']) !== '') {
                    $saleRepresentativesOuterIds[] = trim($deal['UF_TORGOVYJPREDSTAVITEL']);
                }
            }
        }

        $saleRepresentativesOuterIds = array_values($saleRepresentativesOuterIds);

        $usersObjs = \Bitrix\Main\UserTable::getList([
            'filter' => [ '%XML_ID' => $saleRepresentativesOuterIds ]
        ]);

        $usersData = [];

        while ($row = $usersObjs->Fetch()) {
            $usersData[] = [
                'ID' => (int) $row['ID'],
                'NAME' => $row['NAME'],
                'LOGIN' => $row['LOGIN'],
                'EMAIL' => $row['EMAIL'],
                'PHONE' => UserPhoneAuthTable::getList([
                    'filter' => [
                        'USER_ID' => (int) $row['ID'],
                        'CONFIRMED' => 'Y'
                    ]
                ])->Fetch()['PHONE_NUMBER']
            ];
        }

        return $usersData;
    }

    /**
     * Метод для получения договоров по id контрагента
     *
     * @param $contragentId
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public function getDealsByContragentId($contragentId) {
        $deals = [];

        $hlblock = HL\HighloadBlockTable::getById(HIGHLOAD_DOGOVORA_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $entityDataClass = $entity->getDataClass();

        $dealsObjs = $entityDataClass::getList([
            'select' => ['*'],
            'filter' => [
                'UF_IDKONTRAGENTA' => $contragentId
            ]
        ]);

        while ($row = $dealsObjs->Fetch()) {
            $deals[] = $row;
        }

        return $deals;
    }

    /**
     * Метод для получения договора по его номеру
     *
     * @return mixed
     * @throws \Bitrix\Main\SystemException
     */
    public function getDealByDealNumber() {
        $hlblock = HL\HighloadBlockTable::getById(HIGHLOAD_DOGOVORA_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $entityDataClass = $entity->getDataClass();

        return $entityDataClass::getList([
            'select' => ['*'],
            'filter' => [
                'UF_NOMERDOGOVORA' => $this->post_data['id']
            ]
        ])->Fetch();
    }

    /**
     * метод для получения торгового представителя по договору (договору-приложению)
     *
     * @return array|array[]
     * @throws \Bitrix\Main\SystemException
     */
    public function getSaleRepresentativeByDeal() {
        $saleRepresentativesOuterIds = [];
        $deal = $this->getDealByDealNumber();

        if (!$deal) {
            $this->errors[] = 'Договора не существует';
            return [ 'errors' => $this->errors ];
        }

        if (strpos($deal['UF_TORGOVYJPREDSTAVITEL'], ',')) {
            $arr = explode(',', $deal['UF_TORGOVYJPREDSTAVITEL']);

            foreach ($arr as $item) {
                $saleRepresentativesOuterIds[] = trim($item);
            }
        } else {
            $saleRepresentativesOuterIds[] = trim($deal['UF_TORGOVYJPREDSTAVITEL']);
        }

        $usersObjs = \Bitrix\Main\UserTable::getList([
            'filter' => [
                '%XML_ID' => $saleRepresentativesOuterIds,
                'ACTIVE' => 'Y'
            ]
        ]);

        $usersData = [];

        while ($row = $usersObjs->Fetch()) {
            $usersData[] = [
                'ID' => (int) $row['ID'],
                'NAME' => $row['NAME'],
                'LOGIN' => $row['LOGIN'],
                'EMAIL' => $row['EMAIL'],
                'PHONE' => UserPhoneAuthTable::getList([
                    'filter' => [
                        'USER_ID' => (int) $row['ID'],
                        'CONFIRMED' => 'Y'
                    ]
                ])->Fetch()['PHONE_NUMBER']
            ];
        }

        return $usersData;
    }

    /**
     * Метод для создания результата формы "Вызов торгово предложения"
     *
     * @return array|array[]|string[]
     */
    public function callSalesRepresentative() {
        if (!isset($this->post_data['message']) || empty($this->post_data['message'])) {
            $this->errors[] = 'Не заполнено поле сообщения';
        }

        if (!isset($this->post_data['id']) || empty($this->post_data['id'])) {
            $this->errors[] = 'Не передан номер договора';
        }

        if (!empty($this->errors)) {
            return [ 'errors' => $this->errors ];
        }

        // данные о пользователе
        $user = $this->getUserData();

        // дата и время получения запроса
        $createdDateTime = $this->getCurrentDateTimeStr();

        Loader::includeModule('form');
        // идентификатор формы
        $formId = (int) \CFormResult::GetList(3, ($by="s_id"), ($order="desc"),false, false, 'N')->Fetch()['ID'] + 1;

        // найти торгового представителя по договору, по договору-приложению
        $saleRepresentative = $this->getSaleRepresentativeByDeal();

        $arFields = [
            // общие поля
            // дата и время создания заявки
            'form_text_24' => $createdDateTime,
            // идентификатор заявки
            'form_text_28' => $formId,
            // имя или логин пользователя
            'form_text_25' => $user['NAME'] ?? $user['LOGIN'],
            // номер договора
            'form_text_11' => $this->post_data['id'],
            // адрес эл. почты пользователя
            'form_text_26' => $user['EMAIL'],

            // данные для пользователя
            // номер телефона первого торгового представителя
            'form_text_22' => $saleRepresentative[0]['PHONE'],

            // данные для представителя
            // сообщение заявки
            'form_text_10' => $this->post_data['message'],
            // идентификатор пользователя
            'form_text_12' => $this->user_id,
            // имя или логин торгового представителя
            'form_text_21' => !empty($saleRepresentative[0]['NAME']) ? $saleRepresentative[0]['NAME'] : $saleRepresentative[0]['LOGIN'],
            // адрес электронной почты первого торгового представителя
            'form_text_23' => $saleRepresentative[0]['EMAIL'],
        ];

        return $this->addResult(3, $arFields);
    }

    /**
     * Метод для создания результата формы "Запрос акт сверки"
     *
     * @return array[]|string[]
     */
    public function getAct() {
        if (!isset($this->post_data['date']) || empty($this->post_data['date'])) {
            $this->errors[] = 'Не заполнено поле даты';
        }

        if (!isset($this->post_data['id']) || empty($this->post_data['id'])) {
            $this->errors[] = 'Не передан номер договора';
        }

        if (!empty($this->errors)) {
            return [ 'errors' => $this->errors ];
        }

        // данные о пользователе
        $user = $this->getUserData();

        // дата и время получения запроса
        $createdDateTime = $this->getCurrentDateTimeStr();

        // идентификатор формы
        $formId = (int) \CFormResult::GetList(4, ($by="s_id"), ($order="desc"),false, false, 'N')->Fetch()['ID'] + 1;

        // найти торгового представителя по договору, по договору-приложению
        $saleRepresentative = $this->getSaleRepresentativeByDeal();

        $arFields = [
            // общие поля
            // имя или логин пользователя
            'form_text_29' => $user['NAME'] ?? $user['LOGIN'],
            // дата и время создания заявки
            'form_text_30' => $createdDateTime,
            // идентификатор запроса
            'form_text_31' => $formId,

            // пользователю
            // электронный адрес пользователя
            'form_text_32' => $user['EMAIL'],

            // торговому представителю
            // период дат
            'form_text_13' => $this->post_data['date'],
            // номер договора
            'form_text_14' => $this->post_data['id'],
            // идентификатор пользователя
            'form_text_15' => $this->user_id,

            // имя или логин торгового представителя
            'form_text_42' => !empty($saleRepresentative[0]['NAME']) ? $saleRepresentative[0]['NAME'] : $saleRepresentative[0]['LOGIN'],
            // адрес электронной почты первого торгового представителя
            'form_text_43' => $saleRepresentative[0]['EMAIL'],
        ];

        return $this->addResult(4, $arFields);
    }

    /**
     * Метод для создания результата формы "Запрос прайс-листа"
     *
     * @return array[]|string[]
     */
    public function getPriceList() {
        if (!isset($this->post_data['message']) || empty($this->post_data['message'])) {
            $this->errors[] = 'Не заполнено поле сообщения';
        }

        if (!empty($this->errors)) {
            return [ 'errors' => $this->errors ];
        }

        // идентификатор формы
        $formId = (int) \CFormResult::GetList(5, ($by="s_id"), ($order="desc"),false, false, 'N')->Fetch()['ID'] + 1;

        // данные о пользователе
        $user = $this->getUserData();

        // дата и время получения запроса
        $createdDateTime = $this->getCurrentDateTimeStr();

        $saleRepresentatives = $this->getSaleRepresentative();

        $saleEmails = '';
        foreach ($saleRepresentatives as $item) {
            $saleEmails .= $item['EMAIL'] . ',';
        }

         $arFields = [
            // сообщение заявки
            'form_text_16' => $this->post_data['message'],
            // идентификатор пользователя
            'form_text_17' => $this->user_id,
            // дата и время запроса
            'form_text_36' => $createdDateTime,
            // идентификатор запроса
            'form_text_33' => $formId,
            // имя пользователя
            'form_text_34' => $user['NAME'] ?? $user['LOGIN'],
            // электронный адрес пользователя
            'form_text_35' => $user['EMAIL'],
            // адреса торговых представителей
            'form_text_45' => $saleEmails
        ];

        return $this->addResult(5, $arFields);
    }

    /**
     * Метод для создания результата формы "Написать директору"
     *
     * @return array[]|string[]
     */
    public function writeDirector() {
        if (!isset($this->post_data['message']) || empty($this->post_data['message'])) {
            $this->errors[] = 'Не заполнено поле сообщения';
        }

        if (!isset($this->post_data['id']) || empty($this->post_data['id'])) {
            $this->errors[] = 'Не передан номер договора';
        }

        if (!empty($this->errors)) {
            return [ 'errors' => $this->errors ];
        }

        // идентификатор формы
        $formId = (int) \CFormResult::GetList(6, ($by="s_id"), ($order="desc"),false, false, 'N')->Fetch()['ID'] + 1;

        // данные о пользователе
        $user = $this->getUserData();

        // дата и время получения запроса
        $createdDateTime = $this->getCurrentDateTimeStr();

        // адреса электронных почт директоров
        $directorEmailsRaw = $this->getDirectorEmailByDeal();

        $directorEmails = '';
        foreach ($directorEmailsRaw as $email) {
            $directorEmails .= $email . ',';
        }
        $directorEmails = substr($directorEmails, 0, -1);

        $arFields = [
            // сообщение заявки
            'form_text_18' => $this->post_data['message'],
            // идентификатор пользователя
            'form_text_19' => $this->user_id,
            // номер договора
            'form_text_20' => $this->post_data['id'],
            // дата и время создания заявки
            'form_text_37' => $createdDateTime,
            // номер заявки
            'form_text_38' => $formId,
            // имя или логин пользователя
            'form_text_39' => $user['NAME'] ?? $user['LOGIN'],
            // адрес электронной почты пользователя
            'form_text_40' => $user['EMAIL'],
            // адреса электронных почт директоров
            'form_text_46' => $directorEmails,
        ];

        return $this->addResult(6, $arFields);
    }

    /**
     * Метод для создания результата веб-формы
     *
     * @param $formId
     * @param $arFields
     * @return array[]|string[]
     */
    public function addResult($formId, $arFields) {
        $resultId = \CFormResult::Add($formId, $arFields, 'N');

        if ($resultId) return [ 'success' => 'Y' ];

        global $strError;
        $this->errors[] = $strError;
        return [ 'errors' => $this->errors ];
    }

    /**
     * Метод для получения номера договоров
     *
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public function getDealsNumbers() {
        $dealsNumbers = [];

        $contragentId = $this->getContragentId();

        $deals = $this->getDealsByContragentId($contragentId);

        foreach ($deals as $deal) {
            $dealsNumbers[] = $deal['UF_NOMERDOGOVORA'];
        }

        return $dealsNumbers;
    }

    /**
     * Метод для получения электронных адресов директоторов распределительных центров
     *
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public function getDirectorEmailByDeal() {
        $hlblock = HL\HighloadBlockTable::getById(HIGHLOAD_DOGOVORA_ID)->fetch();
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);

        $entityDataClass = $entity->getDataClass();

        $centersStr = $entityDataClass::getList([
            'select' => ['*'],
            'filter' => [
                'UF_NOMERDOGOVORA' => $this->post_data['id']
            ]
        ])->Fetch()['UF_RASPREDELITELNYECENTRY'];

        $centersIds = [];
        if (strpos($centersStr, ',')) {
            $arr = explode(',', $centersStr);

            foreach ($arr as $item) {
                $centersIds[] = (int) $item;
            }
        } else {
            $centersIds[] = $centersStr;
        }

        // распределительный центр
        $hlblock2 = HL\HighloadBlockTable::getById(HIGHLOAD_DISTRIBUTION_CENTER_ID)->fetch();
        $entity2 = HL\HighloadBlockTable::compileEntity($hlblock2);

        $entityDataClass2 = $entity2->getDataClass();

        $centersObjs = $entityDataClass2::getList([
            'select' => ['ID', 'UF_POCHTAKONTAKTA'],
            'filter' => [
                'UF_IDRASPREDELITELNOGOCENTRA' => $centersIds
            ]
        ]);

        $emails = [];
        while ($row = $centersObjs->fetch()) {
            $emails[] = $row['UF_POCHTAKONTAKTA'];
        }

        return array_unique($emails);
    }
}

?>