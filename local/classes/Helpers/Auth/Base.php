<?
namespace Godra\Api\Helpers\Auth;

use \Bitrix\Main\UserTable,
    \Godra\Api\Helpers\Utility\Misc,
    \Bitrix\Main\UserPhoneAuthTable;

class Base
{

    protected $data;
    protected $cuser;
    protected $data_rows = [
        'login',
        'password',
        'code'
    ];

    function __construct()
    {
        global $USER;

        $this->cuser = $USER;
        $this->data  = Misc::getPostDataFromJson();
    }

    public function getMap()
    {
        return $this->data_rows;
    }

    /**
     * Получить логин по телефону или email
     * @param string $row_name Поле которое мы хотим получить
     * @param string $email_or_phone email или телефон пользователя
     * @return string
     */
    public static function getDataByLogin($email_or_phone, $row_name = 'LOGIN')
    {
        Misc::includeModules(['main']);

        $type = \strpos($email_or_phone, '@') ? 'EMAIL' : 'PHONE_NUMBER';

        if($type == $row_name)
            return $email_or_phone;

        if($type == 'PHONE_NUMBER')
        {
            $user_id = UserPhoneAuthTable::getList([
                'filter' => ['PHONE_NUMBER' => $email_or_phone]
            ])->fetch()['USER_ID'];
        }
        else
        {
            $user_id = UserTable::getList([
                'select' => ['ID'],
                'filter' => ['EMAIL' => $email_or_phone]
            ])->fetch()['ID'];
        }

//        return [
//            'php' => 'Base.php',
//            'row_name' => $row_name,
//            'type' => $type,
//            'user_id' => $user_id
//        ];

        if($row_name == 'PHONE_NUMBER')
            return UserPhoneAuthTable::getList([
                        'filter' => ['USER_ID' => $user_id]
                    ])->fetch()['PHONE_NUMBER'];

        $res = \CUser::GetList(
                'sort',
                'asc',
                ['ID' => $user_id]
            )->Fetch()[$row_name] ?:

            UserTable::getList([
                'select' => [$row_name],
                'filter' => ['ID' => $user_id]
            ])->fetch()[$row_name];

        if(empty($res))
                $data['errors'] = 'Поле '.$row_name.' не найдено';

        return $res;
    }

    public function checkConfirmCode($login, $code)
    {
        return ($code == $this->getDataByLogin($login, 'UF_CONFIRM_CODE'));
    }

    /**
     * Сменить пароль
     *
     * @param string $login телеон или почта
     * @return void
     */
    public function changePassword()
    {
        $data = Misc::getPostDataFromJson();

        if($this->checkConfirmCode($data['login'], $data['code']))
        {
            $id = $this->getDataByLogin($data['login'], 'ID');
            $user = new \CUser;
            $result['success'] = $user->Update($id, [
                'UF_CONFIRM_CODE' => rand(0000,9999),
                'PASSWORD' => $data['password'],
                'CONFIRM_PASSWORD' => $data['password']
            ]);

            if ($result['success'])
            {
                $user->Update($id, [ 'UF_CONFIRM_CODE' => rand(0000,9999) ]);
       
                (new \Godra\Api\Notify\Sender)->send(['userId' => $id], new \Godra\Api\Notify\UserChangePasswordSender());
            }
            else
            {
                $result['errors'][] = 'Не получилось сменить пароль, обратитесь в поддержку';
            }
        }
        else
            $result['errors'][] = 'Введен неверный проверочный код';

        return $result;
    }
}
?>