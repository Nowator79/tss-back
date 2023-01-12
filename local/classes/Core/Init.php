<?
namespace Godra\Api\Core;

use Bitrix\Main\Application,
    Godra\Api\Routing\Route,
    Godra\Api\Core\Events,
    Godra\Api\Helpers\Utility\Misc,
    Godra\Api\Helpers\Utility\Errors,
    Godra\Api\Helpers\Auth\Authorisation;

class Init
{
    public static function run()
    {
        // переменная ошибок апи
        global $API_ERRORS, $apiErrorStatusCode;
        
        header('Access-Control-Allow-Origin: '.($_SERVER['HTTP_ORIGIN'] ? $_SERVER['HTTP_ORIGIN'] : '*'));
        
        // толку от это нет, так как при подключении ядра битрикса cors полностью ломается, нужно выводить заголовки до подключения битрикса, поэтому вынесено в htaccess
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS')
        {
            header('HTTP/2 200 OK');
            exit;
        }

        // Инициализация событий
        Events::init();
        
        if (php_sapi_name() != 'cli') 
        {
            //(new Authorisation)->preAuthByCookieHash();
            (new \Godra\Api\Helpers\Auth\Bitrix)->authByToken();
        }
        
        $requestPage = Application::getInstance()->getContext()->getRequest()->getRequestedPage();

        if (Misc::checkRequestPage($requestPage))
        {
            $object_arr = (new Route())->toMethod($requestPage);
            $method = $object_arr[1];
            
            \class_exists($object_arr[0]) ?
                $result = (new $object_arr[0]())->$method():
                $API_ERRORS[] = 'Метод не существует';

            $is_err = count($API_ERRORS);

            // Заголовки для отдачи Json
            Misc::setHeaders('json');
            //Misc::setHeaders('mandatory');
            
            //$is_err ? Misc::setHeaders('500') : Misc::setHeaders('200');
            
            $statusCode = 200;

            if ($is_err)
            {
                if ($apiErrorStatusCode)
                {
                    $statusCode = $apiErrorStatusCode;
                }
                else
                {
                    $statusCode = 500;
                }
            }
            
            Misc::setHeaders($statusCode);

            if ($method == 'getMap')
            {
                Misc::setHeaders('200');
                echo \json_encode($result ?: ['status' => 0, 'errors' => $API_ERRORS], JSON_UNESCAPED_UNICODE);
            }
            else
            {
                echo \json_encode($is_err ? ['status' => 0, 'errors' => $API_ERRORS] : $result, JSON_UNESCAPED_UNICODE);
            }

            die();
        }
    }
}
?>