<?php

use GetOpt\ArgumentException;
use GetOpt\ArgumentException\Missing;
use GetOpt\GetOpt;
use GetOpt\Operand;
use GetOpt\Option;
use Src\MultiGet;

require_once __DIR__ . '/vendor/autoload.php';

define('MEGABYTE', 1048576);
define('NUM_CHUNKS', 4);

$getOpt = new GetOpt();

// Define options
$getOpt->addOptions([
    Option::create('?', 'help', GetOpt::NO_ARGUMENT)
        ->setDescription('Show this help and quit'),
    Option::create('v', 'verbose', GetOpt::NO_ARGUMENT)
        ->setDescription('Display verbose output'),
    Option::create('o', 'output', GetOpt::OPTIONAL_ARGUMENT)
        ->setDescription('Filename to save download to'),
    Option::create('n', 'numchunks', GetOpt::OPTIONAL_ARGUMENT)
        ->setDescription('Number of chunks to download. 0 to use as many as needed')
        ->setDefaultValue(NUM_CHUNKS),
    Option::create('s', 'chunksize', GetOpt::OPTIONAL_ARGUMENT)
        ->setDescription('Size of chunks to download in bytes.')
        ->setDefaultValue(MEGABYTE),
    Option::create('t', 'totalsize', GetOpt::OPTIONAL_ARGUMENT)
        ->setDescription('Total size to download in bytes')
        ->setDefaultValue(MEGABYTE * NUM_CHUNKS)
]);

// Required operand
$getOpt->addOperands([
    Operand::create('url', Operand::REQUIRED)
        ->setDescription('URL to download file from')
]);

// Process arguments and catch user errors
try {
    try {
        $getOpt->process();
    } catch (Missing $exception) {
        // Catch missing exceptions if help is requested
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

$options = [
    'debug' => $getOpt->getOption('verbose'),
    'output' => $outputFile,
    'numChunks' => $getOpt->getOption('numchunks'),
    'chunkSize' => $getOpt->getOption('chunksize'),
    'totalSize' => $getOpt->getOption('totalsize')
];

$multiGet = new MultiGet($url, $options);
echo $multiGet->exec() ? 'Successful download' . PHP_EOL : 'Failed downloading' . PHP_EOL;


