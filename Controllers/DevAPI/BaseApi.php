<?php

namespace App\Controllers\DevAPI;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Exceptions\PageNotFoundException;


class BaseApi extends BaseController
{

    use ResponseTrait;

    protected $success = false;
    protected $data = [];
    protected $error = null;



    public function _remap( $method )
    {
        if(! get_config('dev_api')){

            throw new PageNotFoundException('API not enabled');

        }

        //verify API
        if($this->verifyAPI()){

            if(method_exists($this, $method)){

                //call to target API method
                return $this->$method();

            }

            $this->addError('Invalid Request');
            return $this->jsonResponse();
        }

        $this->addError('Invalid API key');
        return $this->jsonResponse();
    }

    protected function verifyAPI(): bool
    {
        $apiKey = $this->request->getVar('apikey');
        return $apiKey == get_config('dev_apikey');
    }

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

        $data = [
            'success' => $this->success
        ];

        if(! $this->success) {

            if($this->error === null)
                $this->error = 'Something went wrong';

            $data['error'] = $this->error;
        }else{

            $data['data'] = $this->data;

        }

        return $this->setResponseFormat('json')->respond($data);


    }


    protected function badRequest()
    {
        $this->addError('Bad request');
        return $this->jsonResponse();
    }

    protected function isWaiting(): bool
    {
        return $this->request->getGet('await') == 'true';
    }

    protected function import(array $uniqIds, $type = 'movies')
    {
        if(! empty( $uniqIds )){

            $bulkImport = service('bulk_import');
            $type == 'movies' ? $bulkImport->movies() : $bulkImport->series();
            $bulkImport->set( $uniqIds )->run();

            if($results = $bulkImport->getResults()){

                return  $results;

            }

        }


        return false;
    }




}