<?php

namespace Godra\Api\Integration\Dadata;

use Godra\Api\Integration\Dadata\DadataAnswer;

class DadataAnswerAddress extends DadataAnswer
{
    public function getAddress(): string
    {
        $data = $this->data['suggestions'][0]['data'];

        if (!$data) {
            return '';
        }

        $arAddress = [];
        if ($value = $data['postal_code']) {
            $arAddress[] = $value;
        }
        if ($value = $data['region_with_type']) {
            $arAddress[] = $value;
        }
        if ($value = $data['street_with_type']) {
            $arAddress[] = $value;
        }

        $arHouse = [];
        if ($value = $data['house_type']) {
            $arHouse[] = $value;
        }

        if ($value = $data['house']) {
            $arHouse[] = $value;
        }

        if ($value = $data['block_type']) {
            $arHouse[] = $value;
        }

        if ($value = $data['block']) {
            $arHouse[] = $value;
        }

        if (count($arHouse) > 0) {
            $arAddress[] = implode(' ', $arHouse);
        }

        if ($data['flat_type'] && $data['flat']) {
            $arAddress[] = $data['flat_type'] . ' ' . $data['flat'];
        }

        return implode(', ', $arAddress);
    }
    public function getAddressNormalized(): array
    {
        $data = $this->data['suggestions'][0]['data'];
        
        $res = [
            "postal_code" => $data['postal_code']?$data['postal_code']:'',
            "region" => $data['region']?$data['region']:'',
            "region_fias_id" => $data['region_fias_id']?$data['region_fias_id']:'',
            "area" => $data['area']?$data['area']:'',
            "area_fias_id" => $data['area_fias_id']?$data['area_fias_id']:'',
            "city" => $data['city']?$data['city']:'',
            "city_fias_id" => $data['city_fias_id']?$data['city_fias_id']:'',
            "settlement" => $data['settlement']?$data['settlement']:'',
            "settlement_fias_id" => $data['settlement_fias_id']?$data['settlement_fias_id']:'',
            "street" => $data['street']?$data['street']:'',
            "street_fias_id" => $data['street_fias_id']?$data['street_fias_id']:'',
            "house" => $data['house']?$data['house']:'',
            "house_fias_id" => $data['house_fias_id']?$data['house_fias_id']:'',
            "block" => $data['block']?$data['block']:'',
            "flat" => $data['flat']?$data['flat']:'',
            "flat_fias_id" => $data['flat_fias_id']?$data['flat_fias_id']:'',
        ];

        return $res;
    }
}
