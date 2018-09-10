<?php

use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;
use GetOpt\GetOpt;
use GetOpt\Operand;
use GetOpt\Option;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;

require_once __DIR__ . '/vendor/autoload.php';

define('MEGABYTE', 1048575);

$getOpt = new GetOpt();

// define common options
$getOpt->addOptions([
    Option::create('?', 'help', GetOpt::NO_ARGUMENT)
        ->setDescription('Show this help and quit'),
    Option::create('o', 'output', GetOpt::OPTIONAL_ARGUMENT)
        ->setDescription('Filename to save download to'),
    Option::create('n', 'numchunks', GetOpt::OPTIONAL_ARGUMENT)
        ->setDescription('Number of chunks to download')
        ->setDefaultValue(4),
    Option::create('s', 'chunksize', GetOpt::OPTIONAL_ARGUMENT)
        ->setDescription('Size of chunks to download')
        ->setDefaultValue(MEGABYTE),
    Option::create('t', 'totalsize', GetOpt::OPTIONAL_ARGUMENT)
        ->setDescription('Total size to download')
        ->setDefaultValue(MEGABYTE * 4)
]);

$getOpt->addOperands([
    Operand::create('url', Operand::REQUIRED)
        ->setDescription('URL to download file from')
]);

// process arguments and catch user errors
try {
    try {
        $getOpt->process();
    } catch (Missing $exception) {
        // catch missing exceptions if help is requested
        if (!$getOpt->getOption('help')) {
            throw $exception;
        }
    }
} catch (ArgumentException $exception) {
    file_put_contents('php://stderr', $exception->getMessage() . PHP_EOL);
    echo PHP_EOL . $getOpt->getHelpText();
    exit;
}

if ($getOpt->getOption('help')) {
    echo $getOpt->getHelpText();
    exit;
}

$url = $getOpt->getOperand('url');
$outputFile = $getOpt->getOption('output') ?: pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_BASENAME);

$client = new Client();

$headResponse = $client->head($getOpt->getOperand('url'));
$headCode = $headResponse->getStatusCode();

$contentSize = $headResponse->getHeader('Content-Length');


$promises = [
    0 => $client->getAsync($url, ['headers' => ['Range' => 'bytes=0-1048575']]),
    1 => $client->getAsync($url, ['headers' => ['Range' => 'bytes=1048576-2097151']]),
    2 => $client->getAsync($url, ['headers' => ['Range' => 'bytes=2097152-3145727']]),
    3 => $client->getAsync($url, ['headers' => ['Range' => 'bytes=3145728-4194303']]),
];

try {
    $results = Promise\unwrap($promises);

    $fh = fopen($outputFile, 'ab');

    /* @var ResponseInterface[] $results */
    foreach ($results as $resp) {
        fwrite($fh, $resp->getBody());
    }
} catch (Throwable $e) {
    echo $e->getMessage();
}

