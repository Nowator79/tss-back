<?
namespace Godra\Api\User;

class Update extends Base
{
    protected $row_data = [
        'name' => [ 'mandatory' => false, 'alias' => 'NAME' ], # Имя
        'email' => [ 'mandatory' => true, 'alias' => 'EMAIL' ], # E-Mail
        'last_name' => [ 'mandatory' => false, 'alias' => 'LAST_NAME' ], # Фамилия
        'second_name' => [ 'mandatory' => false, 'alias' => 'SECOND_NAME' ], # Отчество
        'password' => [ 'mandatory' => false, 'alias' => 'PASSWORD' ], # Новый пароль
        'phone_number' => [ 'mandatory' => true, 'alias' => 'PHONE_NUMBER' ], # Номер телефона для регистрации
        'point_of_sale' => [ 'mandatory' => true, 'alias' => 'POINT_OF_SALE' ], # ТОрговая точка
    ];

    public function updateUser()
    {
        return $this->update();
    }
}
