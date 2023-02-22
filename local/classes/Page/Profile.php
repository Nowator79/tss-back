<?
namespace Godra\Api\Page;

use Godra\Api\Helpers\Utility\Misc;
use Godra\Api\User\Get;

class Profile
{
    /**
     * личный кабинет, страница "Личные данные"
     */
    public function get()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $page = 1;
        
        if ((int)$_GET['page'])
        {
            $page = (int)$_GET['page'];
        }
        
        $bitrixUserApi = new \Godra\Api\Helpers\Auth\Bitrix();
        
        $notifications = new \Godra\Api\Helpers\Notifications();

        if ($bitrixUserApi->isAuth())
        {
            // принадлежность к суперпользователю
            $result['isSuperUser'] = $bitrixUserApi->isSuperUser();
            
            // данные пользователя для отображения в форме
            $result['userData'] = $bitrixUserApi->getUserData();
            
            // уведомления на первый экран
            $userId = $bitrixUserApi->getUserId();
            
            if ($userId)
            {
                $result['notifications'] = $notifications->getList($page, $userId);
            }
            
            $status = 1;
        }
        else
        {
            $API_ERRORS[] = 'Необходимо авторизоваться';
            
            $apiErrorStatusCode = 401;
        }
        
        $result['status'] = $status;

        return $result;
    }

    /**
     * запросить изменение личных данных пользователя у менеджера
     *
     * @return void
     */
    public function changeUserData()
    {
        $params = Misc::getPostDataFromJson();

        if(!empty($params)){
            \Bitrix\Main\Mail\Event::send(array(
                "EVENT_NAME" => USER_DATA_CHANGE_EVENT,
                "LID" => \Bitrix\Main\Application::getInstance()->getContext()->getSite(),
                "C_FIELDS" => $params
            ));
            return ['success' => 'Данные отправлены на проверку'];
        } else {
            return ['error' => 'Поля формы пустые'];
        }
    }

    /**
     * получить план продаж
     *
     * @return void
     */
    public function getSalesPlan()
    {
        $params = Misc::getPostDataFromJson();
        \Bitrix\Main\Loader::includeModule("highloadblock");
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById(HIGHLOAD_SALES_PLAN_ID)->fetch();
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        global $USER;
        $userId = ($USER->GetID() == 0) ? 1 : $USER->GetID();

        $filter = [
            'UF_USER_XML_ID' => Get::getParentUserXmlIdEx($userId)
        ];

        if (!empty($params['date_begin']) && !empty($params['date_end'])) {
            $BITRIX_DATETIME_FORMAT = 'd.m.Y H:i:s';
            $dateBegin = new \DateTime(sprintf($params['date_begin'], date('Y'), date('m'), date('d')), new \DateTimeZone('UTC'));
            $dateEnd = new \DateTime(sprintf($params['date_end'], date('Y'), date('m'), date('d')), new \DateTimeZone('UTC'));
            $dateBegin->modify('+1 day -1 second');
            $filter['<=UF_DATE'] = $dateEnd->format($BITRIX_DATETIME_FORMAT);
            $filter['>=UF_DATE'] = $dateBegin->format($BITRIX_DATETIME_FORMAT);
        }

        $rsData = $entity_data_class::getList(array(
            "select" => ["*"],
            "order" => ["ID" => "ASC"],
            "filter" => $filter
        ));

        $res = [];
        while($arData = $rsData->Fetch()){
            $arData['UF_DATE'] = $arData['UF_DATE']->toString();
            $res[] = $arData;
        }

        return $res;
    }

    /**
     * получить контрагентов
     *
     * @return string[]|void
     */
    public function getContragents()
    {
        $params = Misc::getPostDataFromJson();

        if (empty($params['userId'])) {
            return ['error' => 'Не передан ID пользователя!'];
        }

        $contragentID = \Bitrix\Main\UserTable::getList([
            'filter' => [ 'ID' => $params['userId'], 'ACTIVE' => 'Y'],
            'select' => [ 'ID', 'XML_ID' ]
        ])->Fetch()['XML_ID'];

        if ($contragentID) {
            $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById(HIGHLOAD_KONTRAGENTS_ID)->fetch();
            $entityDataClass = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock)->getDataClass();

            $filter = [
                'UF_USER' => $contragentID
            ];

            $rsData = $entityDataClass::getList(array(
                "select" => ["*"],
                "order" => ["ID" => "ASC"],
                "filter" => $filter
            ));

            return $rsData->FetchAll();
        }
    }

    public function changeLogo()
    {
        $params = Misc::getPostDataFromJson();

        if (empty($params['userId'])) {
            \CModule::IncludeModule("main");
            $oUser = new \CUser;

            if (!empty($_FILES["logo"]['name'])) {
                $fileId = \CFile::SaveFile($_FILES["logo"], 'avatar');
                $arFile = \CFile::MakeFileArray($fileId);
                $arFile['del'] = "Y";
                $arFields['PERSONAL_PHOTO'] = $arFile;
            }

            $result = $oUser->Update($params['userId'], $arFields);

            if ($result) {
                return [
                    'url' => \CFile::GetPath($fileId),
                    'success' => 'Логоти загружен!'
                ];
            }
        }
    }
}
