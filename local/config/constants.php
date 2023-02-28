<?

require_once('additionalConstants.php');

# SID формы регистрации

use Bitrix\Main\Config\Option;

define('REGISTRATION_FORM_SID', 'REQUEST_TO_REGISTRATION');

# SID формы обратной связи
define('CALLBACK_FORM_SID', 'CALLBACK');

# Dadata api
define('DADATA_AUTH_TOKEN', '9ebf703e9023a9fcfdbdbde9f73a2bda6243e497');

# sms.ru api key
define('SMSRU_AUTH_TOKEN', '3F60DC5D-A2AC-CCF9-03F5-6B83074AD9B9');

# апи код инфоблока с баннерами
define('IBLOCK_BANNERS_API', 'banners');

# апи код инфоблока с баннерами
define('IBLOCK_CATALOG_API', 'catalog');

# апи код инфоблока с акциями
define('IBLOCK_STOCK_API', 'stock');

# апи код инфоблока с дешбордами (этапы регистрации, условия сотрудничества)
define('IBLOCK_DASHBOARD_API', 'dashboard');

# TABLE_NAME хайлоадблока с меню
define('HIGHLOAD_MENU_ID', 'menus');

/************** ТОКЕН **************** */

# Секретный код токена
define('TOKEN_SECRET_KEY', '231654Wrt');

# Время жизни токена / сек
define('TOKEN_EXPIRE_SEC', 3600);

/****************************************** */

# TABLE_NAME хайлоадблока с Хлебными крошками
define('HIGHLOAD_BREADCRUMBS_ID', 'breadcrumbs');

# TABLE_NAME хайлоадблока с Хлебными крошками
define('SEVEREN_AUTHORIZE_DATA', ['login' => 'Agrokomplex', 'password' => 'CEPP1Van']);

# Свойство с единицами измерения. множественное, value:упаковка Description: 5 (шт от базовой единицы)
define('MEASURE_PROPERTY_ID', Option::get('main', 'api_measures_property_code') ?: false);

# Идентификатор группы суперпользователей
define('SUPER_USER_GROUP', 6);

# Имя таблицы highload-блока уведомлений
define('HIGHLOAD_BLOCK_NOTIFICATION', 'b_hlbd_notifications');

# Идентификатор поля типов уведомлений
define('TYPES_FIELD_ID', 53);

# id highload-блока уведомлений
define('HIGHLOAD_BLOCK_NOTIFICATION_ID', 5);

# Идентификатор highload-блока "Контрагенты"
define('HIGHLOAD_KONTRAGENTS_ID', 60);

# Идентификатор highload-блока "Договора"
define('HIGHLOAD_DOGOVORA_ID', 6);

# Идентификатор highload-блока "График"
define('HIGHLOAD_SCHEDULE_ID', 10);

# Идентификатор highload-блока "Торговые точки"
define('HIGHLOAD_TRADE_POINTS_ID', 11);

# Идентификатор highload-блока "Распределительный центр"
define('HIGHLOAD_DISTRIBUTION_CENTER_ID', 9);

# Идентификатор highload-блока "Ассортименты"
define('HIGHLOAD_ASSORTIMENT_CENTER_ID', 12);

# Идентификатор highload-блока "План продаж"
define('HIGHLOAD_SALES_PLAN_ID', 59);

# Идентификатор highload-блока "Шифр товара"
define('HIGHLOAD_PRODUCT_CODE_ID', 61);

# Почтовое событие отправки данных пользователя на проверку

define('USER_DATA_CHANGE_EVENT', 'USER_DATA_CHANGE');