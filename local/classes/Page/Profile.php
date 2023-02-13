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
        \Bitrix\Main\Loader::includeModule("highloadblock");
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById(HIGHLOAD_SALES_PLAN_ID)->fetch();
        $entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();

        global $USER;
        $userId = ($USER->GetID() == 0) ? 1 : $USER->GetID();

        $filter = [
            'UF_USER_XML_ID' => Get::getParentUserXmlIdEx($userId)
        ];

        $rsData = $entity_data_class::getList(array(
            "select" => ["*"],
            "order" => ["ID" => "ASC"],
            "filter" => $filter
        ));


//        while($arData = $rsData->Fetch()){
//            var_dump($arData);
//        }

        return $rsData->FetchAll();
    }

    public function changeLogo()
    {

    }
}
