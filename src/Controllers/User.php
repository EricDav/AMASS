<?php
    namespace AMASS\Controllers;

    use AMASS\Helpers\Helper;
    use AMASS\Helpers\JWT;
    use AMASS\Models\Model;

    class User extends Controller {
        const EXP_IN_SEC = 18000;
        public function __construct() {
            parent::__construct();
        }

        public function create($request) {
            $this->dbConnection->open();
            $errorMessages = $this->validateCreate($request);
            if (sizeof($errorMessages) == 0) {
                $passwordHash = password_hash($request->body->password, PASSWORD_DEFAULT);
                $userDetails = array(
                    'email' => $request->body->email,
                    'name' => $request->body->name,
                    'phone_number' => $request->body->phone_number,
                    'role' => $request->body->role,
                    'password' => $passwordHash
                );

                $userId = Model::create(
                    $this->dbConnection,
                    $userDetails,
                    'users'
                );

                if ($userId) {
                    // Add to students if role is 1
                    $user = array(
                        'id' => $userId,
                        'role' => $request->body->role,
                        'email' => $request->body->email,
                        'phone_number' => $request->body->phone_number,
                        'name' => $request->body->first_name,
                    );
                    $jwt = JWT::generateJWT(json_encode(['email' => $request->body->email, 'name' => $request->body->name, 'id' => $userId, 'exp' => (time()) + User::EXP_IN_SEC]));
                    $this->jsonResponse(array('success' => true, 'message' => 'User created successfully', 'token' => $jwt, 'user' => $user, 'exp' => User::EXP_IN_SEC), Controller::HTTP_OKAY_CODE);
                }

                $this->jsonResponse(array('success' => false, 'message' => 'Server error'));
            } else {
                $this->jsonResponse(array('success' => false, 'message' => $errorMessages));
            }
        }

        public function validateCreate($request) {
            $errorMessages = array();
            $name = $request->body->name;

            $isValidName = Helper::isValidName($name);
            $isValidEmail = Helper::isValidEmail($request->body->email);

            if (!$isValidEmail['isValid']) {
                $errorMessages['email'] = $isValidEmail['message'];
            }

            if (!$isValidName['isValid']) {
                $errorMessages['name'] = $isValidName['message'];
            }

            if (!trim($request->body->password)) {
                $errorMessages['password'] = 'Password is required';
            }

            if (strlen($request->body->phone_number) != 11 || !is_numeric($request->body->phone_number)) {
                $errorMessages['phone_number'] = 'Invalid phone number';
            }

            if ($request->body->role != 0 && $request->body->role != 1) {
                $errorMessages['role'] = 'Invalid role';
            }

            if (sizeof($errorMessages) == 0) {
                $user = Model::findOne($this->dbConnection, array('email' => $request->body->email), 'users');

                if ($user) {
                    $errorMessages['email'] = 'User with this email already exist';
                } else {
                    $user = Model::findOne($this->dbConnection, array('phone_number' => $request->body->phone_number), 'users');
                    if ($user) {
                        $errorMessages['phone_number'] = 'User with this phone number already exist';
                    }
                }

            }

            return $errorMessages;
        }

        public function login($request) {
            $this->dbConnection->open();
            $errorMessages = $this->validateLogin($request->body->username, $request->body->password);
            if (sizeof($errorMessages['errorMessages']) > 0) {
                $this->jsonResponse(array('success' => false, 'message' => $errorMessages['errorMessages']), Controller::HTTP_BAD_REQUEST_CODE);
            }

            $whichUser = $errorMessages['data'] == 'email' ? 'email' : 'phone_number';
            $user = Model::findOne($this->dbConnection, array($whichUser => $request->body->username), 'users');
            if ($user === 'Server error') {
                $this->jsonResponse(array('success' => false, 'message' => ['Server error']), Controller::HTTP_SERVER_ERROR_CODE);
            }

            if (is_array($user) && password_verify($request->body->password, $user['password'])) {
                $user['password'] = '';
                $jwt = JWT::generateJWT(json_encode([$user, 'exp' => (time()) + User::EXP_IN_SEC]));
                $this->jsonResponse(array('success' => true, 'user' => $user, 'message' => 'logged in successfully', 'token' => $jwt, 'exp' =>  User::EXP_IN_SEC));
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Invalid username or password'), Controller::HTTP_UNAUTHORIZED_CODE);
        }

        public function validateLogin($username, $password) {
            $errorMessages = array();

            if (empty(trim($password))) { 
                $errorMessages['password'] = 'Password is required';
            }

            if (is_numeric($username) && strlen($username) == 11) {
                $whichUserDetails = 'phone number';
            } else if (Helper::isValidEmail($username)['isValid']) {
                $whichUserDetails = 'email';
            } else {
                $errorMessages['username'] = 'Invalid email or phone number';
            }

            return ['errorMessages' => $errorMessages, 'data' => $whichUserDetails];
        }

        public function get($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->query->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);
            $users = UserModel::find($this->dbConnection, array('role' => $req->query->role));
            if (is_array($users)) {
                $this->jsonResponse(array('success' => true, 'data' => $users), 200);
            }
            $this->jsonResponse(array('success' => false, 'message' => 'Server error'), Controller::HTTP_SERVER_ERROR_CODE);
        }

        public function validateUpdate($request, $userId) {
            $errorMessages = array();
            $nameArr = explode(' ', $request->body->name);

            $firstName = $nameArr[0];
            $lastName = $nameArr[1];

            $isValidFirstName = Helper::isValidName($firstName);
            $isValidLastName = Helper::isValidName($lastName);
            $isValidEmail = Helper::isValidEmail($request->body->email);
            // var_dump($isValidLastName); exit;

            if (!$isValidEmail['isValid']) {
                $errorMessages['email'] = $isValidEmail['message'];
            }

            if (!$isValidFirstName['isValid']) {
                $errorMessages['firstName'] = $isValidFirstName['message'];
            }

            if (!$isValidLastName['isValid']) {
                $errorMessages['lastName'] = $isValidLastName['message'];
            }

            if (sizeof($errorMessages) == 0) {
                $user = UserModel::findOne($this->dbConnection, array('email' => $request->body->email));

                if ($user && $user['id'] != $userId) {
                    $errorMessages['email'] = 'User with this email already exist';
                }
            }

            return $errorMessages;
        }

        public function update($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->body->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);

            $errorMessages = $this->validateUpdate($req, $tokenPayload->id);

            if (sizeof($errorMessages) > 0) {
                $this->jsonResponse(array('success' => false, 'message' => $errorMessages), 400);
            }

            $phoneNumber = $req->body->phone_number;
            if (!is_numeric($phoneNumber)) {
                $phoneNumber = '';
            }

            if (UserModel::update($this->dbConnection, [
                'email' => $req->body->email,
                'name' => $req->body->name,
                'phone_number' => $phoneNumber
            ], ['id' => $tokenPayload->id])) {
                $this->jsonResponse(array('success' => true, 'message' => 'Updated success'), 200);
            } else {
                $this->jsonResponse(array('success' => false, 'message' => 'Internal server error'), 500);
            }
        }

        public function updatePassword($req) {
            $this->dbConnection->open();
            $tokenPayload = JWT::verifyToken($req->body->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);

            $user = UserModel::findOne($this->dbConnection, ['id' => $tokenPayload->id]);
            if (!$user) {
                $this->jsonResponse(array('success' => false, 'message' => 'User not found'), 404);
            }

            if (password_verify($req->body->old_password, $user['password'])) {
                $passwordHash = password_hash($req->body->new_password, PASSWORD_DEFAULT);

                if (UserModel::update($this->dbConnection, [
                    'password' => $passwordHash,
                ], ['id' => $tokenPayload->id])) {
                    $this->jsonResponse(array('success' => true, 'data' => $teachers), 200);
                } else {
                    $this->jsonResponse(array('success' => false, 'message' => 'Internal server error'), 500);
                }
            }

            $this->jsonResponse(array('success' => false, 'message' => 'Old password not correct'), 400);

        }
    }
?>