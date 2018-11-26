<?php
namespace Acme\MyApp\Entity;

use Rindow\Stdlib\Entity\PropertyAccessPolicy;

class Color implements PropertyAccessPolicy
{
    public $id;

    public $product;

    public $color;
}