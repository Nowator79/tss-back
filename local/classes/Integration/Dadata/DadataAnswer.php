<?php

namespace Godra\Api\Integration\Dadata;

class DadataAnswer
{
    protected $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
