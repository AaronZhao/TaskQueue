<?php
class RedisLib
{
    private static $_instance;
    private $_redis;

    static function getRedis( $host, $port = 6379, $auth = '' ) {
        if( empty($host) ) {
            return false;
        }

        if ( !self::$_instance ) {
            self::$_instance = new self($host, $port, $auth);
        }
        return self::$_instance;
    }

    private function __construct( $host, $port, $auth ) {
        try {
            $this->_redis = new Redis();
            $this->_redis->connect($host, $port, 1);
            if( !empty( $auth ) && !$this->_redis->auth( $auth ) ) {
                throw TaskQueueExceptionLib( 'Redis 认证失败!', TaskQueueExceptionLib::ERROR_REDIS_FALSE );
            }
        } catch ( RedisException $e ) {
            throw TaskQueueExceptionLib( 'Code:' . $e.gtCode() . "\nMessage:". $e.getMessage(),TaskQueueExceptionLib::ERROR_REDIS_FALSE );
        }

    }

    public function __call( $function , $arguments ){
        try {
            if( $this->_redis ) {
                return call_user_func_array([$this->_redis, $function], $arguments);
            }
            return false;
        } catch ( RedisException $e ){
            throw TaskQueueExceptionLib( 'Code:' . $e.gtCode() . "\nMessage:". $e.getMessage(),TaskQueueExceptionLib::ERROR_REDIS_FALSE );
        }
    }

    public function close(){
        try {
            $this->_redis->close();
        } catch ( RedisException $e ){
            throw TaskQueueExceptionLib( 'Code:' . $e.gtCode() . "\nMessage:". $e.getMessage(),TaskQueueExceptionLib::ERROR_REDIS_FALSE );
        }
    }

}