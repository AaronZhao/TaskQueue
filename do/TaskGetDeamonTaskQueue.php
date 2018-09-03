<?php
/**
 * 任务队列系统类 － 系统类
 * 
 * 此任务类的功能是定期检查任务队列存储服务器上是否出现新的任务队列相关List的Key.
 * 存在,将此任务对列的任务字符串加入第三方存储介质存储的各任务队列(TaskQueueArr)信息的数组中
 * 并检查各任务的书写是否符合规范要求,及时更新各个任务配置参数的相关变化,更新至TaskQueueArr中
 * 
 * @package bin/taskQueue/do
 * @author zhaowei
 * 
 */

class TaskGetDeamonTaskQueue extends TaskQueueWorkerA
{
	private $_taskArr = array();
	
	function __construct()
	{
		$this->taskString = 'GetDeamonTaskQueue';
		$this->taskType = TaskQueueHost::TASK_TYPE_ASYNC;
		$this->taskInterval = 30;
		$this->taskMaxNum = 100;
	}
		
	function run(){
		$obj = $this->getTaskQueueStore( );

		$this->_taskArr = $this->_getTaskArr( $obj->getTaskKeys( ) );
		foreach( $this->_taskArr as $key => $value )
		{
			try{
				$this->_checkTask($key);
			}catch ( TaskQueueExceptionLib $e )
			{
			    global $isDev;
				$e->handle( $this->taskQueueStr, $isDev);
				continue;
			}
		}
		$taskQueueArr = $this->decode( $obj->getTaskQueue(  ) );
		foreach( $this->_taskArr as $key => $value )
		{
			if( !array_key_exists( $key, $taskQueueArr ) )
			{
				$taskQueueArr[$key] = $value;
			}
			else
			{
				$taskQueueArr[$key][TASK_TYPE] = $value[TASK_TYPE];
				$taskQueueArr[$key][TASK_INTERVAL] =$value[TASK_INTERVAL];
				$taskQueueArr[$key][TASK_MAX_NUM] = $value[TASK_MAX_NUM];
				$taskQueueArr[$key][PROCESS_NUM] = $value[PROCESS_NUM];
			}
		}
		$obj->setTaskQueue($this->encode( $taskQueueArr ) );		
	}
	
	/**
	 * 根据从服务器端获取的任务队列相关的Key生成一个以各任务字符串为key的数组并返回
	 * Enter description here ...
	 * @param unknown_type $taskQueueKeys
	 */
	private function _getTaskArr( $taskQueueKeys )
	{
		$taskArr = array();
		$taskStrArr = array();
		foreach( $taskQueueKeys as $value )
		{

			$taskStrArr = explode( '::',$value );
			$taskArr[$taskStrArr[1]] = array();
		}
		foreach( TaskQueueHost::$sysTaskArr as $value )
		{
			$taskArr[$value] = array();
		}
		return $taskArr;
	}
	
	/**
	 * 检查各任务队列字符串是否符合规范,并通过实例化各个任务队列的对象后,初始化各任务队列的相关信息.
	 * 此程序定时运行,因此如需改变各任务队列的相关参数,只需要在各任务队列的类里进行调整即可
	 * Enter description here ...
	 * @param unknown_type $taskString
	 * @throws taskQueueExceptionLib
	 */
	private function _checkTask( $taskString )
	{
		if( !file_exists( TASK_ROOT.'do/Task'.$taskString.'.php' ) )
		{
			unset( $this->_taskArr[$taskString] );
			//此处可以添加如果该任务的文件或类不存在，删除该任务的队列，但为了不丢失已写入的数据，暂时不做处理
			throw new TaskQueueExceptionLib($this->taskQueueStr.'::任务文件不存在! 文件名:'.TASK_ROOT.'do/Task'.$taskString.".php \n",
				TaskQueueExceptionLib::ERROR_TASKFILE_NOT_EXISTS );
		}
		
		
		require_once TASK_ROOT.'do/Task'.$taskString.'.php';
		$className = 'Task'.$taskString;
		if( !class_exists( $className ) )
		{
			unset( $this->_taskArr[$taskString] );
			//此处可以添加如果该任务的文件或类不存在，删除该任务的队列，但为了不丢失已写入的数据，暂时不做处理
			throw new TaskQueueExceptionLib($this->taskQueueStr.'::任务类不存在! 文件名:'.TASK_ROOT.'do/'.$className.'.php  类名:'.$className."\n",
				TaskQueueExceptionLib::ERROR_TASKCLASS_NOT_EXISTS );
		}
		
		$taskObj = new $className ();
		if( TaskQueueHost::TASK_TYPE_ASYNC != $taskObj->taskType && TaskQueueHost::TASK_TYPE_SYNC != $taskObj->taskType )
		{
			unset( $this->_taskArr[$taskString] );
			throw new TaskQueueExceptionLib($this->taskQueueStr.'::任务类配置异常! 文件名:'.TASK_ROOT.'do/Task'.$className.'.php  类名:'.$className."\n",
				TaskQueueExceptionLib::EXP_TASKCLASS_CONFIG );
		}
		$this->_taskArr[$taskString][TASK_STRING] = $taskString;
		$this->_taskArr[$taskString][TASK_TYPE] = $taskObj -> taskType;
		$this->_taskArr[$taskString][TASK_INTERVAL] = $taskObj -> taskInterval;
		$this->_taskArr[$taskString][TASK_MAX_NUM] = $taskObj->taskMaxNum;
		$this->_taskArr[$taskString][PROCESS_NUM] = $taskObj->processNum;
		if( TaskQueueHost::TASK_TYPE_ASYNC == $this->_taskArr[$taskString][TASK_TYPE] &&
			( 	$this->_taskArr[$taskString][TASK_INTERVAL] <= 0  ||
				!isset( $this->_taskArr[$taskString][TASK_INTERVAL] )
			) 
		)
		{
			unset( $this->_taskArr[$taskString] );
			//此处可以添加如果该任务的文件或类不存在，删除该任务的队列，但为了不丢失已写入的数据，暂时不做处理
			throw new TaskQueueExceptionLib($this->taskQueueStr.'::异步任务需要设置运行间隔时间,单位:秒! 文件名:'.TASK_ROOT.'do/'.$className.'.php  类名:'.$className."\n",
				TaskQueueExceptionLib::EXP_PARAMS );
		}
		
		if( TaskQueueHost::TASK_TYPE_SYNC == $this->_taskArr[$taskString][TASK_TYPE] &&
		    ( 	!isset( $this->_taskArr[$taskString][PROCESS_NUM] )
		        || $this->_taskArr[$taskString][PROCESS_NUM] <= 0
		    )
		)
		{
		    unset( $this->_taskArr[$taskString] );
		    //此处可以添加如果该任务的文件或类不存在，删除该任务的队列，但为了不丢失已写入的数据，暂时不做处理
		    throw new TaskQueueExceptionLib($this->taskQueueStr.'::同步任务需要设置运行进程数量! 文件名:'.TASK_ROOT.'do/'.$className.'.php  类名:'.$className."\n",
		        TaskQueueExceptionLib::EXP_PARAMS );
		}
		return true;	
	}
}
/* 测试代码
try{
	$obj = new taskGetDeamonTaskQueue();
	$obj->run();
}catch (Exception $e){
	$e->__toString();
}
*/