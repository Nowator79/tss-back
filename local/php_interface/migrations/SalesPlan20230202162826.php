<?php

namespace Sprint\Migration;


class SalesPlan20230202162826 extends Version
{
    protected $description = "";

    protected $moduleVersion = "4.2.4";

    /**
     * @throws Exceptions\HelperException
     * @return bool|void
     */
    public function up()
    {
        $helper = $this->getHelperManager();
        $hlblockId = $helper->Hlblock()->saveHlblock(array (
  'NAME' => 'Saleplan',
  'TABLE_NAME' => 'saleplan',
  'LANG' => 
  array (
    'ru' => 
    array (
      'NAME' => 'План продаж',
    ),
  ),
));
        $helper->Hlblock()->saveField($hlblockId, array (
  'FIELD_NAME' => 'UF_DATE',
  'USER_TYPE_ID' => 'date',
  'XML_ID' => '',
  'SORT' => '100',
  'MULTIPLE' => 'N',
  'MANDATORY' => 'N',
  'SHOW_FILTER' => 'N',
  'SHOW_IN_LIST' => 'Y',
  'EDIT_IN_LIST' => 'Y',
  'IS_SEARCHABLE' => 'Y',
  'SETTINGS' => 
  array (
    'DEFAULT_VALUE' => 
    array (
      'TYPE' => 'NONE',
      'VALUE' => '',
    ),
  ),
  'EDIT_FORM_LABEL' => 
  array (
    'en' => '',
    'ru' => 'Дата',
  ),
  'LIST_COLUMN_LABEL' => 
  array (
    'en' => '',
    'ru' => 'Дата',
  ),
  'LIST_FILTER_LABEL' => 
  array (
    'en' => '',
    'ru' => 'Дата',
  ),
  'ERROR_MESSAGE' => 
  array (
    'en' => '',
    'ru' => 'Дата',
  ),
  'HELP_MESSAGE' => 
  array (
    'en' => '',
    'ru' => 'Дата',
  ),
));
            $helper->Hlblock()->saveField($hlblockId, array (
  'FIELD_NAME' => 'UF_PLAN',
  'USER_TYPE_ID' => 'double',
  'XML_ID' => '',
  'SORT' => '100',
  'MULTIPLE' => 'N',
  'MANDATORY' => 'N',
  'SHOW_FILTER' => 'N',
  'SHOW_IN_LIST' => 'Y',
  'EDIT_IN_LIST' => 'Y',
  'IS_SEARCHABLE' => 'N',
  'SETTINGS' => 
  array (
    'PRECISION' => 4,
    'SIZE' => 20,
    'MIN_VALUE' => 0.0,
    'MAX_VALUE' => 0.0,
    'DEFAULT_VALUE' => NULL,
  ),
  'EDIT_FORM_LABEL' => 
  array (
    'en' => '',
    'ru' => 'План',
  ),
  'LIST_COLUMN_LABEL' => 
  array (
    'en' => '',
    'ru' => 'План',
  ),
  'LIST_FILTER_LABEL' => 
  array (
    'en' => '',
    'ru' => 'План',
  ),
  'ERROR_MESSAGE' => 
  array (
    'en' => '',
    'ru' => 'План',
  ),
  'HELP_MESSAGE' => 
  array (
    'en' => '',
    'ru' => 'План',
  ),
));
            $helper->Hlblock()->saveField($hlblockId, array (
  'FIELD_NAME' => 'UF_FACT',
  'USER_TYPE_ID' => 'double',
  'XML_ID' => '',
  'SORT' => '100',
  'MULTIPLE' => 'N',
  'MANDATORY' => 'N',
  'SHOW_FILTER' => 'N',
  'SHOW_IN_LIST' => 'Y',
  'EDIT_IN_LIST' => 'Y',
  'IS_SEARCHABLE' => 'N',
  'SETTINGS' => 
  array (
    'PRECISION' => 4,
    'SIZE' => 20,
    'MIN_VALUE' => 0.0,
    'MAX_VALUE' => 0.0,
    'DEFAULT_VALUE' => NULL,
  ),
  'EDIT_FORM_LABEL' => 
  array (
    'en' => '',
    'ru' => 'Факт',
  ),
  'LIST_COLUMN_LABEL' => 
  array (
    'en' => '',
    'ru' => 'Факт',
  ),
  'LIST_FILTER_LABEL' => 
  array (
    'en' => '',
    'ru' => 'Факт',
  ),
  'ERROR_MESSAGE' => 
  array (
    'en' => '',
    'ru' => 'Факт',
  ),
  'HELP_MESSAGE' => 
  array (
    'en' => '',
    'ru' => 'Факт',
  ),
));
        }

    public function down()
    {
        //your code ...
    }
}
