<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Login extends BaseController
{
    public function index()
    {
        $title = 'Admin Login';

        //redirect already logged admin to dashboard
        if(service('auth')->isLogged()){
            return redirect()->to('/admin');
        }

        if($this->request->getMethod() == 'post')
        {
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            if(service('auth')->login($username, $password)){

                //redirect to dashboard
                return redirect()->to('/admin/dashboard');

            }

            return redirect()->back()
                             ->with('error', 'Invalid username or password')
                             ->withInput();

        }

        return view('admin/auth/login', compact('title'));
    }
}
