<?
namespace Godra\Api\Helpers\Utility;

use CUserFieldEnum,
    Bitrix\Main\Application,
    Bitrix\Main\HttpRequest,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Highloadblock as HL,
	CIBlockPropertyEnum;

class Misc {

    /**
     * Проверяет текущий путь, api запрос это или нет
     *
     * @param string $requestPage путь к текущей странице
     * @return void
     */
    public static function checkRequestPage($requestPage)
    {
        return (strpos($requestPage, 'api/') != false);
    }


    /**
     * Прописать заголовки
     *
     * @param string $type  тип заголовка json/200/mandatory..
     * @return void
     */
    public static function setHeaders($type)
    {
        if($type == 'json')
            header('content-type: application/json');

        if($type == '200')
        {
            header('HTTP/2 200 OK');
            header('Status: 200 OK');
        }

        if($type == '500')
        {
            header('HTTP/2 500 OK');
            header('Status: 500 error');
        }
        
        // это не работает в битриксе
        /*
        if($type == 'mandatory')
        {
            //header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Credentials: true');
            header("Access-Control-Allow-Methods: *");
            header("Access-Control-Allow-Headers: *");
        }
        */
        
        if ($type == 401)
        {
            header('HTTP/2 401 Unauthorized');
        }
        
        if ($type == 422)
        {
            header('HTTP/2 422 Unprocessable Entity');
        }
        
        if ($type == 405)
        {
            header('HTTP/2 405 Method Not Allowed');
        }
        
        if ($type == 403)
        {
            header('HTTP/2 403 Forbidden');
        }
    }

    /**
     * Получить данные из запроса Json
     *
     * @return array
     */
    public static function getPostDataFromJson()
    {
        return \json_decode(HttpRequest::getInput(), true);
    }

    /**
     * делает простую сверку полей приходящих
     * по post с теми которые мы передадим
     * @param array $post_data ['code'=>'123', 'name' => 'admin'...]
     * @param array $rows ['code', 'admin' ..]
     * @param array $errors массив куда складывать ошибки
     * @deprecated v1
     */
    public static function checkRows($post_data, $rows, &$errors = [])
    {
        foreach ($rows as $row)
            if(!$post_data)
                $errors[] = 'Не указано поле '.$row;
    }

    /**
     * делает простую сверку полей приходящих
     * по post с теми которые мы передадим
     * @param array $post_data
     * @param array $row_data ['code', 'admin' ..]
     */
    public static function checkRowsV2($post_data, $row_data)
    {
        global $API_ERRORS;

        foreach ($row_data as $name => $row)
        {
            if($row['mandatory'] AND !$post_data[$name])
                $API_ERRORS[] = 'Не передано поле '.$name.'; Для уточнения данных по полю, вызовите метод /api/map с параметром url текущего метода.';
        }

    }

    /**
     * массив в json
     * @param array $php_array
     * @return string
     */
    public static function phpToJson($php_array)
    {
        return json_decode($php_array);
    }


    /**
     * Подключение модулей
     *
     * @param array $modules список модулей в массиве
     */
    public static function includeModules(array $modules)
    {
        foreach ($modules as $module)
            if(!\Bitrix\Main\Loader::includeModule($module))
                throw new \Exception("Не удалось подключить модуль $module", 1);
    }

    /**
     * Форматирование цен
     *
     * @param mixed $price  цена
     */
    public static function priceFormat($price)
    {
        $price = number_format($price, 2, ',', ' ');
        $price = preg_replace('#[,]00#', '', $price);

		return $price;
    }

    /**
     * Сортировка массива по полю
     *
     * @param array $arr массив с данными
     * @param string $fieldName поле для сортировки
     * @param boolean $desc направление сортировки
     * @return void
     */
	public static function aSortByField(array &$arr, string $fieldName, $desc = false)
    {
		uasort($arr, function ($a, $b) use ($fieldName, $desc) {
			return ($a[$fieldName] <=> $b[$fieldName]) * ($desc ? -1 : 1);
		});
	}

    /**
     * Очистка массива от пустых значений
     *
     * @param array $array
     * @return array
     */
    public static function clearArrayOfEmptyValues(array $array): array
    {
        return array_diff($array, [null, false, "", " "]);
    }

    /**
     * Implode() с удалением пустых значений из массива строк
     *
     * @param array $strings массив строк
     * @param string $glue разделитель, по умолчанию пробел
     * @return string
     */
    public static function getJoinString(array $strings, string $glue = " "): string
    {
	    return implode($glue, self::clearArrayOfEmptyValues($strings));
    }

    /**
     * Генератор паролей
     *
     * @param integer $length длина пароля
     * @return string
     */
    public static function generatePassword(int $length = 10): string
    {
        $alphabet = implode("", range("a", "z"));
        $alphabet .= strtoupper($alphabet);
        $alphabet .= "0123456789!@#\$%&*()-_=";

        $alphabetLength = strlen($alphabet);
        $password = "";

        for ($i = 0; $i < $length; $i++)
            $password .= $alphabet[mt_rand(0, $alphabetLength - 1)];

        return $password;
    }

