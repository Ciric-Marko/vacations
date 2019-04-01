<?php

namespace App\Vacation\Utility;

use \Firebase\JWT\JWT;

/**
 * Class JwtTokenUtility
 *
 * @package App\Vacation\Utility
 */
class JwtTokenUtility {

    /** @var string */
    private $secret;

    /** @var string $secret */
    public function __construct($secret) {
        $this->secret = $secret;
    }

    /**
     * @param int $uid
     * @param int $tokenDuration
     * @return string
     */
    public function createToken($uid, $tokenDuration = 86400 /* seconds (default=24h)*/) {
        $uid = intval($uid);
        if (!($uid > 0)) {
            return '';
        }
        $now = time();
        $data = array();
        $data['uid'] = $uid;
        $data['iat'] = $now;
        $data['exp'] = $now + $tokenDuration;
        return JWT::encode($data, $this->secret);
    }

    /**
     * @param string $token
     * @return bool|mixed
     */
    public function validateToken($token) {
        try {
            $payload = JWT::decode($token, $this->secret, array('HS256'));
            if (!$payload) {
                return false;
            }
            return json_decode(json_encode($payload));
        } catch (\Exception $e) {
            return false;
        }
    }
}