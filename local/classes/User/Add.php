<?
namespace Godra\Api\User;

class Add extends Base
{
    protected $row_data = [
        'name' => [ 'mandatory' => true, 'alias' => 'NAME' ], # Имя
        'email' => [ 'mandatory' => true, 'alias' => 'EMAIL' ], # E-Mail
        'last_name' => [ 'mandatory' => true, 'alias' => 'LAST_NAME' ], # Фамилия
        'second_name' => [ 'mandatory' => true, 'alias' => 'SECOND_NAME' ], # Отчество
        'password' => [ 'mandatory' => true, 'alias' => 'PASSWORD' ], # Новый пароль
        'phone_number' => [ 'mandatory' => true, 'alias' => 'PHONE_NUMBER' ], # Номер телефона для регистрации
        'point_of_sale' => [ 'mandatory' => true, 'alias' => 'POINT_OF_SALE' ], # Торговая точка
    ];

    public function addUser()
    {
        return $this->add();
    }
}
