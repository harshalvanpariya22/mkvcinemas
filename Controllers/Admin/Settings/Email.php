<?php

namespace App\Controllers\Admin\Settings;



class Email extends BaseSettings
{

    public function index()
    {
        $title = 'Email Settings';

        return view('admin/settings/email', compact('title'));
    }

    public function test()
    {

        $email = new \App\Libraries\Email();

        if($email->isReady()){

            $email->base->setTo( get_config('email_address') );
            $email->base->setSubject('Email Test - VIPEmbed Script');
            $email->base->setMessage('Hi, I am working fine.');

            if($email->base->send()){
                echo "<h3>Email Send to " . get_config('email_address') . " successfully</h3>" ;
            }else{
                echo '<h3>Email Send Failed</h3>';
                echo $email->base->printDebugger(['headers']);
            }

        }else{
            echo 'Email Settings Not Ready Yet';
        }
        return;
    }


    public function update()
    {

        if ($this->request->getMethod() == 'post') {


            if($this->validate([
                'email' => 'permit_empty|valid_email'
            ])){

                $email = $this->request->getPost('email');
                $smtpSettings = $this->request->getPost('smtp');

                $data = [
                    'email_address' => $email,
                    'smtp_settings' => json_encode( $smtpSettings )
                ];

                return $this->save( $data );


            }

        }

        return redirect()->back();

    }

}