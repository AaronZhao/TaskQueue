<?php
/**
 * 任务队列异常类
 * 
 * @package lib
 * @author zhaowei
 * @ctime 2014/03/19
 */
class TaskQueueExceptionLib extends Exception{
	
	//异常代码
	const EXP_SYS = 1000;
	const EXP_PARAMS = 1001;
	const EXP_PARAMS_GETTASKKEYS = 1002;
	const EXP_LISTSIZE = 1003;
	const EXP_TASKPROCESS_ASYNC_TIMEOUT = 1004; //异步任务,在间隔时间内没有能够执行完毕
	const EXP_TASKCLASS_CONFIG = 1005;
	const EXP_TASKQUEUE_MAXNUM = 1006;
	const EXP_RETURN_FALSE = 1007;
	
	//错误代码
	const ERROR_SYS = 2000;
	const ERROR_REDIS_FALSE = 2001;
	const ERROR_TASKCLASS_NOT_EXISTS = 2002;
	const ERROR_TASKFILE_NOT_EXISTS = 2003;
	const ERROR_SYS_SHUTDOWN = 2004;
	const ERROR_TASKPROCESS_SYCN_EXIT = 2005;
	const ERROR_TASKOBJECT_NOT_EXISTS = 2006;
	const ERROR_TASKPROCESS_ASYNC_EXIT = 2007;
	const ERROR_ASYNC_RESTART_FAILED = 2008;
	const ERROR_SYNC_RESTART_FAILED = 2009;
	const ERROR_WRITE_DB_FAILED = 2010;
	
    // 自定义字符串输出的样式
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message} \n";
    }
    
    public function handle( $hostStr, $isDev )
    {
        echo "handle\n";
    	switch ($this->code )
    	{
    		case TaskQueueExceptionLib::EXP_TASKPROCESS_ASYNC_TIMEOUT:
    		case TaskQueueExceptionLib::EXP_TASKCLASS_CONFIG:
    		case TaskQueueExceptionLib::ERROR_REDIS_FALSE:
    		case TaskQueueExceptionLib::ERROR_SYS_SHUTDOWN:
    		case TaskQueueExceptionLib::ERROR_TASKCLASS_NOT_EXISTS:
    		case TaskQueueExceptionLib::ERROR_TASKFILE_NOT_EXISTS:
    		    !$isDev ? $this->_sendMail($hostStr) : $this->_writeLog();
    			break;
    		default:
    			$this->_writeLog();
    	}
    }
    
    private function _sendMail( $hostStr ){
    	
    	TaskQueueClientAct::addTask( 'CheckMail', parent::__toString(), $hostStr );
    }
    
    private function _writeLog()
    {
        echo "write log!\n";
    	$date = date(DATE_FORMAT_ALL );
    	$logDate = date("Y-m-d");
    	echo $date . ' :' . $logDate ."\n";
    	$err = $this->message;
    	$err .= parent::__toString();
    	var_dump( $err );
    	file_put_contents(TASK_LOG_DIR . "task_queue_$logDate.log", "{$date} :: \n {$err}\n\n\n\n", FILE_APPEND);
    }
	
}

