<?
namespace Godra\Api\Page;

use Godra\Api\Helpers\Utility\Misc;

class Stors
{
    /**
     * Получение списка доступных служб доставок с учетом настроенных ограничений
     *
     * @return array
     */
    public function getStors()
    {
        \CModule::IncludeModule("sale");
        $params = Misc::getPostDataFromJson();

        $filter = ($params['limit'] == 'full') ? ['ACTIVE' => 'Y'] : ['ACTIVE' => 'Y', '!TRACKING_PARAMS' => false];

        $result = \Bitrix\Sale\Delivery\Services\Table::getList(
            [
                'filter' => $filter,
            ]
        );

        return $result->fetchAll();
    }
}