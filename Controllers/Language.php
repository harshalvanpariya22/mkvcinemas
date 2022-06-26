<?php

namespace App\Controllers;


use CodeIgniter\Exceptions\PageNotFoundException;

class Language extends BaseController
{
    public function index($lang = '')
    {

        //check multi languages is enabled or not
        if(! is_multi_languages_enabled()){
            throw new PageNotFoundException('Multi language feature not enabled');
        }

        $session = session();
        $locale = $this->request->getGet('l');

        //set selected language in session
        $session->remove('lang');
        $session->set('lang', $locale);

        //redirect back
        $redirectUrl = $this->request->getGet('redirect');
        if(strpos($redirectUrl, base_url()) === false || strpos($redirectUrl, base_url() . '/lang') !== false){
            $redirectUrl = base_url();
        }

        //redirect back
        return redirect()->to( $redirectUrl )
                         ->setCookie('lang', $locale, 60 * 60 * 24 * 360);
    }
}
