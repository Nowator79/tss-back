<?
namespace Godra\Api\User;

use Godra\Api\Helpers\Utility\Misc;

class Get extends Base
{
    protected $select_rows = [
        [ 'name' => 'NAME' ], // имя
        [ 'name' => 'LAST_NAME'], // фамилия
        [ 'name' => 'SECOND_NAME'], // отчество
        [ 'name' => 'LOGIN'], // логин
        [ 'name' => 'EMAIL'], // Email
        [ 'name' => 'ID'], // id
        // [ 'name' => 'UF_POINT_OF_SALE'] // торговые точки
    ];

    public function GetUsers()
    {
        return $this->get();
    }

    /**
     * Метод для получения внешнего кода
     *
     * @param $userId
     * @return mixed
     */
    public function getParentUserXmlId($userId) {
        return \Bitrix\Main\UserTable::getList([
            'filter' => [ 'ID' => $userId, 'ACTIVE' => 'Y'],
            'select' => [ 'ID', 'UF_PARENT_USER' ]
        ])->Fetch()['UF_PARENT_USER'];
    }

    /**
     * Метод для получения внешнего кода - поле XML_ID
     *
     * @param $userId
     * @return mixed
     */
    public function getParentUserXmlIdEx($userId) {
        return \Bitrix\Main\UserTable::getList([
            'filter' => [ 'ID' => $userId, 'ACTIVE' => 'Y'],
            'select' => [ 'ID', 'XML_ID' ]
        ])->Fetch()['XML_ID'];
    }

    /**
     * Метод для получения внешнего кода
     *
     * @param $userId
     * @return mixed
     */
    public function getUserIdByXmlId($xmlId) {
        return (int) \Bitrix\Main\UserTable::getList([
            'filter' => [ '=XML_ID' => $xmlId, 'ACTIVE' => 'Y'],
            'select' => [ 'ID', 'XML_ID' ]
        ])->Fetch()['ID'];
    }

    public function getContragents()
    {
        $params = Misc::getPostDataFromJson();

        if (empty($params['userId'])) {
            return ['error' => 'Не передан ID пользователя!'];
        }

        $contragentID = \Bitrix\Main\UserTable::getList([
            'filter' => [ 'ID' => $params['userId'], 'ACTIVE' => 'Y'],
            'select' => [ 'ID', 'UF_CONTRAGENT_ID ' ]
        ])->Fetch()['UF_CONTRAGENT_ID '];

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

}
