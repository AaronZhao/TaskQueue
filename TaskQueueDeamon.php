<?php
/** 
 * 任务队列守护进程程序
 * 
 * 周期性检查任务队列在第三方存储的各任务队列的相关信息(TaskQueueArr),并及时更新成员变量_deamonTaskQueue.
 * 
 * 通过各个任务队列的各种状态,做出相应的处理.
 * 
 * 此进程只进行各任务进程的检查,如异步任务是否执行超时、同步任务的进程是否异常退出、启动、定时启动、检查进程的健康状态等等
 * ------------------------------------------------------------------------------
 * TaskQueueArr 键名
 * TaskQueueArr = array(
 * 	'$taskString' => array(
 * 		'taskType' => taskQueueHost::TASK_TYPE_SYNC,
 * 		'taskInterval => 30,
 *      'processNum' => 5, // 同步任务参数，启动处理进程数量
 * 		'taskMaxNum => 10000,
 * 		'taskPid => 10043,
 * 		'obj' => $object,
 * 		'taskStartTime => 33242342343,
 * 		'taskEndTime => 3234234545,
 * 		'taskEmail => zhaowei@6rooms.com,
 * 		'taskPhone => 18600575609
 * 	),
 * )
 * 
 * ----------------------------------------------------------------------------------
 * 任务字符串与任务类名及任务文件名的对应关系
 * 
 *     任务字符串                  类名                           文件名
 * GetDeamonTaskQueue      taskGetDeamonTaskQueue       taskGetDeamonTaskQueue.php
 * ----------------------------------------------------------------------------------
 * 
 * @package bin/taskQueue/
 * @author zhaowei
 * @ctime 2014/03/19
 * 
 */
class TaskQueueDeamon
{	
	// 守护进程维护的任务队列，
	private $_deamonTaskQueue = array();

	//
	private $_taskQueueObj = array();

	// 默认Sleep时间，单位：微妙
	private $_sleepSec = 500000;

	// 任务队列实例字符串
	private $_taskQueueStr = '';
	
    function __construct( $taskQueueStr )
    {
        if( $this->_checkTaskQueueStr(  $taskQueueStr ) )
        {
            $this->_taskQueueStr = $taskQueueStr;
        }
    }
	
	public function run()
	{	
	    global $isDev;
	    //增加重启进程机制
	    $pidTm = $this->_getSysPidFilectime('taskQueuePid');
	    
		while(1){
		        
		        //增加重启进程机制 start
    		    $pidTm2	= $this->_getSysPidFilectime('taskQueuePid');
    		    if ($pidTm2 != $pidTm)
    		    {
    		        echo "kill by bin/taskQueuePid \n";
    		        exit(0);
    		    }
    		    //增加重启进程机制 end
    		    
				$this->_deamonTaskQueue = $this->_getTaskQueue();
				foreach( $this->_deamonTaskQueue as $key => &$value )
				{
					try{
						//此处的处理功能跟taskGetDeamonTaskQueue的功能类似,只是缺少了新任务的添加操作,
						//将来可以将此处的检查删除
						if( !$this->_checkTask( $key ) )
						{
							continue;
						}
						//--------------可再次重构部分-------------------------------------------------------
						//此处可以根据业务类型来实例化各个具体业务检查方式,从而可以随时添加不同需求的业务处理方式
						//下次重构时可以改进
						switch( $value[TASK_TYPE] )
						{
							case TaskQueueHost::TASK_TYPE_SYNC:
								$this->_checkSyncProcess( $value );
								break;
							case TaskQueueHost::TASK_TYPE_ASYNC:
							default:
								$this->_checkAsyncProcess( $value );
						}
					}catch ( TaskQueueExceptionLib $e ){
						$e->handle( $this->_taskQueueStr, $isDev );
					}
				}
//				RedisLib::getRedis(TaskQueueHost::$hostConfig[$this->_taskQueueStr]['config']['host'],
//                    TaskQueueHost::$hostConfig[$this->_taskQueueStr]['config']['port'],
//                    TaskQueueHost::$hostConfig[$this->_taskQueueStr]['config']['auth']
//                )->close();
				usleep( $this->_sleepSec );	
		}
		
	}
	
