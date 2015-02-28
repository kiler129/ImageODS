<?php
namespace noFlash\ImageODS;

class VirtualDisc implements DiscInterface {
    const TYPE_GENERIC = "public.optical-storage-media";
    const TYPE_CD = "public.cd-media";
    const TYPE_DVD = "public.dvd-media";
    private $allowedTypes = array(self::TYPE_CD, self::TYPE_DVD, self::TYPE_GENERIC);

    private $name = 'Virtual Disc';//By default generated from filename
    private $type = self::TYPE_GENERIC;
    public $stream;

    public function __construct($path, $name = null, $type = self::TYPE_GENERIC) {
        $this->setType($type);
        $this->setStream($path);

        if(empty($name)) {
            $name = $this->generateNameFromPath($path);
        }

        $this->setName($name);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    private function generateNameFromPath($path)
    {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        return str_replace(array("_", "-"), " ", $filename);
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        if(!in_array($type, $this->allowedTypes)) {
            throw new InvalidArgumentException("Invalid type specified - $type");
        }

        $this->type = $type;
    }

    private function setStream($path)
    {
        $stream = @fopen($path, 'r');
        if (!$stream) {
            throw new RuntimeException("Failed to add $path - cannot open file");
        }

        $this->stream = $stream;
    }

    public function getSize()
    {
        $stat = fstat($this->stream);
        return $stat['size'];
    }

    public function __toString()
    {
        return "Virtual:".$this->getName();
    }
}