<?php
    namespace AMASS\Models;

    class Product {
        public static function getProduct($dbConnection, $productId) {
            try {
                $sql = 'SELECT products.name, products.description, products.image_url, products.price, products.created_at, products.category_id, users.email AS seller_email, users.phone_number AS seller_phonenumber, users.name AS  seller_name FROM products INNER JOIN users ON products.user_id=users.id WHERE products.id=' . $productId;
                return $dbConnection->pdo->query($sql);
            } catch(Exception $e) {
                return false;
            }
        }

        public static function getComments($dbConnection, $productId) {
            try {
                $sql = 'SELECT comments.comment, comments.rate, users.name FROM comments INNER JOIN users ON comments.user_id=users.id WHERE products.id=' . $productId;
                return $dbConnection->pdo->queryAll($sql);
            } catch(Exception $e) {
                return false;
            }
        }
    }


?>