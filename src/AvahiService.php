<?php
namespace noFlash\ImageODS;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class was tested with avahi-publish-service 0.6.31 on Debian Linux.
 *
 *
 * avahi-publish-service [options] [-s] <name> <type> <port> [<txt ...>]
 * avahi-publish-service [options] -a <host-name> <address>
 *
 * -h --help            Show this help
 * -V --version         Show version
 * -s --service         Publish service
 * -a --address         Publish address
 * -v --verbose         Enable verbose mode
 * -d --domain=DOMAIN   Domain to publish service in
 * -H --host=DOMAIN     Host where service resides
 * --subtype=SUBTYPE An additional subtype to register this service with
 * -R --no-reverse      Do not publish reverse entry with address
 * -f --no-fail         Don't fail if the daemon is not available
 *
 * Class AvahiService
 */
class AvahiService
{
    const ERR_ALREADY_PUBLISHED = 'You cannot modify parameters while service is published, call unpublish() first';
    public $avahiPublishServiceBinary = '/usr/bin/avahi-publish-service';

    /** @var LoggerInterface */
    private $logger;

    private   $pid;
    protected $name;
    protected $type;
    protected $port      = 0;
    protected $domain;
    protected $host;
    protected $noReverse = false;
    protected $texts     = array();

    public function __construct($name, $type, $port, LoggerInterface $logger = null)
    {
        $this->setName($name);
        $this->setType($type);
        $this->setPort($port);

        $this->logger = (empty($logger)) ? new NullLogger() : $logger;
        $this->logger->debug("AvahiService initialized");
    }

    /**
     * Return human-readable service name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets human readable service name.
     *
     * @param $name
     */
    public function setName($name)
    {
        if ($this->isPublished()) {
            throw new RuntimeException(self::ERR_ALREADY_PUBLISHED);
        }

        $this->name = substr($name, 0, 255);
    }

    /**
     * Retrieves service type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets service type.
     *
     * @param string $type
     */
    public function setType($type)
    {
        if ($this->isPublished()) {
            throw new RuntimeException(self::ERR_ALREADY_PUBLISHED);
        }

        $this->type = $type;
    }

    /**
     * Return service listening port number.
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        if ($this->isPublished()) {
            throw new RuntimeException(self::ERR_ALREADY_PUBLISHED);
        }

        $this->port = $port;
    }

    /**
     * Returns custom service domain.
     *
     * @return string|null
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Publish service in custom domain. It's NOT the same as hostname.
     *
     * @param $domain
     *
     * @see http://avahi.org/wiki/AvahiAndUnicastDotLocal
     */
    public function setDomain($domain)
    {
        if ($this->isPublished()) {
            throw new RuntimeException(self::ERR_ALREADY_PUBLISHED);
        }

        $this->domain = $domain;
    }

    /**
     * Provides service hostname.
     *
     * @return mixed
     */
    public function getHostname()
    {
        return $this->host;
    }

    /**
     * RFC1123 hostname of service.
     *
     * @param $host
     */
    public function setHostname($host)
    {
        if ($this->isPublished()) {
            throw new RuntimeException(self::ERR_ALREADY_PUBLISHED);
        }

        if (!preg_match('/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/',
            $host)
        ) {
            throw new InvalidArgumentException("Invalid hostname");
        }

        $this->host = $host;
    }

    /**
     * Returns whatever "noReverse" flag was set.
     *
     * @return bool
     * @see setNoReverse()
     */
    public function isNoReverse()
    {
        return $this->noReverse;
    }

    /**
     * If set to true it will not publish reverse entry with address
     *
     * @param bool $status False by default
     */
    public function setNoReverse($status = false)
    {
        if ($this->isPublished()) {
            throw new RuntimeException(self::ERR_ALREADY_PUBLISHED);
        }

        $this->noReverse = (bool)$status;
    }

    /**
     * Adds text to service.
     *
     * @param $text
     *
     * @return int Internal text entry number
     */
    public function addText($text)
    {
        if ($this->isPublished()) {
            throw new RuntimeException(self::ERR_ALREADY_PUBLISHED);
        }

        $this->texts[] = $text;
        end($this->texts);
        $key = key($this->texts);

        $this->logger->debug("Added new avahi TXT#$key record: ".$text);
        return $key;
    }

    /**
     * Removes service text using id returned by addText()
     *
     * @param int $id
     *
     * @throws \InvalidArgumentException
     */
    public function removeText($id)
    {
        if ($this->isPublished()) {
            throw new RuntimeException(self::ERR_ALREADY_PUBLISHED);
        }

        if(!is_integer($id) || !isset($this->texts[$id])) {
            throw new InvalidArgumentException("Invalid avahi TXT record id specified - $id");
        }

        unset($this->texts[$id]);
        $this->logger->debug("Removed avahi TXT#$id record");
    }

    /**
     * Removes all texts assigned to service.
     */
    public function clearServiceTexts()
    {
        if ($this->isPublished()) {
            throw new RuntimeException(self::ERR_ALREADY_PUBLISHED);
        }

        $this->texts = array();
        $this->logger->debug("Cleared avahi TXT records");
    }

    /**
     * Determines whatever service is published or not.
     *
     * @return bool
     */
    public function isPublished()
    {
        return !empty($this->pid);
    }

    /**
     * Starts publishing service.
     *
     * @return int|false Pid of avahi publisher instance. Can be used later with unpublish()
     */
    public function publish()
    {
        if ($this->isPublished()) {
            throw new RuntimeException(self::ERR_ALREADY_PUBLISHED);
        }

        //do sth

        $this->logger->info('Avahi service published');
    }


    public function unpublish()
    {
        if (!$this->isPublished()) {
            throw new RuntimeException("Service is not published!");
        }

        //do sth


        $this->logger->info('Avahi service unpublished');
    }
}