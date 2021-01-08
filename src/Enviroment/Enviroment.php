<?php
    namespace AMASS\Enviroment;

    class Enviroment {
        public static function getEnv() {
            $host = explode(':', $_SERVER['HTTP_HOST']);

            if ($host[0] == 'localhost') {
                return 'development';
            }

            if ($host[0] == 'staging-api.amass.ng') {
                return 'staging';
            }

            if ($host == 'api.amass.ng') {
                return 'production';
            }

            die('Connection Failed');
        }
    }
?>