	/**
	 * 检查同步任务队列的状态
	 * Enter description here ...
	 * @param unknown_type $value
	 * @throws taskQueueExceptionLib
	 */
	private function _checkSyncProcess( &$value )
	{
	    global $isDev;
	    for( $i = 0; $i < $value[PROCESS_NUM]; $i++ )
	    {
    		if( !isset($this->_taskQueueObj[$value[TASK_STRING].'_'.$i] ) )
    		{
    			$this->_taskQueueObj[$value[TASK_STRING].'_'.$i] = new ProcessLib( PHP_BIN.' '.TASK_ROOT.'TaskQueueWorker.php '.$value[TASK_STRING].' '.$this->_taskQueueStr.' '. $isDev,
    				' >> '.TASK_LOG_DIR.'taskQueue_'.$value[TASK_STRING].'.log  2>>'.TASK_LOG_DIR.'taskQueue_'.$value[TASK_STRING].'.log ' );		
    			//需要原来的开始时间已作记录
    			throw new TaskQueueExceptionLib( $this->_taskQueueStr.'::任务'.$value[TASK_STRING].'_'.$i.'执行期间,未通过isset检查.开始执行时间'.date(DATE_FORMAT_ALL,$value[TASK_START_TIME]).';结束时间'.date(DATE_FORMAT_ALL,time()),
    			    TaskQueueExceptionLib::ERROR_TASKOBJECT_NOT_EXISTS );
    		}
    		else if( !is_object( $this->_taskQueueObj[$value[TASK_STRING].'_'.$i] ) )
    		{
    			unset($this->_taskQueueObj[$value[TASK_STRING].'_'.$i]);
    			$this->_taskQueueObj[$value[TASK_STRING]] = new ProcessLib( PHP_BIN.' '.TASK_ROOT.'taskQueueWorker.php '.$value[TASK_STRING].' '.$this->_taskQueueStr.' '. $isDev,
    				' >> '.TASK_LOG_DIR.'taskQueue_'.$value[TASK_STRING].'.log  2>>'.TASK_LOG_DIR.'taskQueue_'.$value[TASK_STRING].'.log ' );
    			//需要原来的开始时间已作记录
    			throw new TaskQueueExceptionLib( $this->_taskQueueStr.'::任务'.$value[TASK_STRING].'_'.$i.' 执行期间,产生的对象丢失.开始执行时间'.date(DATE_FORMAT_ALL,$value[TASK_START_TIME]).';结束时间'.date(DATE_FORMAT_ALL,time()),
    				TaskQueueExceptionLib::ERROR_TASKOBJECT_NOT_EXISTS );
    		}
    		else
    		{
    			if( !$this->_taskQueueObj[$value[TASK_STRING].'_'.$i]->status() ){
    			    $oldPid = $this->_taskQueueObj[$value[TASK_STRING].'_'.$i]->getPid();
    				if( !$this->_taskQueueObj[$value[TASK_STRING].'_'.$i]->start() )
    				{
    					throw new TaskQueueExceptionLib( $this->_taskQueueStr.'::同步任务启动失败,任务字符串:'.$value[TASK_STRING]."; \n 时间:".date(DATE_FORMAT_ALL).';间隔时间为:'.$value[TASK_INTERVAL],
    							TaskQueueExceptionLib::ERROR_SYNC_RESTART_FAILED );
    				}
    				$newPid = $this->_taskQueueObj[$value[TASK_STRING].'_'.$i]->getPid();
    				//需要原来的开始时间已作记录
    				throw new TaskQueueExceptionLib($this->_taskQueueStr.'::任务 '.$value[TASK_STRING].'异常终止.开始执行时间'.date(DATE_FORMAT_ALL,$value[TASK_START_TIME]).'终止时间为:'.date(DATE_FORMAT_ALL,time()).' oldpid:'.$oldPid.' newPid:'.$newPid.'错误信息参见'
    					.TASK_LOG_DIR.'taskQueue_'.$value[TASK_STRING].'.log ', TaskQueueExceptionLib::ERROR_TASKPROCESS_SYCN_EXIT );
    			}
    		}
	    }
	}
	
