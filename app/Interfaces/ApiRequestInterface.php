<?php declare(strict_types=1);

namespace App\Interfaces;


interface ApiRequestInterface
{
    public function postRequest(string $endPoint, array $params): object;
    public function getRequest(string $token, string $endPoint, string $params): object;
}
