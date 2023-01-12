<?
namespace Godra\Api\Routing;

class AdditionalRoute
{
    public function getRoutes()
    {
        return 
        [
            /* личный кабинет */
            
            // GET, личный кабинет, страница "Личные данные"
            '/api/page/profile' => [\Godra\Api\Page\Profile::class, 'get'],
            
            // POST, личный кабинет, страница "Личные данные", обновление данных пользователя
            '/api/user/update' => [\Godra\Api\User::class, 'update'],
            
            // GET, личный кабинет, страница "Личные данные", список уведомлений с постраничной навигацией
            '/api/notification/list' => [\Godra\Api\Notification::class, 'list'],
            
            // POST, личный кабинет, страница "Личные данные", вывставление флага прочитанности уведомлений
            '/api/notification/markAsRead' => [\Godra\Api\Notification::class, 'markAsRead'],
            
            // GET, личный кабинет, список заказов
            '/api/order/list' => [\Godra\Api\Order::class, 'list'],
            
            // GET, личный кабинет, страница заказа
            '/api/order/get' => [\Godra\Api\Order::class, 'get'],
            
            // POST, личный кабинет, страница заказа, повторить заказ
            '/api/order/repeat' => [\Godra\Api\Order::class, 'repeat'],
            
            // POST, личный кабинет, страница заказа, удалить заказ
            '/api/order/delete' => [\Godra\Api\Order::class, 'delete'],
            
            // GET, личный кабинет, страница информации о контрагенте
            '/api/contragent/get' => [\Godra\Api\Contragent::class, 'get'],
            
            // GET, личный кабинет, страница управления пользователями, список
            '/api/usermanagement/list' => [\Godra\Api\UserManagement::class, 'list'],
            
            // POST, личный кабинет, страница управления пользователями, включение/выключение пользователя
            '/api/usermanagement/switch' => [\Godra\Api\UserManagement::class, 'switch'],
            
            // POST, личный кабинет, страница управления пользователями, редактирование пользователя
            '/api/usermanagement/update' => [\Godra\Api\UserManagement::class, 'update'],
            
            // POST, личный кабинет, страница управления пользователями, добавление пользователя
            '/api/usermanagement/add' => [\Godra\Api\UserManagement::class, 'add'],
            
            // GET, личный кабинет, страница списка торговых точек
            '/api/outlets/list' => [\Godra\Api\Outlets::class, 'list'],
            
            // GET, личный кабинет, страница списка торговых точек
            '/api/documents/list' => [\Godra\Api\Documents::class, 'list'],
            
            // GET, личный кабинет, страница списка торговых точек, скачивание документа
            '/api/documents/download' => [\Godra\Api\Documents::class, 'download'],
            
            /* публичная часть */
            
            // GET, публичная часть, список торговых точек
            '/api/outlets/all' => [\Godra\Api\Outlets::class, 'all'],
            
            // POST, публичная часть, сохранение торговой точки и договора
            '/api/outlets/set' => [\Godra\Api\Outlets::class, 'set'],
            
            // GET, публичная часть, поиск в шапке
            '/api/search/header' => [\Godra\Api\Search::class, 'header'],
			
			// запрос стоимости товара
			'/api/catalog/getProductPrice' => [\Godra\Api\Catalog::class, 'getProductPrice'],
        ];
    }
}