	/**
	 * 检查异步任务队列的运行状态
	 * Enter description here ...
	 * @param unknown_type $value
	 * @throws taskQueueExceptionLib
	 */
	private function _checkAsyncProcess( &$value )
	{
	    global $isDev;
	    
		if( !isset($this->_taskQueueObj[$value[TASK_STRING]] ) )
		{
			$this->_taskQueueObj[$value[TASK_STRING]] = new ProcessLib( PHP_BIN.' '.TASK_ROOT.'TaskQueueWorker.php '.$value[TASK_STRING].' '.$this->_taskQueueStr. ' '. $isDev,
				' >> '.TASK_LOG_DIR.'taskQueue_'.$value[TASK_STRING].'.log  2>>'.TASK_LOG_DIR.'taskQueue_'.$value[TASK_STRING].'.log ' );		
		}
		else if( ( $value[TASK_START_TIME] + $value[TASK_INTERVAL] ) < time() )
		{
			if( is_object( $this->_taskQueueObj[$value[TASK_STRING]] ) ){
				if( $this->_taskQueueObj[$value[TASK_STRING]]->status()){
					throw new TaskQueueExceptionLib($this->_taskQueueStr.'::'. $value[TASK_STRING]."::异步任务未能在间隔的时间段内执行完毕,执行时间超时. \n 开始执行时间为:".date(DATE_FORMAT_ALL,$value[TASK_START_TIME]).";间隔时间为:".$value[TASK_INTERVAL],
						TaskQueueExceptionLib::EXP_TASKPROCESS_ASYNC_TIMEOUT );
				}
				else
				{
					if( !isset( $value[TASK_END_TIME] ) )
					{
						throw new TaskQueueExceptionLib( $this->_taskQueueStr.'::'.$value[TASK_STRING]."::异步任务未设置结束时间. \n 开始执行时间为:".date(DATE_FORMAT_ALL,$value[TASK_START_TIME]).";间隔时间为:".$value[TASK_INTERVAL],
							TaskQueueExceptionLib::ERROR_TASKPROCESS_ASYNC_EXIT );
					}
					if( !$this->_taskQueueObj[$value[TASK_STRING]]->start() )
					{
						throw new TaskQueueExceptionLib( $value[TASK_STRING].'::异步任务启动失败,任务字符串:'.$value[TASK_STRING]."; \n 时间:".date(DATE_FORMAT_ALL).';间隔时间为:'.$value[TASK_INTERVAL],
							TaskQueueExceptionLib::ERROR_ASYNC_RESTART_FAILED );
					}
				}
			}
			else 
			{
				$this->_taskQueueObj[$value[TASK_STRING]] = new ProcessLib( PHP_BIN.' '.TASK_ROOT.'TaskQueueWorker.php '.$value[TASK_STRING] .' '.$this->_taskQueueStr.' '. $isDev,
				' >> '.TASK_LOG_DIR.'taskQueue_'.$value[TASK_STRING].'.log  2>>'.TASK_LOG_DIR.'taskQueue_'.$value[TASK_STRING].'.log ' );
				$value['taskPid'] = $value['obj']->getPid();
				
			}
		}
	}
	
