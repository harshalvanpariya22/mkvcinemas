<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;


class BaseAjax extends BaseController
{

    use ResponseTrait;

    protected $success = false;
    protected $data = [];
    protected $error = null;


    protected function addError( $error )
    {
        if(is_array( $error )) {
            $error = array_shift($error);
        }
        $this->error = $error;
    }

    protected function addData( $data )
    {
        if(is_array( $data )) {
            if(! empty($this->data)){
                $this->data = array_merge($this->data, $data);
            }else{
                $this->data = $data;
            }
        }
    }

    protected function success()
    {
        $this->success = true;
    }


    protected function jsonResponse()
    {
        $this->success = $this->success || ! empty($this->data);

        $data = [
            'success' => $this->success,
            'data' => $this->data
        ];

        if(! $this->success) {

            if($this->error === null)
                $this->error = 'unknown error';

            $data['error'] = $this->error;
        }else{
            if(! empty($this->error)){
                $data['error'] = $this->error;
            }
        }

        return $this->setResponseFormat('json')->respond($data);


    }

}
