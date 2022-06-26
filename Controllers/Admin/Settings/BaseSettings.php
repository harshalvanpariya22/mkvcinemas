<?php


namespace App\Controllers\Admin\Settings;

use App\Controllers\BaseController;
use App\Models\SettingsModel;


class BaseSettings extends BaseController
{

    /**
     * @var SettingsModel
     */
    protected $model;


    public function __construct()
    {
        $this->model = new SettingsModel();
    }

    protected function save(array $data ): \CodeIgniter\HTTP\RedirectResponse
    {

        foreach ($data as $name => $val) {

            $config = $this->model->getConfig( $name );
            if($config === null)
                continue;

            $config->fill( ['value' => $val] );

            if($config->hasChanged()){

                $this->model->update($name, ['value' => $val]);

            }

        }

        return redirect()->back()
            ->with('errors', $this->validator !== null ? $this->validator->getErrors() : '')
            ->with('success', 'Application settings updated successfully');

    }

}