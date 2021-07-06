<?php

namespace TCGunel\ShprBot\Models;

class Category extends BaseModel
{
    /** @var integer */
    public $id;

    /** @var string */
    public $name;

    /** @var Product[] */
    public $products;

    public function __construct(array $abstract)
    {
        parent::__construct($abstract);
    }
}
