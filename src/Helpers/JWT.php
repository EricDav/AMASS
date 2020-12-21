<?php 
    namespace AMASS\Helpers;

    use Carbon\Carbon;
    class JWT {
        const SECRET = '149b1d4a6d08ca8759329dbac00917c85ff981cdede63ab8b25ff0f5dc11b875';
        public static function generateJWT($payload) {
            $header = json_encode([
                'typ' => 'JWT',
                'alg' => 'HS256'
            ]);

            // var_dump($payload); exit;

            $secret = self::SECRET;

            // Encode Header
            $base64UrlHeader = self::base64UrlEncode($header);

            // Encode Payload
            $base64UrlPayload = self::base64UrlEncode($payload);

            // Create Signature Hash
            $signature = \hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);

            // Encode Signature to Base64Url String
            $base64UrlSignature = self::base64UrlEncode($signature);

            // Create JWT
            $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

            return $jwt;
        }

        public static function verifyToken($jwt) {
            $secret = self::SECRET;

            // split the token
            $tokenParts = explode('.', $jwt);
            $header = \base64_decode($tokenParts[0]);
            $payload = \base64_decode($tokenParts[1]);
            $signatureProvided = $tokenParts[2];
            // var_dump(json_decode($payload)->exp); exit;

            // check the expiration time - note this will cause an error if there is no 'exp' claim in the token
            $expiration = Carbon::createFromTimestamp(json_decode($payload)->exp);
            // var_dump(Carbon::now()->diffInSeconds($expiration, false)); exit;
            $tokenExpired = (Carbon::now()->diffInSeconds($expiration, false) < 0);

            // build a signature based on the header and payload using the secret
            $base64UrlHeader = self::base64UrlEncode($header);
            $base64UrlPayload = self::base64UrlEncode($payload);
            $signature = \hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
            $base64UrlSignature = self::base64UrlEncode($signature);

            // verify it matches the signature provided in the token
            $signatureValid = ($base64UrlSignature === $signatureProvided);

            $res = (object)array();


            if ($tokenExpired) {
                $res->success = false;
                $res->message = 'Token has expired';
                return $res;
            }

            if ($signatureValid) {
                $res->success = true;
                $res->payload = $payload;
                return $res;
            } 

            $res->success = false;
            $res->message = 'Invalid token';
            return $res;
        }

        public static function base64UrlEncode($text) {
            return str_replace(
                ['+', '/', '='],
                ['-', '_', ''],
                \base64_encode($text)
            );
        }
    }

?>