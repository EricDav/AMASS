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
              
            )
        );
    }
?>
