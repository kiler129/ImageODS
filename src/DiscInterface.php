<?php
namespace noFlash\ImageODS;

interface DiscInterface
{
    public function __construct($path, $name = null, $type = self::TYPE_GENERIC);

    public function getName();

    public function setName($name);

    public function getType();

    public function setType($type);

    public function getSize();

    public function __toString();
}