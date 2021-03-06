<?php
    namespace AMASS\Controllers;

    use AMASS\Helpers\Helper;
    use AMASS\Helpers\JWT;
    use AMASS\Models\Model;
    use AMASS\SendMail;

    class User extends Controller {
        const EXP_IN_SEC = 18000;
        public function __construct() {
            parent::__construct();
        }

        public function initiate($request) {
            $email = $request->body->email;
            $isValidEmail = Helper::isValidEmail($request->body->email);
            if (!$isValidEmail['isValid']) {
                $this->jsonResponse(array('success' => '11', 'message' => 'Invalid email address'));
            }

            if (strlen($request->body->phone_number) != 11 || !is_numeric($request->body->phone_number)) {
                $this->jsonResponse(array('success' => '11', 'message' => 'Invalid phone number'));
            }


            $this->dbConnection->open();
            // Checks if user with this email has been verified
            $userE = Model::findOne($this->dbConnection, array('email' => $request->body->email, 'is_verified' => 1), 'users');
            if ($userE) {
                $this->jsonResponse(array('success' => '11', 'message' => 'User with this email already exist'));
            }

            // Checks if user with this phone number has been verified
            $userP = Model::findOne($this->dbConnection, array('phone_number' => $request->body->phone_number, 'is_verified' => 1), 'users');
            if ($userP) {
                $this->jsonResponse(array('success' => '11', 'message' => 'User with this phone number already exist'));
            }

            $user = Model::findOne($this->dbConnection, array('email' => $request->body->email), 'users');
            $yourCode = Helper::generatePin();
            
            if (!$user) {
                $userId = Model::create(
                    $this->dbConnection,
                    array('token' => $yourCode, 'is_verified' => 0, 'email' => $email, 'phone_number' => $request->body->phone_number, 'name' => $request->body->name),
                    'users'
                );
            } else {
                Model::update(
                    $this->dbConnection,
                    array('token' => $yourCode, 'is_verified' => 0,  'name' => $request->body->name),
                    array('id' => $user['id']),
                    'users'
                );
            }

            $message = "<h3>Your verification code: " . $yourCode . "</h3>";
            $mail = new SendMail($email, "Account Verification", $message, true);
            $mail->send();
            $this->jsonResponse(array('success' => '00', 'message' => 'Check your email address for the verification code and enter it below'));
        }

        public function create($request) {
            $this->dbConnection->open();
            $errorMessages = $this->validateCreate($request);
            if ($errorMessages == null) {
                $passwordHash = password_hash($request->body->password, PASSWORD_DEFAULT);
                $userDetails = array(
                    'is_verified' => 1,
                    'password' => $passwordHash
                );

                $userId = Model::update(
                    $this->dbConnection,
                    $userDetails,
                    array('email' => $request->body->email),
                    'users'
                );
                $hash = password_hash($request->body->email . $request->body->phone_number, PASSWORD_DEFAULT);
                if ($userId) {
                    $user = array(
                        'id' => $userId,
                        'email' => $request->body->email,
                        'phone_number' => $request->body->phone_number,
                        'name' => $request->body->name,
                        'hash' => $hash,
                        'created_at' => gmdate('Y-m-d\ H:i:s')
                    );
                    $jwt = JWT::generateJWT(json_encode(['email' => $request->body->email, 'name' => $request->body->name, 'id' => $userId, 'exp' => (time()) + User::EXP_IN_SEC]));
                    $this->jsonResponse(array('success' => '00', 'message' => 'User created successfully', 'token' => $jwt, 'user' => $user, 'exp' => User::EXP_IN_SEC), Controller::HTTP_OKAY_CODE);
                }

                $this->jsonResponse(array('success' => '11', 'message' => 'Server error'));
            } else {
                $this->jsonResponse(array('success' => '11', 'message' => $errorMessages));
            }
        }

        public function validateCreate($request) {
            $errorMessages = null;
            $isValidEmail = Helper::isValidEmail($request->body->email);

            if (!$isValidEmail['isValid']) {
                return $isValidEmail['message'];
            }

            if (!trim($request->body->password)) {
                return 'Password is required';
            }

            if (strlen($request->body->phone_number) != 11 || !is_numeric($request->body->phone_number)) {
                return'Invalid phone number';
            }

            if (!$errorMessages) {
                $user = Model::findOne($this->dbConnection, array('email' => $request->body->email), 'users');
                if ($user['is_verified']) {
                    return 'User already created and verified';
                }

                if ($user['token'] != $request->body->token) {
                    return 'Invalid token';
                }

                if ($user['phone_number'] != $request->body->phone_number) {
                    return 'Phone number does not match';
                }

            }

            return $errorMessages;
        }

        public function login($request) {
            $this->dbConnection->open();
            $errorMessages = $this->validateLogin($request->body->username, $request->body->password);
            if (sizeof($errorMessages['errorMessages']) > 0) {
                $this->jsonResponse(array('success' => '11', 'message' => $errorMessages['errorMessages']), Controller::HTTP_BAD_REQUEST_CODE);
            }

            $whichUser = $errorMessages['data'] == 'email' ? 'email' : 'phone_number';
            $user = Model::findOne($this->dbConnection, array($whichUser => $request->body->username), 'users');
            if ($user === 'Server error') {
                $this->jsonResponse(array('success' => '11', 'message' => ['Server error']), Controller::HTTP_SERVER_ERROR_CODE);
            }

            if (is_array($user) && password_verify($request->body->password, $user['password'])) {
                $user['password'] = '';
                $jwt = JWT::generateJWT(json_encode(['user' => $user, 'exp' => (time()) + User::EXP_IN_SEC]));
                $this->jsonResponse(array('success' => '00', 'user' => $user, 'message' => 'logged in successfully', 'token' => $jwt, 'exp' =>  User::EXP_IN_SEC));
            }

            $this->jsonResponse(array('success' => '11', 'message' => 'Invalid username or password'), Controller::HTTP_UNAUTHORIZED_CODE);
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
            $tokenPayload = JWT::verifyToken($req->body->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'), 401);
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);
            $users = UserModel::find($this->dbConnection, array('role' => $req->body->role));
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

        public function getUser($request) {
            $userId = $request->body->user_id;
            if (!is_numeric($userId)) {
                $this->jsonResponse(array('success' => '11', 'message' => 'user id must be numeric'));
            }

            $this->dbConnection->open();
            $user = Model::findOne(
                $this->dbConnection,
                array('id' => $userId),
                'users'
            );

            unset($user['hash']);
            if ($user) {
                $this->jsonResponse(array('success' => '00', 'data' => $user));
            }

            $this->jsonResponse(array('success' => '11', 'message' => 'Server error', 'data' => array()));
        }

        public function bookAppointment($request) {
            $productId = $request->body->product_id;
            $hash = $request->body->hash;

            if (!is_numeric($productId)) {
                $this->jsonResponse(array('success' => '11', 'message' => 'Product id must be numeric'));
            }
            $this->dbConnection->open();
            $user = Model::findOne($this->dbConnection, array('hash' => $hash), 'users');
            if (!$user) {
                $this->jsonResponse(array('success' => '11', 'message' => 'User not found'));
            }

            $appointmentId = Model::create(
                $this->dbConnection,
                array(
                    'product_id' => $productId,
                    'user_id' => $user['id'],
                    'created_at' => gmdate('Y-m-d\ H:i:s')
                ),
                'appointments'
            );

            if ($appointmentId) {
                $this->jsonResponse(array('success' => '00', 'message' => 'Appointment booked successfully'));
            }

            $this->jsonResponse(array('success' => '11', 'message' => 'Server error', 'data' => array()));
        }
    }
?>
