<?php

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity */
class MyEntity
{
    /** @ORM\Id @ORM\GeneratedValue(strategy="NONE") @ORM\Column(type="integer") */
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }
}
