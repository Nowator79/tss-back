<?
namespace Godra\Api\Helpers\Auth;

use \Godra\Api\Services\Form,
    \Godra\Api\Helpers\Utility\Misc;

class Registration extends Base
{
    protected $data_rows = [
        'company_name',
        'inn',
        'login',
        'region',
        'phone',
        'password',
        'email',
    ];

    public function registerByPassword()
    {
        foreach ($this->data_rows as $row)
            if(!isset($data[$row]))
                return ['error' => 'не хватает данных для поля '.$row];

        $arFields = [
            "EMAIL"             => $this->data['email'],
            "LOGIN"             => $this->data['login'],
            "ACTIVE"            => "N",
            "GROUP_ID"          => 1,
            "PASSWORD"          => $this->data['password'],
            "CONFIRM_PASSWORD"  => $this->data['password'],
        ];

        $user = new \CUser;
        $new_user_id = $user->Add($arFields);

        return $new_user_id ?: $new_user_id->LAST_ERROR;
    }

    public function registerByForm()
    {
        global $API_ERRORS, $apiErrorStatusCode;
        
        $result = [];
        
        $status = 0;
        
        $resultForm = (new Form(REGISTRATION_FORM_SID, $this->data))->addResult();
        
        if (!$resultForm['errors'])
        {
            $status = 1;
        }
        else
        {
            $API_ERRORS[] = implode(', ', $resultForm['errors']);
            
            $apiErrorStatusCode = 422;
        }
        
        $result['status'] = $status;
        
        return $result;
    }
}
?>