<?php

namespace App\Controllers\Admin\Settings;


use App\Models\LinkModel;
use CodeIgniter\Model;

class Servers extends BaseSettings
{

    public function index()
    {
        $title = 'Servers Settings';

        $linksModel = new LinkModel();
        $distLinks = $linksModel->select('link')
                                ->distinct()
                                ->findAll();

        $servers = get_config('renamed_servers');

        if(! empty($distLinks)){

            foreach ($distLinks as $link) {
                $host = $link->getHost();
                if(! empty($host)){
                    if(! isset( $servers[$host] )){
                        $servers[$host] = '';
                    }
                }
            }

        }

        $serverOptions = [];
        if(! empty( $servers )){
            foreach ($servers as $key => $val) {
                if(! empty($val)){
                    $serverOptions[$val] = $val;
                }else{
                    $serverOptions[$key] = $key;
                }
            }
        }


        return view('admin/settings/servers', compact('title', 'servers', 'serverOptions'));
    }


    public function update()
    {


        if($this->request->getMethod() == 'post'){

            if($this->validate([
                'servers.*' => 'permit_empty|alpha_numeric_punct',
                'default_server' => 'permit_empty|alpha_numeric_punct'
            ])){

                $servers = $this->request->getPost('renamed_servers');
                $servers = array_map('trim', $servers);

                $default_server = $this->request->getPost('default_server');
                $servers = is_array($servers) ? json_encode($servers) : NULL;

                $data = [
                    'renamed_servers' => $servers,
                    'default_server' => $default_server
                ];

                return $this->save( $data );

            }

        }


        return redirect()->back();

    }

}