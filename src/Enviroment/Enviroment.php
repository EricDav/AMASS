<?php
    namespace AMASS\Enviroment;

    class Enviroment {
        public static function getEnv() {
            $host = explode(':', $_SERVER['HTTP_HOST']);
            echo $host[0]; exit;
            return $host[0] == 'localhost' ? 'development' : 'production';
        }
    }
?>