	/**
	 * 获取个任务队列的相关信息,如果没有则初始化一个任务队列
	 * Enter description here ...
	 */
	private function _getTaskQueue( )
	{
		//此处的设计需要在重构阶段思考
		$className = 'TaskQueue'.TaskQueueHost::$hostConfig[$this->_taskQueueStr]['store_type'].'Mod';
		$obj = new $className ( $this->_taskQueueStr );
		$result = $obj->getTaskQueue(  );
		if( NULL == $result || !$result || empty( $result ))
		{
		    $filename     = TASK_LOG_DIR . "taskQueueError.log";
		    $data         = '';
			//释放掉$this->_taskQueueObj前，先吧所有还在运行的进程杀掉
		    foreach( $this->_taskQueueObj as $taskString => $taskObj )
		    {
		        if( $taskObj->status() )
		        {
		            if( $taskObj->stop() )
		            {
		                  $data   .= date(DATE_FORMAT_ALL)."\n 任务 {$taskString} {$taskObj->getPid()} 停止成功\n";
		            }
		            else 
		            {
		                  $data   .= date(DATE_FORMAT_ALL)."\n 任务 {$taskString} {$taskObj->getPid()} 停止失败\n";
		            }
		        }
		    }
			//为了防止Reids服务端崩溃,如果涉及初始化,也需要吧$this->$_taskQueueObj释放掉
			unset($this->_taskQueueObj);
			$this->_taskQueueObj = array();
			$initTaskArr = $this->_getInitTask();
			$taskQueueArrString = $this->_encode( $initTaskArr );
			$obj->setTaskQueue( $taskQueueArrString );
			$data    .= date(DATE_FORMAT_ALL)."\n TaskQueueRedisMod->getTaskQueue return false!\n\n\n";
			file_put_contents( $filename, $data, FILE_APPEND );
			return $initTaskArr;
		}
		else 
		{
			$taskQueueArr = $this->_decode( $result );
			foreach( $taskQueueArr as $key => &$value )
			{
				$processInfo = $this->_decode( $obj->getProcessInfo( $key ) );
				$value[TASK_START_TIME] = $processInfo[TASK_START_TIME];
				$value[TASK_END_TIME] = $processInfo[TASK_END_TIME];
				$value[TASK_PID] = $processInfo[TASK_PID];
			}
			return $taskQueueArr;
		}
	}
	
	/**
	 * 检查任务字符串描述的相关任务是否存在,且符合规范
	 * Enter description here ...
	 * @param unknown_type $taskString
	 * @throws taskQueueExceptionLib
	 */
	private function _checkTask( $taskString )
	{

		if( !file_exists( TASK_ROOT.'do/Task'.$taskString.'.php' ) )
		{
			unset( $this->_deamonTaskQueue[$taskString] );
			//此处可以添加如果该任务的文件或类不存在，删除该任务的队列，但为了不丢失已写入的数据，暂时不做处理
			throw new TaskQueueExceptionLib($this->_taskQueueStr.'::任务文件不存在! 文件名:'.TASK_ROOT.'do/Task'.$taskString.".php \n",
				TaskQueueExceptionLib::ERROR_TASKFILE_NOT_EXISTS );
		}
		
		require_once TASK_ROOT.'do/Task'.$taskString.'.php';
		$className = 'Task'.$taskString;
		if( !class_exists( $className ) )
		{
			unset( $this->_deamonTaskQueue[$taskString] );
			//此处可以添加如果该任务的文件或类不存在，删除该任务的队列，但为了不丢失已写入的数据，暂时不做处理
			throw new TaskQueueExceptionLib($this->_taskQueueStr.'::任务类不存在! 文件名:'.TASK_ROOT.'do/'.$className.'.php  类名:'.$className."\n",
				TaskQueueExceptionLib::ERROR_TASKCLASS_NOT_EXISTS );
		}
		
		if( !isset( $this->_deamonTaskQueue[$taskString][TASK_TYPE] ) ||
			( 	TaskQueueHost::TASK_TYPE_ASYNC != $this->_deamonTaskQueue[$taskString][TASK_TYPE] &&
				TaskQueueHost::TASK_TYPE_SYNC != $this->_deamonTaskQueue[$taskString][TASK_TYPE]
			) 
		)	
		{
			unset( $this->_deamonTaskQueue[$taskString] );
			//此处可以添加如果该任务的文件或类不存在，删除该任务的队列，但为了不丢失已写入的数据，暂时不做处理
			throw new TaskQueueExceptionLib($this->_taskQueueStr.'::任务类不存在! 文件名:'.TASK_ROOT.'do/'.$className.'.php  类名:'.$className."\n",
				TaskQueueExceptionLib::ERROR_TASKCLASS_NOT_EXISTS );
		}
		
		if( TaskQueueHost::TASK_TYPE_ASYNC == $this->_deamonTaskQueue[$taskString][TASK_TYPE] &&
			( 	$this->_deamonTaskQueue[$taskString][TASK_INTERVAL] <= 0  || 
				!isset( $this->_deamonTaskQueue[$taskString][TASK_INTERVAL] ) 
			) 
		)
		{
			unset( $this->_deamonTaskQueue[$taskString] );
			//此处可以添加如果该任务的文件或类不存在，删除该任务的队列，但为了不丢失已写入的数据，暂时不做处理
			throw new TaskQueueExceptionLib($this->_taskQueueStr.'::异步任务需要设置运行间隔时间,单位:秒! 文件名:'.TASK_ROOT.'do/'.$className.'.php  类名:'.$className."\n",
				TaskQueueExceptionLib::EXP_PARAMS );
		}
		
		if( TaskQueueHost::TASK_TYPE_SYNC == $this->_deamonTaskQueue[$taskString][TASK_TYPE] &&
		    ( 	
		        !isset( $this->_deamonTaskQueue[$taskString][PROCESS_NUM]) ||
		        $this->_deamonTaskQueue[$taskString][PROCESS_NUM] <= 0
		    )
		)
		{
		    unset( $this->_deamonTaskQueue[$taskString] );
		    throw new TaskQueueExceptionLib($this->_taskQueueStr.'::同步任务需要设置启动进程数! 文件名:'.TASK_ROOT.'do/'.$className.'.php  类名:'.$className."\n",
		        TaskQueueExceptionLib::EXP_PARAMS );
		}
		
		if( empty( $this->_deamonTaskQueue ) ){
			echo '任务队列为空,请检查初始化任务是否设置正确!';
			exit(-1);
		}
		return true;	
	}
	
