<?php
namespace AMASS\Requests;

class Request {
    public $method;
    public $query;
    public $body;
    public $host;
    public $session;
    public $serverName;
    
    public function __construct() {
        $this->method =  $_SERVER['REQUEST_METHOD'];
        $this->host = $_SERVER['HTTP_HOST'];
        $this->serverName = $_SERVER['SERVER_NAME'];
        $path = explode('?', $_SERVER['REQUEST_URI'])[0];

        $this->route = $path;
    }

    public function setParam($allowedParams, $method) {
        $obj = (object)array();

        if ($method == 'POST') {
            $paramType = sizeof($_POST) > 0 ? $_POST : (array)json_decode(file_get_contents("php://input"));
        } else if ($method == 'GET') {
            $paramType = $_GET;
        } else {
            // TODO method not allowed;
        }

        $errorMessages = array();

        foreach($allowedParams as $param) {
            $obj->$param = isset($paramType[$param]) ? filter_var($paramType[$param], FILTER_SANITIZE_STRING) : null;

            if  (!isset($paramType[$param]) || !$paramType[$param]) {
                $errorMessages[$param] = $param . ' is required';
            }
        }

        if (sizeof($errorMessages) > 0) {
            return (object)array('success' => false, 'message' => $errorMessages);
        }

        if ($method == 'POST') {
            $this->body = $obj;
        } else {
            $this->query = $obj;
        }
        
        return (object)array('success' => true);
    }
}

?>