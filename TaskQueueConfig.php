<?php
error_reporting( E_ALL );

define('TASK_ROOT', __DIR__ . '/');
define('PHP_BIN', '/usr/local/php7/bin/php -q ');
define('TASK_LOG_DIR', '/data/logs/taskQueue/');
define('TASK_TYPE', 'taskType');
define('DATE_FORMAT_ALL', 'Y-m-d H:i:s');
define('PROCESS_NUM', 'processNum');
define('TASK_STRING', 'taskString');
define('TASK_INTERVAL', 'taskInterval');
define('TASK_END_TIME', 'taskEndTime');
define('TASK_MAX_NUM','taskMaxNum');
define('TASK_START_TIME', 'taskStartTime');
define('TASK_END_TIME', 'taskEndTime');
define('TASK_PID', 'taskPid');
define('DEFAULT_QUEUE_HOST','TaskQueue');
define('DEFAULT_STORE_TYPE','RedisList');


/*
 * 将错误代码转换为对应字符串
 */
function errnoToString($errno)
{
	switch($errno){
        case E_ERROR:               return "Error";                  
        case E_WARNING:             return "Warning";                
        case E_PARSE:               return "Parse Error";            
        case E_NOTICE:              return "Notice";                
        case E_CORE_ERROR:          return "Core Error";             
        case E_CORE_WARNING:        return "Core Warning";           
        case E_COMPILE_ERROR:       return "Compile Error";          
        case E_COMPILE_WARNING:     return "Compile Warning";        
        case E_USER_ERROR:          return "User Error";             
        case E_USER_WARNING:        return "User Warning";           
        case E_USER_NOTICE:         return "User Notice";            
        case E_STRICT:              return "Strict Notice";          
        case E_RECOVERABLE_ERROR:   return "Recoverable Error";     
        default:                    return "Unknown error ($errno)"; 
    }
}

//任务队列主机配置段
class TaskQueueHost{


    //任务队列状态存储配置
    public static $taskQueueStatusHost = array(
        'host'                => DEFAULT_QUEUE_HOST,
    );
    
    //任务队列存储引擎配置
	public static $hostConfig = array(
	    DEFAULT_QUEUE_HOST    => [
	            'store_type' => DEFAULT_STORE_TYPE ,
                'config'    =>[
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'auth' => ''
                ]
            ],
	);
	
	const TASK_TYPE_SYNC   = 1;
	const TASK_TYPE_ASYNC  = 2;
	
	public static $sysTaskArr = array(
		'GetDeamonTaskQueue',
		'CheckListNum',			// 检查是否有超多未处理的队列
	    'TaskQueueDeamonHeartbeat',
	);
}

register_shutdown_function( function(){
    global $isDev;
    $error = error_get_last();
    if( null != $error['type'] ){
        $msg = date(DATE_FORMAT_ALL )."::taskQueueShutdownFun \n";
        $msg .= "[".errnoToString($error['type'])."]". $error['message']."\n";
        $msg .= "Fatal error on line ".$error['line']." in file ".$error['file']." \n";
        $msg .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
        $msg .= "\n";
        try{
            throw new TaskQueueExceptionLib( $msg, TaskQueueExceptionLib::ERROR_SYS_SHUTDOWN );
        }catch( TaskQueueExceptionLib $e ){
            $e->handle(DEFAULT_QUEUE_HOST, $isDev );
        }
    }
});

set_error_handler( function( $errno, $errstr, $errfile, $errline ){
    global  $isDev;
    if( null != $errno ){
        $msg = '';
        $msg .= "[".errnoToString($errno)."] $errstr\n";
        $msg .= "Fatal error on line $errline in file $errfile \n";
        $msg .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
        $msg .= "\n";
        try{
            throw new TaskQueueExceptionLib( $msg, TaskQueueExceptionLib::ERROR_SYS );
        }catch ( TaskQueueExceptionLib $e ){
            $e->handle( DEFAULT_QUEUE_HOST, $isDev );
        }
    }
});

set_exception_handler( function( $exception ) {
    global $isDev;
    try{
        throw new TaskQueueExceptionLib( $exception->__toString(), TaskQueueExceptionLib::EXP_SYS );
    } catch ( TaskQueueExceptionLib $e ){
        $e->handle( DEFAULT_QUEUE_HOST, $isDev );
    }
});

spl_autoload_register(function( $class ){
    $filename = TASK_ROOT . $class . '.php';
    $taskname = TASK_ROOT . 'do/' . $class . '.php';
    file_exists( $filename ) ? require_once $filename : require_once $taskname;
});

ini_set( "display_errors", "on" );
date_default_timezone_set('Asia/Chongqing');