	/**
	 * 在主进程运行初期,任务队列为空的情况下 ,初始化一个任务队列
	 * Enter description here ...
	 */
	private function _getInitTask()
	{
		$initTaskArr = array();
		foreach( TaskQueueHost::$sysTaskArr as $value )
		{
			if( !file_exists( TASK_ROOT.'do/Task'.$value.'.php' ) )
			{
				echo $this->_taskQueueStr.'::无法获取初始化任务,初始化任务文件'.TASK_ROOT.'do/Task'.$value.'.php 不存在';
				exit(-1);
			}
			
			require_once TASK_ROOT.'do/Task'.$value.'.php';
			if( !class_exists( 'Task'.$value ) )
			{
				echo $this->_taskQueueStr.'::无法获取初始化任务,初始化任务类 task'.$value.'不存在';
				exit(-1);
			}
	
			$className = 'Task'.$value;
			$taskObj = new $className ();
			$initTaskArr[$value] = array(
				TASK_STRING => $value,
				TASK_TYPE => $taskObj->taskType,
				TASK_INTERVAL => $taskObj->taskInterval,
				TASK_MAX_NUM=> $taskObj->taskMaxNum
			);
		}
		
		return 	$initTaskArr;
	}
	
	/**
	 * 检查任务字符串是否为一个有效字符串
	 * 
	 * @param unknown $taskQueueStr
	 */
	private function _checkTaskQueueStr( $taskQueueStr )
	{
	    if( isset( TaskQueueHost::$hostConfig[$taskQueueStr]  ) )
	    {
	        return true;
	    }
	    throw new TaskQueueExceptionLib($this->_taskQueueStr."::任务队列字符串无法识别!",TaskQueueExceptionLib::ERROR_SYS);
	}
	
	private function _encode( $array )
	{
		return json_encode( $array );
	}
	
	private function _decode( $string )
	{
		return json_decode( $string, true );
	}

	private function _getSysPidFilectime( $filename ){
	    clearstatcache();
	    return filectime( $filename );
    }

}