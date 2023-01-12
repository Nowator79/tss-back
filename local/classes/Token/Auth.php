<?php

declare(strict_types=1);

namespace Godra\Api\Token;

use Godra\Api\Helpers\Utility\Misc;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Route;

class Auth extends Base
{
    /**
     * @param  Request  $request
     * @param  Response  $response
     * @param  Route  $next
     *
     * @return ResponseInterface
     * @throws AuthException
     */

    public function login()
    {
        if($_SERVER['HTTP_AUTHORIZATION'])
            $decoded = $this->decodedHeader($_SERVER['HTTP_AUTHORIZATION']);
        else {
            global $API_ERRORS;
            $API_ERRORS[] = 'Нет токена авторизации';
        }

        $decodet['user_type'] = 'superuser';

        return $decoded;
    }
}
