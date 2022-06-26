<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Captcha extends BaseController
{
    public function index()
    {
        $captcha = service('math_captcha');

        $captcha = service('math_captcha');

        $captcha->generate()
                ->display();

       exit;
    }
}
