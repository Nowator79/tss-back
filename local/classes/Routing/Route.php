<?
namespace Godra\Api\Routing;

use \Godra\Api,
    \Godra\Api\Token,
    \Godra\Api\Services,
    \Godra\Api\Helpers\Auth,
    \Bitrix\Main\Application,
    \Godra\Api\HighloadBlock\Menus,
    \Godra\Api\HighloadBlock\BreadCrumbs,
    \Godra\Api\Helpers\Utility\Misc;


class Route
{
    protected $row_data = [
        'url'
    ];

    /**
     * массив методов [сатитк класс , метод]
     *
     * @var array
     */
    protected $methods = [

        /** Отдавать форму для кнопки на баннере */
        /** Реализовать меню footer в highblock "все меню" */
        /** Реализовать иконки black и Green для разделов с помощью uf_ */

        // даёт информацию по ожидаемым полям, принимает url метода
        '/api/map' => [Route::class, 'getMap'],

        // ТЕСТ
        '/api/token' => [Token\Auth::class, 'login'],

        // Отдаёт меню, нужен code
        // есть возможность собирать список разделов указав один пункт меню и в урл забив api code инфоблока
        '/api/menu' => [Menus::class, 'get'],

        // Отдаёт хлебные крошки по параметру url
        '/api/breadcrumbs' => [BreadCrumbs::class, 'get'],

        // авторизация по логин/пароль
        '/api/auth' => [Auth\Authorisation::class, 'authByPassword'],

        // проверка авторизации
        '/api/isauth' => [Auth\Authorisation::class, 'isAuth'],

        // регистрация по форме
        '/api/register' => [Auth\Registration::class, 'registerByForm'],

        // Форма обратной связи
        '/api/callback_form' => [Services\PrepareFormStatic::class, 'addResult'],

        // Логаут
        '/api/logout' => [Auth\Logout::class, 'logoutSelfUser'],//


        // Восстановить пароль |шаг 1| для получения проверочного кода
        '/api/restore_password' => [Auth\Restore::class, 'forEmailOrPhone'],

        // поменять пароль |шаг 2| используя код из шага 1
        '/api/change_password' => [Auth\Restore::class, 'changePassword'],


        # новое восстановление пароля #
        // первый шаг - отправка логина
        '/api/recovery/sendLogin' => [Auth\Restore::class, 'sendLogin'],

        // второй шаг - отправка кода
        '/api/recovery/sendCode' => [Auth\Restore::class, 'sendCode'],

        // третий шаг - отправка пароля
        '/api/recovery/sendPassword' => [Auth\Restore::class, 'sendPassword'],

        # новое восстановление пароля #


        // получить контакты
        '/api/get_contacts' => [Api\Information\Contacts::class, 'getList'],

        // получить баннера
        '/api/banners/get' => [Api\Iblock\Banners::class, 'getList'],

        // получить Акции
        '/api/stock/get' => [Api\Iblock\Stock::class, 'getList'],

        # дополнительные методы
        // получить детальную страницу акции
        '/api/stock/getByCode' => [Api\Iblock\Stock::class, 'getByCode'],
        # дополнительные методы

        #----------------  Каталог START -------------------------#
            # получить Товары
                // надо описать логику работы с ценами
                // логика фильтра товаров (хз как делать)
            //'/api/catalog/get' => [Api\Catalog\Element::class, 'getList'],

            // получить товар
            '/api/catalog/getProduct' => [Api\Catalog\Element::class, 'getProduct'],

            // тестовый метод, потом удалить
            // getDetailStocksData
            '/api/catalog/getDetailStocksData' => [Api\Catalog\Element::class, 'getDetailStocksData'],

            # получить кол-во, метод как get, только отдаёт не элементы , а их кол-во
            '/api/catalog/getсount' => [Api\Catalog\Element::class, 'getCount'],

            // Получить список просмотренных товаров // параметры не нужны
            '/api/catalog/getviewed' => [Api\Catalog\Element::class, 'getViewed'],

            # получить Пузыри
            '/api/catalog/buble' => [Api\Catalog\Bubble::class, 'getList'],

            # дополнительные методы
            // получить разделы каталога первого уровня
            '/api/catalog/getFirstLevelSections' => [Api\Catalog\Element::class, 'getFirstLevelSections'],

            // получить популярные предложения -  разделы, подразделы
            '/api/catalog/getPopularSections' => [Api\Catalog\Element::class, 'getPopularSections'],

            // получить элементы раздела каталога 1 и 2 уровня вложенности
            '/api/catalog/getSectionElements' => [Api\Catalog\Element::class, 'getSectionElements'],

            # дополнительные методы
            # Получить данные фильтра для текущего состояния структуры каталога
            '/api/catalog/filter/get' => [Api\Catalog\Filter::class, 'getData'],

            # Получить данные свойств фильтра для текущего состояния структуры каталога
            '/api/catalog/filter/getFilterProperty' => [Api\Catalog\Filter::class, 'getFilterProperty'],
        #----------------  Каталог END ---------------------------#


        #----------------  Корзина START -------------------------#
            # добавить товар
            '/api/basket/add'    => [Api\Basket\AddProduct::class, 'byId'],
            # удалить товар
            '/api/basket/delete' => [Api\Basket\DeleteProduct::class, 'byId'],
            # получить товары в корзине
            '/api/basket/get' => [Api\Basket\Helper::class, 'getBasketItems'],
            # уменьшить кол-во товара
            '/api/basket/remove' => [Api\Basket\RemoveProduct::class, 'byId'],
            # получить кол-во товаров в корзине
            '/api/basket/count' => [Api\Basket\Helper::class, 'getQuantityList'],

            // обновление корзины (согласно выбранному договору)
            '/api/basket/update' => [Api\Basket\Update::class, 'update'],
            
            // удалить все товары из корзины
            '/api/basket/clear' => [Api\Basket\Helper::class, 'clear'],

            # Создать заказ
            # надо допилить оплату и доставку
            # передать даты доставки от пользователя
            '/api/order/add' => [Api\Basket\Order::class, 'add'],

            # получить список заказов
            # надо допилить получение состава заказов + допил оплаты и доставки
            '/api/orders/get' => [Api\Orders\Get::class, 'get'],
        #----------------  Каталог END ---------------------------#


        #----------------  Пользователи START --------------------#
            # Создать пользователя
                # Добавить проверку владения точкой
                # Добавить проверку на суперпользователя
            '/api/users/add' => [Api\User\Add::class, 'addUser'],

            # Редактировать пользователя
                # Добавить проверку владения точкой
                # Добавить проверку на суперпользователя
            '/api/users/update' => [Api\User\Update::class, 'updateUser'],

            # Редактировать пользователя
                # Добавить проверку владения точкой
                # Добавить проверку на суперпользователя
            '/api/users/delete' => [Api\User\Delete::class, 'deleteUser'],

            # Редактировать пользователя
                # Добавить проверку владения точкой
                # Добавить проверку на суперпользователя
                # Добавить выборку пользователей по контрагенту суперпользователя
            '/api/users/get' => [Api\User\Get::class, 'GetUsers'],
        #----------------  Пользователи END ----------------------#

        #---------------- Конфигурвтор ---------------------------#
            # Получиь основной товар
            '/api/builder/get' => [Api\SetsBuilder\Builder::class, 'getMainProduct'],
            # Получить товары входящие в слот
            '/api/builder/getOptions' => [Api\SetsBuilder\Builder::class, 'getOptions'],
        #---------------- Конфигруатор END -----------------------#



        #---------------- Тест -----------------------------------#
            '/api/base/test' => [Api\Test\Base::class, 'Test'],
        #---------------- Тест END -------------------------------#



        // получить дешборды
        '/api/dashboards/get' => [Api\Iblock\Dashboard::class, 'getList'],

        // Таб "Запросы"
        // Запрос "Вызвать торгового представителя"
        '/api/requests/callSalesRepresentative' => [Api\UserRequests::class, 'callSalesRepresentative'],

        // Запрос "Запрос акт сверки"
        '/api/requests/getAct' => [Api\UserRequests::class, 'getAct'],

        // Запрос "Запрос прайс-листа"
        '/api/requests/getPriceList' => [Api\UserRequests::class, 'getPriceList'],

        // Запрос "Написать директору"
        '/api/requests/writeDirector' => [Api\UserRequests::class, 'writeDirector'],

        // Получение договоров пользователя
        '/api/requests/getDealsNumbers' => [Api\UserRequests::class, 'getDealsNumbers']
    ];

