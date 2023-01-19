<?

// файлы, где надо править работу со справочниками, после выгрузки реальных справочников из 1С

// Helpers/Outlets.php
// Helpers/Contagent.php
// Helpers/Contract.php
// Helpers/Documents.php
// Helpers/Nomenclature.php

// каталог товаров
define('IBLOCK_CATALOG', 5);

// акции
define('IBLOCK_SALES', 3);

# Имя таблицы highload-блока торговых точек
define('HIGHLOAD_BLOCK_OUTLETS', 'torgovaya_tochka'); // fake

# Имя таблицы highload-блока контрагентов
define('HIGHLOAD_BLOCK_CONTRAGENT', 'kontragent'); //fake

# Имя таблицы highload-блока договоров
define('HIGHLOAD_BLOCK_CONTRACT', 'dogovora'); //fake

# Имя таблицы highload-блока документов
define('HIGHLOAD_BLOCK_DOCUMENTS', 'documents'); //fake

# Имя сущности highload-блока документов
define('HIGHLOAD_BLOCK_DOCUMENTS_ENTITY', 'Documents'); //fake

# Имя таблицы highload-блока документов
define('HIGHLOAD_BLOCK_NOMENCLATURE', 'assortiment'); //fake

# Имя сущности highload-блока контрагентов
define('HIGHLOAD_BLOCK_CONTRAGENT_ENTITY_PRODUCTION', 'Kontragenty'); //real // пока используется только в одном месте, при импорте пользователей

# Идентификатор свойства акций
define('PROPERTY_SALES_ID', 57);

# Идентификатор свойства хиты
define('PROPERTY_POPULAR_ID', 58);

# Идентификатор свойства новинки
define('PROPERTY_NEW_ID', 59);

# Идентификатор свойства фасовка
define('PROPERTY_PACK_ID', 60);

# Идентификатор свойства упаковка
define('PROPERTY_PACKAGE_ID', 61);

# Идентификатор свойства сертификация
define('PROPERTY_CERTIFICATION_ID', 69);

# Форма запроса стоимости товара
define('REQUEST_PRICE_FORM_SID', 'REQUEST_PRICE_FORM');

# ID типов цен
define('PRICE_TYPE_IDS', [496,510]);
