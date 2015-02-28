<?php
namespace noFlash\ImageODS;

use noFlash\CherryHttp\HttpCode;
use noFlash\CherryHttp\HttpRequest;
use noFlash\CherryHttp\HttpRequestHandlerInterface;
use noFlash\CherryHttp\HttpResponse;
use noFlash\CherryHttp\StreamServerNodeInterface;
use noFlash\CherryHttp\HttpException;
use Psr\Log\LoggerInterface;

class OdsHandler implements HttpRequestHandlerInterface
{
    /** @var DiscInterface[] */
    public $discs = array();
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->logger->debug("ODS handler initialized");
    }


    public function onRequest(StreamServerNodeInterface $client, HttpRequest $request)
    {
        $urlCheck = preg_match('/\/(CdRom\d+)\.dmg/', $request->getUri(), $matches);
        if($urlCheck !== 1) {
            $this->logger->debug("Invalid disc URL requested");
            throw new HttpException("Invalid disc URL.", HttpCode::NOT_FOUND);

        } elseif(!isset($this->discs[$matches[1]])) {
            $this->logger->warning("Got valid but unknown disc URL: ".$matches[1]);
            throw new HttpException("There's no such disc as ".$matches[1], HttpCode::NOT_FOUND);
        }

        $disc = $this->discs[$matches[1]];

        switch (strtoupper($request->getMethod())) {
            case 'HEAD':
                $response = new HttpResponse(null, array(
                    "Content-Type" => "application/octet-stream",
                    "Accept-Ranges" => "bytes",
                    "Content-Length" => $disc->getSize()
                ));
                var_dump($response);
                $client->pushData($response);
                break;

            case "GET":
                //todo sending stream
            break;

            default:
                throw new HttpException('', HttpCode::METHOD_NOT_ALLOWED);
        }
    }

    public function getHandledPaths()
    {
        return array('*');
    }
}