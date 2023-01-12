<?
namespace Godra\Api\Integration\SMSSeveren;

/**
 * @param array $data [ phone => номер телефона, text => текст сообщения]
 */
class Send extends Base
{
    protected $select_rows = [
        'phone', 'text'
    ];

    function __construct($data)
    {
        global $API_ERRORS;
        $this->data = $data;

        foreach ($this->select_rows as $key => $v)
            if(!$data[$v])
                $API_ERRORS[] = 'Не указано поле '.$key;
    }

    public function send()
    {
        return $this->sendMessage();
    }
}
?>