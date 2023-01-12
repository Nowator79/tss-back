<?
namespace Godra\Api;

class Catalog
{
    public function getProductPrice()
    {
		global $API_ERRORS, $apiErrorStatusCode;
		
		$result = [];
		
		$status = 0;
		
		$data = Helpers\Utility\Misc::getPostDataFromJson();
		
        $resultApi = (new Helpers\Catalog)->getProductPrice($data['email'], $data['name'], $data['phone'], $data['code'], $data['comment']);
		
		if ($resultApi['status'])
		{
			$status = 1;
		}
		else
		{
			if ($resultApi['errorText'])
			{
				$API_ERRORS[] = $resultApi['errorText'];
			}
			else
			{
				$API_ERRORS[] = 'Не заполнены обязательные поля';
			}

			$apiErrorStatusCode = 422;
		}
		
		$result['status'] = $status;
		
		return $result;
    }
}
