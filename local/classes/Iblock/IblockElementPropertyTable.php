<?
namespace Godra\Api\Iblock;
use Bitrix\Main\Entity;

class IblockElementPropertyTable extends Entity\DataManager
{

	public static function getTableName()
	{
		return 'b_iblock_element_property';
	}

	public static function getMap()
	{
		$arr = [
			(new Entity\IntegerField(
				'IBLOCK_ELEMENT_ID',
				['primary' => true]
			)),

			(new Entity\IntegerField(
				'IBLOCK_PROPERTY_ID',
				['primary' => true]
			)),

			(new Entity\StringField(
				'ID',
				['required' => true]
			)),

			(new Entity\StringField(
				'VALUE',
				['required' => true]
			)),

			(new Entity\StringField(
				'VALUE_TYPE',
				['required' => true]
			)),

			(new Entity\StringField(
				'VALUE_ENUM',
				['required' => true]
			)),

			(new Entity\StringField(
				'DESCRIPTION',
				['required' => true]
			)),

			(new Entity\StringField(
				'DESCRIPTION',
				['required' => true]
			)),
		];

		return $arr;
	}
}
?>