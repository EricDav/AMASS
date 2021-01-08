<?php 
namespace AMASS\Controllers;

use AMASS\DB\DBConnection;
use AMASS\Models\Wallet as WalletModel;

abstract class Controller {
    const HTTP_BAD_REQUEST_CODE = 400;
    const HTTP_NOT_FOUND = 404;
    const HTTP_SERVER_ERROR_CODE = 500;
    const HTTP_UNAUTHORIZED_CODE = 401;
    const HTTP_OKAY_CODE = 200;

    public function __construct() {
        $this->dbConnection = new DBConnection();
    }

    public function jsonResponse($response, $statusCode=200) {

        header('Content-Type: application/json');
        header("HTTP/1.0 " . '200' . " ");
        echo json_encode($response);
        exit;
    }
}
