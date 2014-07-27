<?php

namespace BigFish\Hub3;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class ApiController
{
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
