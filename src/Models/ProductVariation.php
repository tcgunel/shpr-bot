<?php

namespace TCGunel\ShprBot\Models;

class ProductVariation extends BaseModel
{
    /** @var string */
    public $title;

    /** @var string[] */
    public $options;

    public function __construct(array $abstract)
    {
        self::fixTitle($abstract);
        self::fixOptions($abstract);

        parent::__construct($abstract);
    }

    protected function fixTitle(&$array_of_values)
    {
        if (isset($array_of_values["title"])) {

            $array_of_values["title"] = trim($array_of_values["title"]);

        }
    }

    protected function fixOptions(&$array_of_values): array
    {
        if (isset($array_of_values["options"])) {

            return array_map("trim", $array_of_values["options"]);

        }

        return [];
    }
}
