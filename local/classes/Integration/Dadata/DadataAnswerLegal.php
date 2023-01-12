<?php

namespace Godra\Api\Integration\Dadata;

use Godra\Api\Integration\Dadata\DadataAnswer;

class DadataAnswerLegal extends DadataAnswer
{
    public function isIndividual(): bool
    {
        return $this->data['suggestions'][0]['data']['type'] == "INDIVIDUAL";
    }
}
