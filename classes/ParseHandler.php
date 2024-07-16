<?php

namespace IbookParser;

use GuzzleHttp\Client;

class ParseHandler
{
    protected Client $httpClient;

    public function __construct($client)
    {
        $this->httpClient = $client;
    }

    /**
     * Fetches given url and returns body of the page
     * @param $url
     * @return \Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function getPage($url): \Psr\Http\Message\StreamInterface
    {
        $response = $this->httpClient->get($url);
        return $response->getBody();
    }

    /**
     * Creates XPATH object
     * @param $string
     * @return \DOMXPath
     */
    protected function createDom($string): \DOMXPath
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($string);
        return new \DOMXPath($dom);
    }

    /**
     * Parses given url and returns DOM Xpath object
     * @param $url
     * @return \DOMXPath
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function parse($url): \DOMXPath
    {
        $body = $this->getPage($url);
        return $this->createDom($body);
    }

    /**
     * Searches given query in Xpath object
     * @param $dom
     * @param $expression
     * @return mixed
     */
    public function find($dom, $expression): mixed
    {
        return $dom->evaluate($expression);
    }
}