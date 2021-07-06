<?php

namespace TCGunel\ShprBot\Models;

class ProductOption extends BaseModel
{
    /** @var string */
    public $title;

    /** @var float */
    public $price;

    public function __construct(array $abstract)
    {
        self::fixTitle($abstract);
        self::fixPrice($abstract);

        parent::__construct($abstract);
    }

    protected function fixPrice(&$array_of_values, $index = "price")
    {
        if (isset($array_of_values[$index])) {

            $array_of_values[$index] = preg_replace("/[^\d,]+/", "", $array_of_values[$index]);

            $array_of_values[$index] = (float)str_replace(",", ".", $array_of_values[$index]);

        }
    }

    protected function fixTitle(&$array_of_values)
    {
        if (isset($array_of_values["title"])) {

            $array_of_values["title"] = trim($array_of_values["title"]);

        }
    }
}
