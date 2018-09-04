<?php
abstract class TaskQueueWorkerA{
	
	//任务字符串
	public $taskString = '';
	//任务队列字符串
	public $taskQueueStr = '';
	
	public $processNum = 1;
	/*
	 * 任务类型
	 * 1 持续任务(taskQueueDeamon::TASK_TYPE_SYNC)；
	 * 2定时任务(taskQueueDeamon::TASK_TYPE_ASYNC)
	 */
	public $taskType = 0;
	
	//定时任务间隔时间,单位妙
	public $taskInterval = 0;
	
	//队列报警限制(队列数量的大小超过该数字,会抛出异常报警处理,0表示不报警)
	public $taskMaxNum = 0;
	
	//email
	
	//phone
	
	abstract public function run();
	
	//获取相关任务的任务存储主机信息并声称存储引擎操作类对象返回
	//目前只是临时写一下,未来可以吧负载均衡的方法等写在此方法里
	/**
	 * 
	 * @return TaskQueueStoreIMod
	 */
	protected  function getTaskQueueStore(  )
	{
	 	$className = 'TaskQueue'.TaskQueueHost::$hostConfig[$this->taskQueueStr]['store_type'].'Mod';
	 	return new $className ( $this->taskQueueStr );
	}
	
	//根据任务字符串返回相应的任务队列字符串
	//目前只是临时写一下,未来可以吧负载均衡的方法等写在此方法里
	protected function getTaskQueueHost(  )
	{
	    return $this->taskQueueStr;
	}
	
	protected function encode( $arr )
	{
		return json_encode( $arr );
	}
	
	protected function decode( $str )
	{
		return json_decode( $str, true );
	}
	
	protected function wirteFile( $str )
	{
		$date = date("Y-m-d");
		return file_put_contents( TASK_LOG_DIR . $this->taskString.'_'.$date.".data", $str, FILE_APPEND);
	} 
}