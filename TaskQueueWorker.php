<?php
/**
 * 此类的主要功能是限制各任务类的书写规范,所有任务类的执行,都是通过此类的私有方法调用执行的.
 * 
 * 并及时将个任务的执行状态相关的信息更新至第三方存储介质存储的各任务队列相关信息里(TaskQueueArr).
 * 如进程ID,进程开始时间,进程结束时间
 * 
 * @package bin/taskQueue
 * @author zhaowei
 * 
 */
class TaskQueueWorker{
	
	public function run($obj){
		
		$this->_call($obj);
	}
	
	private function _call(TaskQueueWorkerA $worker){
		
		$worker->run();
	}
	
	/**
	 * 提交各个进程的相关信息至TaskQueueArr
	 * Enter description here ...
	 * @param string $taskString
	 * @param string $taskQueueStr
     * @return mixed
	 */
	public function getProcessInfo( $taskString, $taskQueueStr )
	{
	    
		$className = 'TaskQueue'.TaskQueueHost::$hostConfig[$taskQueueStr]['store_type'].'Mod';
		$taskQueueStore = new $className ( $taskQueueStr );
		$result = $taskQueueStore->getProcessInfo( $taskString );
		if( null == $result ||  !$result )
		{
			$result = $this->_encode(array());
		}
		return $this->_decode( $result );
	}
	
	public function addProcessInfo( $taskString, $processInfo, $taskQueueStr )
	{
		$className = 'TaskQueue'.TaskQueueHost::$hostConfig[$taskQueueStr]['store_type'].'Mod';
		$taskQueueStore = new $className ( $taskQueueStr );
		echo $className . "\n\n\n\n";
		$taskQueueStore->addProcessInfo( $taskString, $this->_encode( $processInfo ) );
	}
	
	private function _encode( $arr )
	{
		return json_encode( $arr );
	}
	
	private function _decode( $str )
	{
		return json_decode( $str, true );
	}
	
}