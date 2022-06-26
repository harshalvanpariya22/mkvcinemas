<?php

namespace App\Controllers\Admin\Settings;

use App\Models\AdminModel;


class Profile extends BaseSettings
{

    public function index()
    {
        $title = 'Profile Settings';

        $adminModel = new AdminModel();
        $admin = $adminModel->getAdmin();

        return view('admin/settings/profile', compact('title', 'admin'));
    }


    public function update()
    {

        $adminModel = new AdminModel();
        $admin = $adminModel->getAdmin();

        if($this->request->getMethod() == 'post'){


            if($this->validate([
                'display_name' => 'permit_empty|alpha_numeric_space',
                'username' => "required|min_length[4]",
                'new_password' => 'permit_empty|min_length[4]|matches[confirm_password]'
            ])){

                $data = $this->request->getPost();

                if(! empty( $data['new_password'] )){

                    //verify old password
                    if(! $admin->verifyPassword( $data['old_password'] )){

                        return redirect()->back()
                            ->with('errors', 'Current password is invalid');

                    }

                    $data['password'] = $data['new_password'];

                }

                //unset tmp data
                if(isset( $data['new_password'] )) unset( $data['new_password'] );
                if(isset( $data['confirm_password'] )) unset( $data['confirm_password'] );
                if(isset( $data['old_password'] )) unset( $data['old_password'] );

                $admin->fill( $data );

                if( $admin->hasChanged() ){

                    if(! $adminModel->save( $admin )){

                        return redirect()->back()
                            ->with('errors', $adminModel->errors());

                    }

                }

                return redirect()->back()
                    ->with('success', 'Profile settings updated successfully');

            }else{

                return redirect()->back()
                    ->with('errors', $this->validator->getErrors())
                    ->withInput();

            }

        }

        return redirect()->back();

    }

}