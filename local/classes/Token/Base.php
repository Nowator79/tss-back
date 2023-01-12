<?php

declare(strict_types=1);

namespace Godra\Api\Token;

use Bitrix\Main\Web\JWT;

abstract class Base
{
    function __construct()
    {
        global $API_ERRORS;
        $this->errors = &$API_ERRORS;
    }

    /**
     * @param  string  $jwtHeader
     */
    protected function decodedHeader(string $jwtHeader)
    {
        if(!$jwtHeader)
            $this->errors[] = "Где твой токен";

        $jwt = explode('Bearer ', $jwtHeader);

        if(!isset($jwt[1]))
            $this->errors[] = "Код токена не забыл?";


        return $this->checkToken($jwt[1]);
    }

    /**
     * @param  string  $token
     */
    protected function checkToken(string $token)
    {
        try
        {
            $decoded = JWT::decode($token, TOKEN_SECRET_KEY, ['HS256']);
        } catch (\Throwable $th)
        {
            $this->errors[] = 'token not correct';
        }

        if(is_object($decoded) && isset($decoded->sub))
            return $decoded;

        $this->errors[] = "Чет не так с токеном";
    }

    /**
     * Get user id from request header
     *
     * @param  Request  $request
     */
    public function getUserId($request)
    {
        $userId = 0;
        $jwtHeader = $_SERVER['HTTP_AUTHORIZATION'];

        if($jwtHeader)
        {
            $decoded = $this->decodedHeader($jwtHeader);

            if(is_object($decoded) && $decoded->sub !== null)
                $userId = (int)$decoded->sub;
        }

        return $userId;
    }
}
