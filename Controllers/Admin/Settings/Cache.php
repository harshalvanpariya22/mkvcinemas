<?php

namespace App\Controllers\Admin\Settings;


class Cache extends BaseSettings
{

    public function index()
    {
        $title = 'Cache Settings';
        return view('admin/settings/cache', compact('title'));
    }


    public function update()
    {

        if($this->request->getMethod() == 'post') {

            if($this->validate([
                'web_page_cache_duration' => 'required|is_natural|greater_than[59]'
            ])){

                $data = $this->request->getPost([
                    'web_page_cache',
                    'web_page_cache_duration'
                ]);
                $data['web_page_cache'] =  isset( $data['web_page_cache'] );

                return $this->save( $data );

            }

            return redirect()->back()
                            ->with('errors', $this->validator->getErrors());

        }

        return redirect()->back();

    }

    public function clean()
    {
        cache()->clean();
        return redirect()->back();
    }


}