<?php

namespace App\AI\Contracts;

interface AIProvider
{
    /**
     * @param  array<int, array<string, string>>  $messages
     * @return array{answer:string,key_points:array<int,string>}
     */
    public function generate(array $messages): array;
}
