<?php

namespace Src;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class MultiGet
{
    private const HTTP_OK = 200;

    private $url;
    private $options;

    /**
     * MultiGet constructor.
     * @param string $url
     * @param array $options
     */
    public function __construct(string $url, array $options)
    {
        $this->url = $url;
        $this->options = $options;
    }

    /**
     * Retrieves the file from the provided url and chunks it up into the passed in options
     * @return bool
     */
    public function exec(): bool
    {
        $totalSize = $this->options['totalSize'];

        $client = new Client();

        // Grabbing the content length from the headers of the file, this is to see if the file being requested is
        // smaller then the total size being requested
        $headResponse = $client->head($this->url);

        if ($headResponse->getStatusCode() === self::HTTP_OK) {
            $contentSize = $headResponse->getHeader('Content-Length');

            // If the file is smaller than the total size requested, just grab all of it
            $totalSize = $contentSize[0] < $this->options['totalSize'] ? $contentSize[0] : $this->options['totalSize'];
        }

        $numChunks = $this->options['numChunks'];

        // If numChunks = 0, then figure out chunk size based on totalSize divided by chunkSize
        if ($numChunks === 0) {
            $numChunks = ceil($totalSize / $this->options['chunkSize']);
        }

        // Setup the initial start and end positions for the range
        $startPos = 0;
        $endPos = $this->options['chunkSize'] - 1; //Subtract one since we start with 0

        $continue = true;
        $promises = [];
        do {
            $options = [
                'debug' => $this->options['debug'],
                'headers' => ['Range' => "bytes=$startPos-$endPos"]
            ];
            $promises[] = $client->getAsync($this->url, $options);

            // Shift starting position to the end position and add one
            $startPos = $endPos + 1;
            $endPos += $this->options['chunkSize'];

            // If we've reached the number of chunks requested, stop building them
            // Else if starting position for next promise is going to be greater than totalSize, then stop
            if (\count($promises) >= $numChunks) {
                $continue = false;
            } elseif ($startPos >= $totalSize) {
                $continue = false;
            }
        } while ($continue);

        try {
            $results = Promise\unwrap($promises);

            $fh = fopen($this->options['output'], 'ab');

            /* @var ResponseInterface[] $results */
            foreach ($results as $resp) {
                fwrite($fh, $resp->getBody());
            }

            return true;
        } catch (Throwable $e) {
            echo $e->getMessage();

            return false;
        }
    }
}