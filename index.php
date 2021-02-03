<?php declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\DataParser;

class Kernel
{
    public function index()
    {
        $app = new DataParser();
        return $app->index();
    }
}

$test = new Kernel;
echo $test->index();
