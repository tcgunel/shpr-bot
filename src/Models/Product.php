<?php

namespace TCGunel\ShprBot\Models;

class Product extends BaseModel
{
    /** @var integer */
    public $id;

    /** @var string */
    public $title;

    /** @var string */
    public $description;

    /** @var float */
    public $price;

    /** @var string */
    public $originalPrice;

    /** @var bool */
    public $freeShipping;

    /** @var bool */
    public $new;

    /** @var int */
    public $stock;

    /** @var string[] */
    public $images;

    /** @var ProductOption[] */
    public $options;

    /** @var int */
    public $option_type;

    /** @var ProductVariation[] */
    public $variations;

    public function __construct(array $abstract)
    {
        self::fixId($abstract);
        self::fixPrice($abstract, "price");
        self::fixPrice($abstract, "originalPrice");
        self::fixStock($abstract);

        parent::__construct($abstract);
    }

    protected function fixId(&$array_of_values)
    {
        if (isset($array_of_values["productId"])) {
            $array_of_values["id"] = (int)$array_of_values["productId"];

            unset($array_of_values["productId"]);
        }
    }

    protected function fixPrice(&$array_of_values, $index = "price")
    {
        if (isset($array_of_values[$index])) {

            $array_of_values[$index] = preg_replace("/[^\d,]+/", "", $array_of_values[$index]);

            $array_of_values[$index] = (float)str_replace(",", ".", $array_of_values[$index]);

        }
    }

    protected function fixStock(&$array_of_values)
    {
        if (isset($array_of_values["stock"])) {

            $array_of_values["stock"] = (int)$array_of_values["stock"];

        }
    }
}
