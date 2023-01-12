<?
namespace Godra\Api\Orders;

class Get extends Base
{
    /**
     * Отдаётся при /api/map
     * @var array
     */
    protected static $row_data = [];


    /**
     * Получить список товаров в корзине
     */
    public function get()
    {
        return $this->getOrders();
    }
}
?>