<?

namespace Godra\Api\Helpers;

class Filter
{
    public function getLabels()
    {
        $result = [];
		
		$utils = new Utility\Misc();
        
		// акции
        $sales = $utils->getListValues(PROPERTY_SALES_ID);
		
		// хиты
		$popular = $utils->getListValues(PROPERTY_POPULAR_ID);
		
		// новинки
		// тип данных - простой чекбокс
		
		// фасовка
		$pack = $utils->getListValues(PROPERTY_PACK_ID);
		
		// упаковка
		$package = $utils->getListValues(PROPERTY_PACKAGE_ID);
		
		// сертификация
		$certification = $utils->getListValues(PROPERTY_CERTIFICATION_ID);
		
		if ($sales)
		{
			foreach ($sales as $key => $value)
			{
				$result[PROPERTY_SALES_ID.':'.$key] = $value;
			}
		}
		
		if ($popular)
		{
			foreach ($popular as $key => $value)
			{
				$result[PROPERTY_POPULAR_ID.':'.$key] = $value;
			}
		}
		
		if ($pack)
		{
			foreach ($pack as $key => $value)
			{
				$result[PROPERTY_PACK_ID.':'.$key] = $value;
			}
		}
		
		if ($package)
		{
			foreach ($package as $key => $value)
			{
				$result[PROPERTY_PACKAGE_ID.':'.$key] = $value;
			}
		}
		
		if ($certification)
		{
			foreach ($certification as $key => $value)
			{
				$result[PROPERTY_CERTIFICATION_ID.':'.$key] = $value;
			}
		}
        
		$result[PROPERTY_NEW_ID.':Y'] = 'Новинка'; // Y - простой чекбокс строка (сейчас в каталоге много дублей полей с одинаковыми названиями и никто не знает какие из них актуальные)
		
		// update
		// фронтенд-разработчик попросил переделать в массив с другой структурой для удобства работы на js
		$tmp = [];
		
		foreach ($result as $key => $val)
		{
			$tmp[] = 
			[
				'name' => $val,
				'id'   => $key,
			];			
		}
		
		$result = $tmp;
		//
		
        return $result;
    }
}
