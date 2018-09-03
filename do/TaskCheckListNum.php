<?php
/**
 * 任务队列系统任务类 － 系统任务
 * 
 * 此任务类主要负责扫描各个任务队列的数量,当数量达到预设的报警数时,系统抛出异常,进行相应的处理
 * 
 * @author zhaowei
 *
 */
class TaskCheckListNum extends TaskQueueWorkerA
{
	function __construct()
	{
		$this->taskString = 'CheckListNum';
		$this->taskType = TaskQueueHost::TASK_TYPE_ASYNC;
		$this->taskInterval = 10;
	}
	
	public function run()
	{
		$taskQueueStore = $this->getTaskQueueStore(  );

		$taskQueueArr = $this->decode( $taskQueueStore->getTaskQueue( $this->taskQueueStr ) );

		foreach( TaskQueueHost::$sysTaskArr as $value ){
			if( isset($taskQueueArr[$value]) )
			{
				unset($taskQueueArr[$value]);
			}
		}
		
		foreach( $taskQueueArr as $key => $value )
		{
			try{
				$listNum = $taskQueueStore->getListSize( $key );
				if( $value[TASK_MAX_NUM] !=0 && $listNum >= $value[TASK_MAX_NUM] ){
					throw new TaskQueueExceptionLib($this->taskQueueStr."::任务队列: ".$value[TASK_STRING]." 的队列长度超过报警数量(".$value[TASK_MAX_NUM]."). 目前已经达到 ".$listNum."条 \n",
					TaskQueueExceptionLib::EXP_TASKQUEUE_MAXNUM );
				}
			}catch( TaskQueueExceptionLib $e ){
			    global $isDev;
				$e->handle( $this->taskQueueStr, $isDev);
			}
		}
	}
}