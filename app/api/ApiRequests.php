<?php declare(strict_types=1);

namespace App\Api;

use Phpfastcache\CacheManager;
use Phpfastcache\Drivers\Files\Config;
use App\Interfaces\ApiRequestInterface;

class ApiRequests implements ApiRequestInterface
{
    private int $time = 3600;
    private string $serviceUrl;

    public function __construct(string $serviceUrl)
    {
        $this->serviceUrl = $serviceUrl;
    }

    public function getToken(): string
    {
        $cacheKey = 'api_key';

        $config = new Config();
        $config->setPath(__DIR__ . '/../../cache');

        CacheManager::setDefaultConfig($config);

        $cache = CacheManager::getInstance('files');

        $cachedString = $cache->getItem($cacheKey);

        if (is_null($cachedString->get())) {
            $params = [
                'client_id' => 'ju16a6m81mhid5ue1z3v2g0uh',
                'email' => 'dusan.sparavalo@gmail.com',
                'name' => 'Dusan',
            ];
            $token = $this->postRequest('assignment/register', $params)->data->sl_token;
            $cachedString->set($token)->expiresAfter($this->time);
            $cache->save($cachedString);
        }

        return $cachedString->get();

    }

    public function getPosts(string $token, int $page = 1): array
    {
        return $this->getRequest($token, '/assignment/posts', '?sl_token=' . $token . '&page=' . $page)->data->posts;
    }

    public function getRequest(string $token, string $endPoint = '', string $params = ''): object
    {
        $ch = curl_init();
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_URL, $this->serviceUrl . $endPoint . $params);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $data = curl_exec($ch);

        return json_decode($data);
    }

    public function postRequest(string $endPoint, array $params): object
    {
        $postFields = '';

        foreach ($params as $key => $val) {
            $newString = $key . '=' . $val . '&';
            $postFields .= $newString;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$this->serviceUrl . $endPoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        curl_close ($ch);

        return json_decode($server_output);
    }

}
