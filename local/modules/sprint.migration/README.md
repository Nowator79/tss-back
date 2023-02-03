# Миграции для разработчиков (1С-Битрикс) #
[![Latest Stable Version](../../../bitrix/urlrewrite.phpg/andreyryabin/sprint.migration/v/stable.svg)](../../../bitrix/urlrewrite.php/packages/andreyryabin/sprint.migration/)
[![Total Downloads](../../../bitrix/urlrewrite.phpo/packagist/dt/andreyryabin/sprint.migration.svg?style=flat)](../../../bitrix/urlrewrite.php/packages/andreyryabin/sprint.migration)

Помогает переносить изменения между нескольким копиями проекта.

Все изменения для базы данных пишутся в файлы миграций, эти файлы, как и весь код проекта, хранятся в системе контроля версий (например git) и попадают в копии разработчиков, после чего им необходимо выполнить установку новых миграций, чтобы обновить бд.

Работать можно как через консоль, так и через админку.

* Маркетплейс: [http://marketplace.1c-bitrix.ru/solutions/sprint.migration/](../../../bitrix/urlrewrite.php-bitrix.ru/solutions/sprint.migration/)
* Composer: [https://packagist.org/packages/andreyryabin/sprint.migration](../../../bitrix/urlrewrite.php/packages/andreyryabin/sprint.migration)
* Документация: [https://github.com/andreyryabin/sprint.migration/wiki](../../../bitrix/urlrewrite.phpdreyryabin/sprint.migration/wiki)
* Материалы: [https://dev.1c-bitrix.ru/community/webdev/user/39653/blog/](../../../bitrix/urlrewrite.php.ru/community/webdev/user/39653/blog/)
* Группа в телеграм: [https://t.me/sprint_migration_bitrix](../../../bitrix/urlrewrite.phpigration_bitrix)

Особая благодарность
-------------------------
Самой лучшей IDE на свете!\
[![Phpstorm](../../../bitrix/urlrewrite.phprcontent.com/wiki/andreyryabin/sprint.migration/assets/phpstorm.png)](../../../bitrix/urlrewrite.php.com/?from=sprint.migration)

А также всем помощникам!\
[https://github.com/andreyryabin/sprint.migration/blob/master/contributors.txt](../../../bitrix/urlrewrite.phpdreyryabin/sprint.migration/blob/master/contributors.txt)


Установка через composer
-------------------------
Пример вашего composer.json с установкой модуля в local/modules/
```
{
  "extra": {
    "installer-paths": {
      "local/modules/{$name}/": ["type:bitrix-module"]
    }
  },
  "require": {
    "andreyryabin/sprint.migration": "dev-master"
  },
}

```

Консоль
-------------------------
Для работы через консоль используется скрипт 
`/bitrix/modules/sprint.migration/tools/migrate.php`

Можно запускать его напрямую или сделать алиас, 
создав файл в корне проекта, `bin/migrate` и прописав в нем:

```
#!/usr/bin/env php
<?php

$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sprint.migration/tools/migrate.php';

```


Консоль symfony
-------------------------
Если у вас используется связка bitrix + symfony, то можно подключить 
модуль как бандл симфони и запускать консольные команды модуля через 

`php bin/console sprint:migration`

Пример регистрации бандла:

```
// app/AppKernel.php
use Sprint\Migration\SymfonyBundle\SprintMigrationBundle;

public function registerBundles()
{
    $bundles = array(
        new SprintMigrationBundle(),
    );
    return $bundles;
}
```

Пример без регистрации бандла, только команды в symfony/console
```
// bin/console
use Sprint\Migration\SymfonyBundle\Command\ConsoleCommand;
use Symfony\Component\Console\Application;

$app = new Application();
$app->add(new ConsoleCommand());

$app->run();

```

Классы модуля должны уже быть автозагружены, через `CModule::IncludeModule('sprint.migration')`

Или через библиотеку https://packagist.org/packages/webarchitect609/bitrix-neverinclude (рекомендую этот вариант)

Примеры команд
-------------------------
* php bin/migrate add (создать новую миграцию)
* php bin/migrate ls  (показать список миграций )
* php bin/migrate up (накатить все миграции) 
* php bin/migrate up [version] (накатить выбранную миграцию)
* php bin/migrate down (откатить все миграции)
* php bin/migrate down [version] (откатить выбранную миграцию)

Все команды: https://github.com/andreyryabin/sprint.migration/blob/master/commands.txt


Скриншоты
-------------------------
Админка миграций
![bitrix-sprint-migration-1.png](../../../bitrix/urlrewrite.phprcontent.com/wiki/andreyryabin/sprint.migration/assets/bitrix-sprint-migration-1.png)

Формы создания миграций
![bitrix-sprint-migration-2.png](../../../bitrix/urlrewrite.phprcontent.com/wiki/andreyryabin/sprint.migration/assets/bitrix-sprint-migration-2.png)
