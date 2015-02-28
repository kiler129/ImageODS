<?php
namespace noFlash\ImageODS;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use noFlash\CherryHttp\Server as HttpServer;

class Server
{
    const NAME_PREFIX = "CdRom";

    /** @var LoggerInterface */
    private $logger;
    /** @var DiscInterface[] */
    private $discs = array();
    /** @var OdsHandler */
    private $odsHandler;
    /** @var OdsRouter */
    private $odsRouter;

    private $serviceIp;
    private $serviceHost;
    private $servicePort;
    private $serviceMac;
    /** @var AvahiService */
    private $avahiService;

    public function __construct($mac, $ip = '0.0.0.0', $host = null, $port = 0, LoggerInterface $logger = null) {
        $this->serviceMac = $this->formatMacAddress($mac);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('Invalid IP specified!');
        }
        $this->serviceIp = $ip;
        $this->serviceHost = (empty($host)) ? gethostname() : $host;

        if(($port < 1024 && $port !== 0) || $port > 65535) {
            throw new InvalidArgumentException("Invalid port specified - it should be within 1024-65535 range.");
        }
        $this->servicePort = (int)$port;

        $this->logger = (empty($logger)) ? new NullLogger() : $logger;

        $this->odsHandler = new OdsHandler($this->logger);
        $this->odsHandler->discs = &$this->discs;
        $this->odsRouter = new OdsRouter($this->odsHandler, $this->logger);

        $this->logger->info("ImageODS initialization finished");
        $this->logger->debug("Service init params: MAC-".$this->serviceMac." | IP-".$this->serviceIp." | HOST-".$this->serviceHost." | PORT-".$this->servicePort);
    }


    private function configureAvahi() {
        $this->logger->debug("Configuring avahi service");
        // avahi-publish -H localhost.local -s "ImageODS" _odisk._tcp 6666 "sys=waMA=C8:BC:C8:FF:FF:FF,adVF=0x4,adDT=0x2,adCC=0" "disc0=adVN=NSA Files,adVT=public.optical-storage-media"
        if(empty($this->avahiService)) {
            $this->logger->debug("Creating new instance of avahi service");
            $this->avahiService = new AvahiService('ImageODS Server', '_odisk._tcp', $this->servicePort, $this->logger);
        }

        $this->avahiService->clearServiceTexts();
        $this->avahiService->addText('sys=waMA='.$this->serviceMac.',adVF=0x4,adDT=0x2,adCC=0');
        foreach($this->discs as $discKey => $disc) {
            $this->avahiService->addText($discKey.'=adVN='.$disc->getName().',adVT='.$disc->getType());
        }
        $this->logger->debug("Avahi configuration finished with ".count($this->discs).' discs');
    }

    private function formatMacAddress($mac) {
        if(preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $mac) !== 1) {
            throw new InvalidArgumentException("Invalid mac address specified.");
        }

        $mac = strtoupper(str_replace(array(':', '-'), '', $mac));
        return substr(chunk_split($mac, 2, ':'), 0, -1);
    }

    private function getNextDiscName() {
        end($this->discs);
        $key = key($this->discs);
        if($key === null) { //Empty array
            return self::NAME_PREFIX.'0';
        }

        $lastNumber = (int)substr($key, strlen(self::NAME_PREFIX));
        $nextName = self::NAME_PREFIX.($lastNumber+1);

        return $nextName;
    }

    public function addDisc(DiscInterface $disc) {
        $discId = $this->getNextDiscName();
        $this->discs[$discId] = $disc;

        $this->logger->info("Added new disc $disc with id $discId");

        return $discId;
    }

    public function removeDisc($id) {
        if(!is_integer($id) || !isset($this->discs[$id])) {
            throw new InvalidArgumentException("Invalid disc id specified - $id");
        }

        unset($this->discs[$id]);
        $this->logger->info("Removed disc with id $id");
    }

    public function updateAvahi() {
        $this->logger->info("Updating avahi service...");

        if($this->avahiService !== null && $this->avahiService->isPublished()) {
            $this->avahiService->unpublish();
        }

        $this->configureAvahi();
        $this->avahiService->publish();
    }

    public function run() {
        $server = new HttpServer($this->logger);
        $server->router = $this->odsRouter;
        $server->bind($this->serviceIp, $this->servicePort);
        $this->updateAvahi();
        $server->run();
    }
}