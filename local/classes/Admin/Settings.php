<?
namespace Godra\Api\Admin;

use \Bitrix\Main\Config\Option,
    \Godra\Api\Helpers\Utility\Misc;

class Settings extends BaseData
{

    # Табы на странице настроек
    public $tabs;

    # Форма настроек
    public $data;

    function __construct($bEdit)
    {
        $this->preparePostData();
        parent::__construct();
        $this->setData();
        $this->setTabs($bEdit);
    }

    /**
     * Обработка формы
     */
    protected function preparePostData()
    {
        //if($_POST['apply'] OR $_POST['save'])
        //{
            foreach ($_POST as $key => $value)
                //if(\is_array($value))
                    Option::set(
                        'main',
                        'api_'.$key ,
                        \is_array($value) ? \serialize($value) : \trim($value)
                    );

            // Обработка настройки фильтруемости свойств
            if(count($_POST['filter_props']) AND is_array($_POST['filter_props']))
                (new \Godra\Api\Properties\Helper)->updateFilterableProps($_POST['filter_props']);
        //}
    }

    /**
     * Разделитель настроек
     * @param string $name Название подразделителя
     */
    public function hr($name)
    {
        echo  '<tr class="heading"><td colspan="2"><b>'.$name.'</b></td></tr>';
    }

    /**
     *  Название настройки
     * @param string $name Название инпута
     */
    public function input_hr($name)
    {
        return  '<td width="10%" class="adm-detail-content-cell-l">'.$name.'</td>';
    }

    /**
     * Вывести инпуты
     * @param array $data
     */
    public function inputs($data)
    {
        foreach ($data as $input)
            echo $this->input($input);
    }

    /**
     * Вывести инпут
     * @param array $data массив текущего класса
     * @return string html
     */
    private function input($data)
    {
        if($data['tag'] == 'input')
        {
            return
            '<tr>
                '.$this->input_hr($data['title']).'
                <td width="40%" class="adm-detail-content-cell-r">
                    <'.$data['tag'].' type="'.$data['type'].'" size="40" name="'.$data['name'].'" value="'.$data['value'].'">
                </td>
            </tr>';
        }

        if($data['tag'] == 'select')
        {
            $required = $data['required'] ? 'required':'';

            $res =
            '<tr>
                '.$this->input_hr($data['title']).'
                <td width="40%" class="adm-detail-content-cell-r">
                    <'.$data['tag'].' '.$data['tag_data'].' '.$required.' size="10" type="'.$data['type'].'" size="40" name="'.$data['name'].'" value="'.$data['value'].'">';

                    foreach ($data['multiselect_values'] as $value)
                    {
                        $selected = is_array($data['value'])?
                            (\in_array($value['ID'], $data['value']) ? 'selected' : ''):
                            ($value['ID'] == $data['value'] ? 'selected' : '');

                        $res .= '<option '.$selected.' value="'.$value['ID'].'">'.$value['NAME'].'</option>';
                    }

                    $res .= '<./'.$data['tag'].'.>
                </td>
            </tr>';

            return $res;
        }
    }

    /**
     * Табы настроек
     */
    protected function setTabs($bEdit)
    {
        // Табы
        $this->tabs = [
            [
                "DIV" => "general_settings", "TAB" => (($bEdit) ? "Настройки сайта" : "Настройки сайта"),
                "ICON" => "fileman", "TITLE" => (($bEdit) ? "" : "Основные настройки")
            ],
        ];
    }

    protected function setData()
    {
        $this->setSettingsData();
    }
}
?>