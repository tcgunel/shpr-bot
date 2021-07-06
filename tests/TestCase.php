<?php

namespace TCGunel\ShprBot\Tests;

use TCGunel\ShprBot\ShprBot;
use TCGunel\ShprBot\ShprBotServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public $faker;

    /** @var ShprBot */
    public $bot;

    public $html_content;

    public $http_client;

    public function setUp(): void
    {
        parent::setUp();

        $this->faker = \Faker\Factory::create("tr_TR");
    }

    protected function getPackageProviders($app): array
    {
        return [
            ShprBotServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
    }


}
