<?php
    namespace AMASS\Controllers;

    use AMASS\Helpers\Helper;
    use AMASS\Helpers\JWT;
    use AMASS\Models\Model;
    use AMASS\SendMail;
    use AMASS\Models\Product AS ProductModel;
    use AMASS\Enviroment\Enviroment;

    class Product extends Controller {
        const EXP_IN_SEC = 18000;
        const DEFAULT_LIMIT = 5;
        public function __construct() {
            parent::__construct();
        }

        public function create($request) {
            $this->dbConnection->open();
            // var_dump($request->body->token); exit;
            $name = $request->body->name;
            $description = $request->body->description;
            $categoryId = $request->body->category_id;
            $price  = $request->body->price;
            $tokenPayload = JWT::verifyToken($request->body->token);
            if (!$tokenPayload->success) {
                $this->jsonResponse(array('success' => false, 'message' => 'Authentication failed'));
                // TODO
                // verify token MIGHT TAKE THIS TO A SEPERATE MIDDLEWARE
            }
            $tokenPayload = json_decode($tokenPayload->payload);

            $uploadOk = true;
            $imageFileType = pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION);
            // $imageFileType2 = pathinfo($_FILES['file2']['name'],PATHINFO_EXTENSION);
            // $imageFileType3 = pathinfo($_FILES['file3']['name'],PATHINFO_EXTENSION);
            // $imageFileType4 = pathinfo($_FILES['file4']['name'],PATHINFO_EXTENSION);
            
            $valid_extensions = array("jpg","jpeg","png");
            if( !in_array(strtolower($imageFileType),$valid_extensions) ) {
                $uploadOk = false;
            }

            if (!$uploadOk) {
                $this->jsonResponse(array('success' => '11', 'messages' => 'Invalid image type or extension'));
            }

            $productId = Model::create(
                $this->dbConnection,
                array(
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'category_id' => $categoryId,
                    'user_id' => $tokenPayload->user->id,
                    'created_at' => gmdate('Y-m-d\ H:i:s')
                ),
                'products'
            );

            $newFilename = sha1($productId . $_FILES['file']['name']) . '-' . $productId . '.' . $imageFileType;
            if (move_uploaded_file($_FILES['file']['tmp_name'],  __DIR__ . '/../Public/images/'.$newFilename)) {
                $env = Enviroment::getEnv();
                if ($env == 'development') {
                    $baseUrl = 'http://localhost:8888/amass/src/Public/images/';
                } else if ($env == 'staging') {
                    $baseUrl = 'http://staging-api.amass.ng/src/Public/images/';
                } else {
                    $baseUrl = 'http://api.amass.ng/amass/src/Public/images/';
                }
                Model::update(
                    $this->dbConnection,
                    array('image_url' => $baseUrl . $newFilename),
                    array('id' => $productId),
                    'products'
                );

                $this->jsonResponse(array('success' => '00', 'messages' => 'Product created successfully'));
            }
        }

        public function get($req) {
            $pagNumber = $req->query->page_num;
            $limit = self::DEFAULT_LIMIT;
            $offset = ($pagNumber - 1)*$limit;
            $env = Enviroment::getEnv();
            $this->dbConnection->open();

            $count = ProductModel::getCounts($this->dbConnection)['count'];
            //var_dump($count); exit;
            if ($pagNumber*$limit >= $count) {
                $pagNumber = 0;
            }
            
            if ($env == 'development') {
                $baseUrl = 'http://localhost:8888/v1/products?page_num='.((int)$pagNumber+1);
            } else if ($env == 'staging') {
                $baseUrl = 'http://staging-api.amass.ng/v1/products?page_num='.((int)$pagNumber+1);
            } else {
                $baseUrl = 'http://api.amass.ng/amass/v1/products?page_num='.((int)$pagNumber+1);
            }

            $this->dbConnection->open();
            $products = Model::find($this->dbConnection, array(), 'products', $offset, $limit);
            $this->jsonResponse(array('success' => '00', 'messages' => 'Product retrieved successfully', 'data' => $products, 'next_page' => $pagNumber+1, 'next_page_url' => $baseUrl));
        }

        public function getProduct($request) {
            $productId = $request->query->product_id;
            if (!is_numeric($productId)) {
                $this->jsonResponse(array('success' => '11', 'message' => 'user id must be numeric'));
            }
            $this->dbConnection->open();
            $product = ProductModel::getProduct($this->dbConnection, $productId);
            $comments = ProductModel::getComments($this->dbConnection, $productId);

            if ($product) {
                $product['comments'] = $comments;
                $this->jsonResponse(array('success' => '00', 'data' => $product));
            }

            $this->jsonResponse(array('success' => '11', 'message' => 'Server error', 'data' => array()));
        }

        public function getComments($request) {
            $productId = $request->query->product_id;
            if (!is_numeric($productId)) {
                $this->jsonResponse(array('success' => '11', 'message' => 'user id must be numeric'));
            }
            $this->dbConnection->open();
            $comments = ProductModel::getComments($this->dbConnection, $productId);
            if ($comments) {
                $this->jsonResponse(array('success' => '00', 'data' => $comments));
            }

            $this->jsonResponse(array('success' => '11', 'message' => 'Server error', 'data' => array()));
        }

        public function addComment($request) {
            $productId = $request->body->product_id;
            $comment = $request->body->comment;
            $rate = $request->body->rate;
            $hash = $request->body->hash;

            if (!is_numeric($productId)) {
                $this->jsonResponse(array('success' => '11', 'message' => 'Product id must be numeric'));
            }

            if (!is_numeric($rate)) {
                $this->jsonResponse(array('success' => '11', 'message' => 'Rate must be numeric'));
            }

            $this->dbConnection->open();
            $user = Model::findOne($this->dbConnection, array('hash' => $hash), 'users');
            if (!$user) {
                $this->jsonResponse(array('success' => '11', 'message' => 'User not found'));
            }

            $commentId = Model::create(
                $this->dbConnection,
                array(
                    'rate' => $rate,
                    'comment' => $comment,
                    'product_id' => $productId,
                    'user_id' => $user['id'],
                    'created_at' => gmdate('Y-m-d\ H:i:s')
                ),
                'comments'
            );

            if ($commentId) {
                $this->jsonResponse(array('success' => '00', 'message' => 'Comment added successfully'));
            }

            $this->jsonResponse(array('success' => '11', 'message' => 'Server error', 'data' => array()));
        }

        public function update($req) {

        }
    }
?>
