<?php

namespace App\Controllers\Admin\Settings;



class Api extends BaseSettings
{

    public function index()
    {
        $title = 'API Settings';

        $topBtnGroup = create_top_btn_group([
            'admin/settings/api/doc' => 'View Doc'
        ]);


        return view('admin/settings/api', compact('title', 'topBtnGroup'));
    }

    public function doc()
    {
        $title = 'API Documentation';

        return view('admin/settings/api_doc', compact('title'));
    }


    public function generate()
    {
        $newKey = random_string(18);

        $this->save([
            'dev_apikey' => $newKey
        ]);

        return redirect()->back()
                         ->with('success', 'API Key generated successfully');
    }

    public function update()
    {

        if ($this->request->getMethod() == 'post') {


            $data['dev_api'] =  ! empty( $this->request->getPost('dev_api') );

            return $this->save( $data );

        }

        return redirect()->back();

    }

}