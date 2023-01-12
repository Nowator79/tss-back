<?
namespace Godra\Api\User;

class Delete extends Base
{
    protected $row_data = [
        'email' => [ 'mandatory' => false, 'alias' => 'EMAIL' ], # E-Mail
        'phone_number' => [ 'mandatory' => false, 'alias' => 'PHONE_NUMBER' ], # Номер телефона для регистрации
    ];

    public function deleteUser()
    {
        return $this->delete();
    }
}
