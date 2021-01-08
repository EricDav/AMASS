<?php
    namespace AMASS\Config;
    
    class DBConfig {
        const  dbConfig = array(
            'development' => array(
                'user' => 'root',
                'password' => 'root',
                'host' => 'localhost',
                'database' => 'amass',
                'port' => 8889
            ),
    
            'production' => array(
                'user' => 'ammasng_amass',
                'password' => 'weareamass123',
                'host' => 'localhost',
                'database' => 'ammassng_dev',
                'port' => 8889
            )
        );
    }
?>
