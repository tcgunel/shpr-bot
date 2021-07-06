<?php

namespace TCGunel\ShprBot;

use Illuminate\Support\ServiceProvider;

class ShprBotServiceProvider extends ServiceProvider
{
    /**
     * Publishes configuration file.
     *
     * @return  void
     */
    public function boot()
    {
    }

    /**
     * Make config publishment optional by merging the config from the package.
     *
     * @return  void
     */
    public function register()
    {
        $this->app->bind('BizimHesapB2b', function($app) {
            return new ShprBot();
        });
    }
}
