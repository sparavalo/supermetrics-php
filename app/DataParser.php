<?php declare(strict_types=1);

namespace App;

use App\Api\ApiRequests;

class DataParser
{
    private string $serviceUrl = 'https://api.supermetrics.com/';

    const NUMBER_OF_MONTHS = 12;
    const NUMBER_OF_PAGES = 10;
    const RESULTS_PER_PAGE = 100;


    public function index(): array
    {
        $connection = new ApiRequests($this->serviceUrl);
        $token = $connection->getToken();

        $data = $this->getPagedData($token);

        return [
            'averageCharLengthPerMonth' => $this->calculateAveragePostLengthPerMonth($data),
            'longestPostByCharLengthPerMonth' => $this->calculateLongestPostPerManthByCharLenght($data),
            'totalPostsSplitByWeekNumber' => $this->getPostsByWeekNumber($data),
            'averagePerUserPerMonth' => $this->calculateAveragePerUserPerMonth($data)
        ];
    }

    public function calculateAveragePostLengthPerMonth(array $data): array
    {
        $postPerMonth = $this->countPostLenght($data);
        for ($i = 1; $i <= self::NUMBER_OF_MONTHS; $i++) {
            $monthName = date('F', mktime(0, 0, 0, $i, 10));
            if (array_key_exists($i, $postPerMonth)) {
                $results[$monthName] = number_format(array_sum($postPerMonth[$i]) / count($postPerMonth[$i]), 2);
            } else {
                $results[$monthName] = 0.00;
            }
        }

        return $results ?? [];
    }

    public function calculateLongestPostPerManthByCharLenght(array $data): array
    {
        $postPerMonth = $this->countPostLenght($data);

        for ($i = 1; $i <= self::NUMBER_OF_MONTHS; $i++) {
            $monthName = date('F', mktime(0, 0, 0, $i, 10));
            if (array_key_exists($i, $postPerMonth)) {
                $results[$monthName] = max($postPerMonth[$i]);
            } else {
                $results[$monthName] = 0;
            }
        }

        return $results ?? [];
    }

    public function calculateAveragePerUserPerMonth(array $data): array
    {
        $postsByUser = $this->getPostsByUser($data);

        return $postsByUser;
    }


    public function getPostsByWeekNumber(array $data): array
    {
        $postPerWeek = [];
        foreach ($data as $post) {
            if (array_key_exists(date('W', strtotime($post->created_time)), $postPerWeek)) {
                $postPerWeek[date('W', strtotime($post->created_time))] += 1;
            } else {
                $postPerWeek[date('W', strtotime($post->created_time))] = 1;
            }
        }
        ksort($postPerWeek);
        return $postPerWeek;
    }

    private function getPostsByUser(array $data): array
    {
        $postByUser = [];
        $users = [];
        $average = [];

        foreach ($data as $post) {
            if (in_array($post->from_id, $users)) {
                continue;
            }
            $users[] = $post->from_id;
        }

        foreach ($users as $user) {
            foreach ($data as $post) {
                if ($user === $post->from_id) {
                    for ($i = 1; $i <= self::NUMBER_OF_MONTHS; $i++) {
                        if (!array_key_exists($user, $postByUser)) {
                            $postByUser[$user] = [];
                        }
                        if (date('n', strtotime($post->created_time)) == $i) {
                            if (array_key_exists($i, $postByUser[$user])) {
                                $postByUser[$user][$i] += 1;
                            } else {
                                $postByUser[$user][$i] = 0;
                            }
                        }
                    }
                }
            }
            $average[$user] = number_format(array_sum($postByUser[$user]) / self::NUMBER_OF_MONTHS, 2);
        }

        return $average;
    }

    private function countPostLenght(array $data): array
    {
        foreach ($data as $post) {
            $postPerMonth[date('n', strtotime($post->created_time))][] = strlen($post->message);
        }

        return $postPerMonth ?? [];
    }

    private function getPagedData(string $token): array
    {
        $data = [];
        $api = new ApiRequests($this->serviceUrl);
        for ($i = 1; $i <= self::NUMBER_OF_PAGES; $i++) {
            if ($i === 1) {
                $data = $api->getPosts($token, $i);
            } else {
                $arr = $api->getPosts($token, $i);
                $incrementedIndex = $i * self::RESULTS_PER_PAGE;
                $data = array_merge($data, array_combine(range($incrementedIndex, count($arr) + ($incrementedIndex - 1)), array_values($arr)));
            }
        }

        return $data;
    }

}
