<?
namespace Godra\Api\Basket;

use \Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\UserTable;
use \Godra\Api\Helpers\Auth\Authorisation;
use Godra\Api\User\Get;

class AddProduct extends Base
{
    /**
     * Отдаётся при /api/map
     * @var array
     */
    protected static $row_data = [
        'element_id' => [
            'mandatory' => true,
            'alias' => 'PRODUCT_ID',
            'description' => 'Ид товара'
        ],
        'quantity' => [
            'mandatory' => false,
            'alias' => 'QUANTITY',
            'default' => 1,
            'description' => 'Кол-во товара , по умолчанию 1'
        ],
        'measure_code' => [
            'mandatory' => false,
            'alias' => 'measure',
            'description' => 'Единица измерения , Символьный код единицы измерения'
        ],
    ];

    /**
     * Апи код информационного блока каталога
     * @var string
     */
    protected static $api_ib_code = IBLOCK_CATALOG_API;

    /**
     * Добавить товар в корзину по id
     */
    public function byId()
    {
		/*
        $headers = apache_request_headers();

        // определить id пользователя по токену
//        $decoded = Authorisation::getUserId($headers);
//        if (!isset($decoded['error'])) {
//            $tokenUserId = $decoded;
//        }
//
//        // определение id типа цена
//        $priceType = $tokenUserId ? self::getPriceType($tokenUserId) : false;

        // определить id пользователя по токену
        $decoded = Authorisation::getUserId($headers);
        if (!isset($decoded['error'])) {
            $tokenUserId = $decoded;
        } else {
            return ['error' => $decoded['error']];
        }

        // является ли суперпользователем
        if (Authorisation::isSuperUser($headers)) {
            $superUserId = $tokenUserId;
        } else {
            // искать суперпользователя для текущего пользователя
            $superUserXmlId = Get::getParentUserXmlId($tokenUserId);
            $superUserId = Get::getUserIdByXmlId($superUserXmlId);
        }

        // Идентификатор текущего договора
        $dealId = UserTable::getList([
            'filter' => [ 'ID' => $superUserId, 'ACTIVE' => 'Y' ],
            'select' => [ 'ID', 'UF_ID_DOGOVOR' ]
        ])->Fetch()['UF_ID_DOGOVOR'];

        $deal =  \Godra\Api\Catalog\Element::getDeal($dealId);
        $priceTypeXmlId = $deal['UF_IDTIPACEN'];
		*/
		
		$priceTypeXmlId = (new \Godra\Api\Helpers\Contract)->getPriceTypeByUserId(\Bitrix\Main\Engine\CurrentUser::get()->getId());

        // передавать id пользователя
        $this->addProductById(
            $this->post_data['element_id'],
            $this->post_data['measure_code'],
            $this->post_data['quantity'],
            $priceTypeXmlId
        );
    }
}
?>