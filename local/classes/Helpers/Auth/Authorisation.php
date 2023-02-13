<?

namespace Godra\Api\Helpers\Auth;

use     \Bitrix\Main\Context,
    \Bitrix\Main\UserTable,
    \Godra\Api\Helpers\Utility\Misc;

use Bitrix\Main\Web\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class Authorisation extends Base
{
    public static $superUsersGroupId = 6;

    protected $data_rows = [
        'login',
        'password'
    ];

    public function isAuth()
    {
        return (bool)$this->cuser->IsAuthorized();
    }

    public function authByPassword()
    {
        $rules = new \Bitrix\Main\Authentication\Policy\RulesCollection;
        // Ставлю время сессии равное времени действия токена
        $rules->set('SESSION_TIMEOUT', (TOKEN_EXPIRE_SEC / 60));

        // авторизация по email или phone
        $emailOrPhone = htmlspecialchars(strip_tags($this->data['login']));

        $type = self::defineLoginType($emailOrPhone);

        switch ($type) {
            case 'email':
                $user = \CUser::GetList(($by = 'personal_country'), ($order = 'desc'), ['=EMAIL' => $emailOrPhone, 'ACTIVE' => 'Y'])->Fetch();
                $userId = (int)$user['ID'];
                break;
            case 'phone':
                $userId = (int)\Bitrix\Main\UserPhoneAuthTable::getList([
                    'filter' => [
                        'CONFIRMED' => 'Y',
                        '%PHONE_NUMBER' => NormalizePhone($emailOrPhone)
                    ] // выборка пользователя с подтвержденным номером
                ])->Fetch()['USER_ID'];

                break;
        }

        if (!$userId) {
            return [
                'error' => 'Пользователя не существует'
            ];
        }

        global $USER;
        if (!$user) {
            $login = \CUser::GetList(($by = 'personal_country'), ($order = 'desc'), ['ID' => $userId, 'ACTIVE' => 'Y'])->Fetch()['LOGIN'];
        } else {
            $login = $user['LOGIN'];
        }

        // авторизация
        $auth = $this->cuser->Login($login, $this->data['password'], "Y", "Y");

        if ($auth['TYPE'] !== 'ERROR') {
            $token = self::createToken([
                'userId' => $userId,
                'permission' => self::defineUserPermission($userId)
            ]);

            return [
                'TOKEN' => $token,
                'USER' => \CUser::GetByID($userId)->Fetch()
            ];
        } else {
            http_response_code(400);
            return ['error' => $auth['MESSAGE']];
        }
    }

    /**
     * метод для определения типа логина - email или телефон
     *
     * @param $emailOrPhone
     * @return string
     */
    public static function defineLoginType($emailOrPhone)
    {
        return strpos($emailOrPhone, '@') ? 'email' : 'phone';
    }

    /**
     * Метод для получения подтвежденного номера телефона пользователя
     *
     * @param $userId
     * @return mixed
     */
    public static function getConfirmedPhone($userId)
    {
        return \Bitrix\Main\UserPhoneAuthTable::getList([
            'filter' => [
                'CONFIRMED' => 'Y',
                'USER_ID' => $userId
            ] // выборка подтвержденного номера
        ])->Fetch()['PHONE_NUMBER'];
    }

    public static function getUserAuthByPhone($phone)
    {
        $result = [];

        if ($phone) {
            $resultPhone = \Bitrix\Main\UserPhoneAuthTable::getList([
                'filter' =>
                    [
                        'CONFIRMED' => 'Y',
                        'PHONE_NUMBER' => $phone
                    ]
            ])->Fetch();

            $result = ['USER_ID' => $resultPhone['USER_ID'], 'PHONE_NUMBER' => $resultPhone['PHONE_NUMBER']];
        }

        return $result;
    }

    /**
     * метод для определения типа пользователя - пользователь или суперпользователь
     *
     * @param $userId
     * @return string
     */
    public static function defineUserPermission($userId)
    {
        $allUserGroups = \CUser::GetUserGroup($userId);

        if (in_array(self::$superUsersGroupId, $allUserGroups)) {
            $permission = 'super';
        } else {
            $permission = 'ordinary';
        }

        return $permission;
    }

    /**
     * метод для создания токена
     *
     * @param $tokenData
     * @return array
     */
    public function createToken($tokenData)
    {
        try {
            http_response_code(200);
            $token = [
                'iss' => ISS,
                'iat' => time(),
                'nbf' => time(),
                'exp' => time() + $this->getPoliciesByUserGroup() * 60 * 3,
                'data' => [
                    'id' => $tokenData['userId'],
                    'permission' => $tokenData['permission']
                ]
            ];

            $jwt = \Firebase\JWT\JWT::encode($token, KEY, 'HS256');
            return [
                'token' => $jwt,
                'isSuperUser' => $tokenData['permission'] == 'ordinary' ? false : true
            ];
        } catch (SystemException $exception) {
            header("HTTP/2.0 {$exception->getCode()}");
            return ['message' => $exception->getMessage()];
        }
    }

    /**
     * Метод для декодирования токена
     *
     * @param $headers
     * @return \stdClass
     */
    public static function getDecodedToken($headers)
    {
        $decodedToken = '';

        $token = preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches) ? $matches[1] : '';

        if ($token) {
            //$token = \Firebase\JWT\JWT::decode($token, new Key(KEY, 'HS256'));

            try {
                $decodedToken = \Firebase\JWT\JWT::decode($token, new Key(KEY, 'HS256'));

                return ['token' => $decodedToken];
            } catch (InvalidArgumentException $e) {
                // provided key/key-array is empty or malformed.
            } catch (DomainException $e) {
                // provided algorithm is unsupported OR
                // provided key is invalid OR
                // unknown error thrown in openSSL or libsodium OR
                // libsodium is required but not available.
            } catch (SignatureInvalidException $e) {
                // provided JWT signature verification failed.
            } catch (BeforeValidException $e) {
                // provided JWT is trying to be used before "nbf" claim OR
                // provided JWT is trying to be used before "iat" claim.
            } catch (ExpiredException $e) {
                // provided JWT is trying to be used after "exp" claim.
            } catch (UnexpectedValueException $e) {
                // provided JWT is malformed OR
                // provided JWT is missing an algorithm / using an unsupported algorithm OR
                // provided JWT algorithm does not match provided key OR
                // provided key ID in key/key-array is empty or invalid.
            }

            //return ['token' => $token];
        }

        return ['error' => 'Отсутствует токен'];
    }

    /**
     * Метод для проверки токена - есть такой пользователь или нет
     *
     * @param $token
     * @return bool
     */
    public static function checkToken($headers)
    {
        $decoded = self::getDecodedToken($headers);

        if (isset($decoded['error'])) return false;
        if (isset($decoded['token']->data->id)) return true;

        return false;
    }

    /**
     * Метод для получения идентификатора пользователя из токена
     *
     * @param $headers
     * @return mixed
     */
    public static function getUserId($headers)
    {
        $decoded = self::getDecodedToken($headers);

        if (isset($decoded['error'])) return $decoded;

        if (isset($decoded['token']->data->id) && (int)$decoded['token']->data->id !== 0) {
            return (int)$decoded['token']->data->id;
        }
    }

    /**
     * Метод для определения является ли суперпользователь пользователь
     *
     * @param $headers
     * @return bool
     */
    public static function isSuperUser($headers)
    {
        $decoded = self::getDecodedToken($headers);

        if (isset($decoded['error'])) return $decoded;

        if (isset($decoded['token']->data->permission) && !empty($decoded['token']->data->permission)) {
            if ($decoded['token']->data->permission == 'super') {
                return true;
            };
        }

        return false;
    }

    /**
     * Получает время действия сессии группы пользователей с кодом all
     * @return int
     */
    public function getPoliciesByUserGroup()
    {
        global $DB;

        // группа "все пользователи"
        $group_alluser_id = $DB->Query('SELECT ID FROM b_group G WHERE STRING_ID="all"', true)->fetch()['ID'];

        // Правила группы
        if ($group_alluser_id)
            $policy = \unserialize(
                $DB->Query('SELECT G.SECURITY_POLICY FROM b_group G WHERE G.ID=' . $group_alluser_id)->fetch()['SECURITY_POLICY']
            );

        return $policy['SESSION_TIMEOUT'] ?: 24;

    }

    public function preAuthByCookieHash()
    {
        $cookie_login = ${\COption::GetOptionString("main", "cookie_name", "BITRIX_SM") . "_LOGIN"};
        $cookie_md5pass = ${\COption::GetOptionString("main", "cookie_name", "BITRIX_SM") . "_UIDH"};

        $this->cuser->LoginByHash($cookie_login, $cookie_md5pass);

        Context::getCurrent()->getResponse()->writeHeaders();
    }

}

?>