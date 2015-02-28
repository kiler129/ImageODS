<?php
namespace noFlash\ImageODS;

use noFlash\CherryHttp\HttpCode;
use noFlash\CherryHttp\HttpException;
use noFlash\CherryHttp\HttpRequestHandlerInterface;
use noFlash\CherryHttp\HttpRouterInterface;
use noFlash\CherryHttp\StreamServerNodeInterface;
use Psr\Log\LoggerInterface;

class OdsRouter implements HttpRouterInterface
{
    /** @var LoggerInterface */
    private $logger;
    private $odsUserAgents = array('CCURLBS::statImage', 'CCURLBS::readDataFork'); //They corresponds to actions
    private $odsHandler;

    public function __construct(OdsHandler $odsHandler, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->odsHandler = $odsHandler;
        $this->logger->debug("ODS router initialized");
    }

    public function handleClientRequest(StreamServerNodeInterface $client)
    {
        var_dump($client->request);

        $userAgent = $client->request->getHeader('user-agent');
        $path = $client->request->getUri();

        if (in_array($userAgent, $this->odsUserAgents)) {
            $this->odsHandler->onRequest($client, $client->request);
            $client->request = null;

        } else { //Maybe in the future I implement simple list of all discs?
            $client->request = null;
            throw new HttpException('To use Remote Disc you need to use Finder.<br/>For details see <i>Use a shared DVD or CD</i> section of <a href="http://support.apple.com/en-us/HT203973">this support article</a>.',
                HttpCode::FORBIDDEN, array("Content-Type" => "text/html"), true);
        }
    }

    public function addPathHandler(HttpRequestHandlerInterface $requestHandler)
    {
    }

    public function removePathHandler(HttpRequestHandlerInterface $requestHandler)
    {
    }
}