    function __construct()
    {
        // вынесем в отдельный файл, чтобы не приходилось постоянно мёржить
        $this->methods = array_merge($this->methods, (new AdditionalRoute)->getRoutes());
        
        $this->data = Misc::getPostDataFromJson();
        $this->cur_page  = Application::getInstance()->getContext()->getRequest()->getRequestedPage();
    }

    /**
     * Получает url метода на входе
     * и показывает какие параметры ожидает метод
     *
     * @return array
     */
    public function getMap()
    {
        global $API_ERRORS;
        return $this->data['url'] == $this->cur_page ?
                    $this->row_data:
                    (
                        \method_exists($this->methods[$this->data['url']][0], 'getMap') ?
                            [
                                'map' => (new $this->methods[$this->data['url']][0])->getMap(),
                                'url' => $this->data['url']
                            ]:
                            $API_ERRORS[] = 'Метод ещё не реализован'
                    );
    }

    /**
     * Роутинг для методов
     *
     * @param string $link
     * @return void
     */
    public function toMethod($link)
    {
        if($this->methods[$link])
            return self::formatEventName($this->methods[$link]);
        else
            return ['errors' => 'Error, methods '.$link.' is undefined'];
    }

    /**
     * Метод хелпер для вызова метода из элементов массива [name::class, method]
     *
     * @param array $method_path_array [name::class, method]
     * @return string
     */
    protected static function formatEventName(array $method_path_array)
	{
		$strName = '';

        if(isset($method_path_array) AND is_array($method_path_array))
        {
            return $method_path_array;
            $strName .= (
                is_object($method_path_array[0]) ?
                    get_class($method_path_array[0]) :
                    $method_path_array[0]
                ).'::'.$method_path_array[1];
        }

		return $strName;
	}
}