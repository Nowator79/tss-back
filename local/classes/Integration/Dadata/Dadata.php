<?php

namespace Godra\Api\Integration\Dadata;

use \GuzzleHttp\Client as HttpClient;

class Dadata
{
    private $client;

    public function __construct()
    {
        $this->client = new HttpClient([
            "headers" => [
                "Authorization" => sprintf("Token %s", DADATA_AUTH_TOKEN),
            ],
        ]);
    }

    public function findDataByInn(string $itn, bool $isMain = false): DadataAnswerLegal
    {
        $arJson = [
            "query" => $itn,
        ];

        if ($isMain) {
            $arJson["branch_type"] = "MAIN";
        }

        $result = $this->client->post(
            "https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party", [
                "json" => $arJson,
            ]);

        $data = json_decode($result->getBody()->getContents(), true);
        return new DadataAnswerLegal($data);
    }

    public function suggestsByInn(string $itn): DadataAnswerLegal {
        $result = $this->client->post(
            "https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party", [
            "json" => [
                "query" => $itn,
            ],
        ]);

        $data = json_decode($result->getBody()->getContents(), true);
        return new DadataAnswerLegal($data);
    }

    public function findDataByIp(string $ip): DadataAnswerLegal {
        $result = $this->client->post(
            "https://suggestions.dadata.ru/suggestions/api/4_1/rs/iplocate/address", [
            "json" => [
                "ip" => $ip,
            ],
        ]);

        $data = json_decode($result->getBody()->getContents(), true);
        return new DadataAnswerLegal($data);
    }

    public function findDataByAddress(?string $address = ''): DadataAnswerAddress
    {   
        if(!$address)
            return new DadataAnswerAddress([]);

        $result = $this->client->post(
            "https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/address", [
                "json" => [
                    "query" => $address,
                ],
            ]);

        $data = json_decode($result->getBody()->getContents(), true);
        return new DadataAnswerAddress($data);
    }
}
