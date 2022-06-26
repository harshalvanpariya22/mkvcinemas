<?php

namespace App\Config;


use CodeIgniter\Config\BaseConfig;
use Config\Database;

class Settings extends BaseConfig
{

    public function __construct()
    {
        parent::__construct();

        $db = Database::connect();
        $settings = $db->query("SELECT * FROM settings")
                       ->getResultArray();

        if($db->affectedRows() > 0) {

            foreach ($settings as $setting) {

                $name = $setting['name'];
                $value = $setting['value'];
                $dataType = $setting['data_type'];

                if($dataType == 'array') {
                    if(is_string($value)){
                        $value = json_decode($value, true);
                        $value = is_array($value) ? $value : [];
                    }
                }

                if($dataType == 'int') {
                    $value = (int) $value;
                }

                if($dataType == 'bool') {
                    $value = ($value == 'true' || $value == 1);
                }

                if($dataType == 'string') {
                    $value = (string) $value;
                }

                $this->{$name} = $value;

            }

        }

    }

    public function isExist($name)
    {
        if(property_exists($this, $name)){
            return true;
        }
        return false;
    }




}