    /**
     * Подключить файл
     *
     * @param string $relativePath // путь
     */
    public static function inlineFile(string $relativePath): void
    {
	    include Application::getDocumentRoot() . $relativePath;
    }

    /**
     * BugTrace
     *
     * @param [type] $var
     * @param boolean $stop
     * @param boolean $inconsole
     * @param boolean $UID
     */
    public static function pre($var,$stop=false,$inconsole = false, $UID = false)
    {
        $bt         = debug_backtrace()[0];
        $dRoot      = str_replace("/", "\\", $_SERVER["DOCUMENT_ROOT"]);
        $bt["file"] = str_replace($dRoot, "", $bt["file"]);
        $dRoot      = str_replace("\\", "/", $dRoot);
        $bt["file"] = str_replace($dRoot, "", $bt["file"]);

        if ($GLOBALS['USER']->IsAdmin())
        {
            if($UID && intval($UID)!==$GLOBALS['USER']->GetID()) return;

            if($inconsole)
            {
                echo "<script>console.log('File: ".$bt['file']." [".$bt['line']."]');console.log(".json_encode($var).");</script>";
            }
            else
            {
                echo '<div style="padding:3px 5px; background:#99CCFF; font-weight:bold;">File: '.$bt["file"].' ['.$bt["line"].']</div>';

                echo '<pre>';
                ((is_array($var) || is_object($var)) ? print_r($var) : var_dump($var));
                echo '</pre>';
            }

            if($stop) exit(0);
        }
    }

    /**
     * Получить свойства каталога, отдаёт массив вида
     * * [ ['NAME' => имя, 'ID' => id], ..  ]
     * @return array|void
     */
    public static function getCatalogProperties()
    {
        \Bitrix\Main\Loader::IncludeModule('catalog');

        return \Bitrix\Iblock\PropertyTable::getList([
            'filter' => [
                'IBLOCK_ID' => \Bitrix\Iblock\IblockTable::getList([
                    'filter' => ['CODE' => IBLOCK_CATALOG_API]
                    ])->fetch()['ID']
            ],
            'select' => ['NAME', 'ID'],
            'order'  => ['NAME' => 'asc'],
        ])->fetchAll();
    }

    public static function getMeasureCooficientByProductId($id)
    {
        return \Godra\Api\Iblock\IblockElementPropertyTable::getList([
            'filter' => [
                'IBLOCK_ELEMENT_ID' => $id,
                'IBLOCK_PROPERTY_ID' => MEASURE_PROPERTY_ID
            ],
            'select' => ['VALUE', 'DESCRIPTION'],
        ])->fetch()['DESCRIPTION'];
    }
    
    public function getHLData($tableName, $filter = [], $order = [], $xmlIdKey = false, $offset = 0, $limit = 0, $isCountTotal = false)
	{
		$result = $records = [];
		
		if ($tableName && \Bitrix\Main\Loader::includeModule('highloadblock'))
		{
			$hlblock = HL\HighloadBlockTable::getRow(
			[
				'filter' => 
				[
					'=TABLE_NAME' => $tableName
				]
			]);
			
			if ($hlblock)
			{
				$entity = HL\HighloadBlockTable::compileEntity($hlblock);
				
				$entityClass = $entity->getDataClass();

				$arRecords = $entityClass::getList(
					[
                        'count_total' => true,
						'filter'      => $filter,
                        'order'       => $order,
                        'offset'      => $offset,
                        'limit'       => $limit,
					]
				);

				foreach ($arRecords as $record)
				{
					$records[($xmlIdKey ? $record['UF_XML_ID'] : $record['ID'])] = $record;
				}
			}
		}
        
        $result['records'] = $records;
        
        if ($isCountTotal)
        {
            $result['total'] = $arRecords->getCount();
        }
		
		return $result;
	}
    
    public function getValuesByUserFieldId($userFieldId)
    {
        $result = [];
        
        if ($userFieldId)
        {
            $res = CUserFieldEnum::GetList([], ['USER_FIELD_ID' => $userFieldId]);
            
            while ($arEnum = $res->Fetch())
            {
                $result[$arEnum['ID']] = $arEnum['VALUE'];
            }
        }
        
        return $result;
    }
    
    public static function numberFormat($val)
    {
        $result = $val;
        
        if ($val)
        {
            $result = number_format($val, 2, '.', ' ');
            
            $result = preg_replace('#[.]00#', '', $result);
        }
        
        return $result;
    }
	
	public function getListValues($propertyId)
	{
		$result = [];
		
		if ($propertyId)
		{		
			$propertyEnums = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $propertyId]);
			
			while ($fields = $propertyEnums->GetNext())
			{
				$result[$fields['ID']] = $fields['VALUE'];
			}
		}
		
		return $result;
	}
}