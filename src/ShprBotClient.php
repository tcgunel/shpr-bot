<?php

namespace TCGunel\ShprBot;

use Illuminate\Support\Facades\Http;

class ShprBotClient
{
    /** @var Http */
    protected $http_client;

    /** @var string */
    protected $url;

    /**
     * ShprBot constructor.
     * @param Http|null $http_client
     */
    public function __construct($http_client)
    {
        if ($http_client instanceof Http === false) {

            $this->http_client = Http::class;

        } else {

            $this->http_client = $http_client;

        }
    }
}
