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

    public function jsonResponse($response, $statusCode) {

        header('Content-Type: application/json');
        header("HTTP/1.0 " . '200' . " ");
        echo json_encode($response);
        exit;
    }

    public function deposit($userId, $amount) { 
        $userWallet = WalletModel::findOne($this->dbConnection, array('user_id' => $userId));
        if (!$userWallet) {
            $userWallet = WalletModel::create($this->dbConnection, array('user_id' => $userId, 'amount' => $amount));

            if ($userWallet) {
                return $amount;
            }
            return $amount;
        }
        $updateParam = array('amount' => $userWallet['amount'] + $amount);
        $whereParam = array('user_id' => $userWallet['user_id']);
        if (!WalletModel::update($this->dbConnection, $updateParam, $whereParam)) {
            $this->jsonResponse(array('success' => true, 'message' => 'Server error occured'), 500);
        }
        return (int)$userWallet['amount'] + $amount;
    }

    public function withdraw($userId, $amount) {
        $userWallet = WalletModel::findOne($this->dbConnection, array('user_id' => $userId));
        if ($userWallet['amount'] < $amount) {
            $this->jsonResponse(array('success' => false, 'message' => 'Insufficient balance'), 400);
        }

        $updateParam = array('amount' => $userWallet['amount'] - $amount);
        $whereParam = array('user_id' => $userWallet['user_id']);
        if (!WalletModel::update($this->dbConnection, $updateParam, $whereParam)) {
            $this->jsonResponse(array('success' => true, 'message' => 'Server error occured'), 500);
        }
        return (int)$userWallet['amount'] - $amount;
    }
}
