<?
namespace Godra\Api\User;

use \Bitrix\Main\UserTable,
    \Bitrix\Main\Entity\Query,
    \Godra\Api\Helpers\Utility\Misc,
    \Bitrix\Main\Engine\CurrentUser,
    \Godra\Api\Helpers\Utility\Errors;

class Base
{
    protected $user_id;
    protected $current_user_id;
    protected $company_id;
    protected $fuser_id;
    protected $post_data = [];
    protected $select_rows = [];

    /**
     * Данные для обработки []
     */
    protected $row_data = [];

    function __construct()
    {
        // получаю данные
        $this->post_data = Misc::getPostDataFromJson();
        $this->current_user_id = CurrentUser::get()->getId() ?: Errors::dontAuth();

        // чекаю обязательные поля
        foreach ($this->row_data as $name => $v)
            if($v['mandatory'])
                if(!$this->post_data[$name])
                    Errors::dontHaveRow($name);
    }

    /**
     * добавить пользователя, работает по post_data
     */
    protected function add()
    {
        if($this->current_user_id)
        {
            $user = new \CUser;

            // поля с пост даты
            foreach ($this->post_data as $key => $v)
                if($this->row_data[$key])
                    $fields[$this->row_data[$key]['alias']] = $v;

            // обязательные доп поля
            $fields['ACTIVE'] = 'Y';
            $fields['GROUP_ID'] = [3,4];
            $fields['LOGIN'] = $fields['PHONE_NUMBER'];

            $id = $user->Add($fields);

            return $user->LAST_ERROR ?: $id;
        }
    }

    /**
     * Обновить данные пользователя, работает по post_data
     */
    protected function update()
    {
        if($this->current_user_id)
        {
            $user_id = $this->getUserIdByPostData();

            $user = new \CUser;

            foreach ($this->post_data as $key => $v)
                if($this->row_data[$key])
                    $fields[$this->row_data[$key]['alias']] = $v;

            $fields['ACTIVE'] = 'Y';
            $fields['GROUP_ID'] = [3,4];
            $fields['LOGIN'] = $fields['PHONE_NUMBER'];

            $id = $user->Update($user_id ,$fields);

            return $user->LAST_ERROR ?: $id;
        }
    }

    /**
     * Удалить пользователя, работает по post_data
     */
    protected function delete()
    {
        if($this->current_user_id)
        {
            $user_id = $this->getUserIdByPostData();
            $user = new \CUser;
            $id = $user->Delete($user_id);

            return $user->LAST_ERROR ?: $id;
        }
    }

    protected function getUserIdByPostData()
    {
        return \Godra\Api\Helpers\Auth\Base::getDataByLogin(
                    $this->post_data['email'] ?? $this->post_data['phone_number'],
                    'ID'
                );
    }

    protected function get()
    {
        if($this->current_user_id)
        {
            Misc::includeModules(['main']);

            $select = array_column($this->select_rows, 'name') ?: [];
            $filter = [];
            $order = [];

            $query = new Query(UserTable::getEntity());

            $collection = $query
                ->setSelect($select)
                ->setFilter($filter)
                ->setOrder($order)
                ->setLimit($this->post_data['limit'] ?: 10)
                ->exec()
                ->fetchCollection();

            // обработка значений
            foreach ($collection as $item)
            {
                foreach($select as $key => $name)
                {
                    $field  = $item->get($name);
                    $name   = $this->select_rows[$key]['alias'] ?:  \strtolower($name);
                    $method = $this->select_rows[$key]['method'];

                    // перебор, для случая множественного значения
                    $new_item[$name] = \is_object($field) ?
                        (
                            method_exists($field, 'getValue') ?
                                $field->getValue():
                                self::getAllValues($field)
                        ):
                        $field;

                    /** Обработка переданных методов в поле method , заменяет $val на значение, если нужен просто результат, а не определённое поле результата, то можно передать только метод */
                    if($method)
                        $new_item[$name] = self::executeMethod($method, $new_item[$name]);
                }

                $result['items'][] = $new_item;
            }

            return count($result['errors']) ? $result['errors'] : $result['items'];
        }
    }

    /**
     * Получить все значения множественного свойства элемента коллекции
     * @return array
     */
    protected static function getAllValues($field)
    {
        foreach ($field as $val)
            $res[] = $val->getValue();

        return $res;
    }

    /**
     * Исполнят метод переданный в виде строки с меткой для данных $val
     * Пример {
     *      $method = "str_replace('world', '', $val)";
     *      $value = 'Hello world'
     *      вернёт 'Hello';
     * }
     * @param string $method
     * @param [type] $value
     */
    protected static function executeMethod($method, $value)
    {
            if(\is_array($value))
            {
                foreach ($value as $val)
                    strpos($method, '$val')?
                        eval('$arr[] = '.\str_replace('$val', $val , $method ).';'):
                        $arr[] = $method($val);
            }
            else
                strpos($method, '$val')?
                    eval('$arr = '.\str_replace('$val', $value , $method ).';'):
                    $arr = $method($value);

        return $arr;
    }

    public function getMap()
    {
        return $this->row_data;
    }
}
?>