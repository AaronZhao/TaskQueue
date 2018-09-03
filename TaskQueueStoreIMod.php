<?php
/**
 * 任务队列存储引擎接口文件
 * 
 * @package mod
 * @author zhaowei
 * @ctime 2014/03/19
 *
 */
interface TaskQueueStoreIMod{

    const TASK_QUEUE_COUNTER = 'TaskQueueCounter::';
    const TASK_QUEUE_PROCESS = 'TaskQueueProcess::';
	
	/**
	 * 添加任务至任务队列
	 * @param String $taskString 任务字符串
	 * @param String $taskParams 任务参数
	 * 
	 * @return 成功返回 true 失败会抛出TaskQueueExceptionLib 异常
	 */
	public function addTask( $taskString, $taskParams );
	
	
	/**
	 * 添加高优先级任务至任务队列
	 * @param String $taskString 任务字符串
	 * @param String $taskParams 任务参数
	 * 
	 * @return 成功返回 true 失败会抛出TaskQueueExceptionLib 异常
	 */
	public function addHighTask( $taskString, $taskParams );
	
	/**
	 * 从任务队列获取任务
	 * @param String $taskString 任务字符串
	 * 
	 * @return 成功返回  String 失败会抛出TaskQueueExceptionLib 异常
	 */
	public function getTask( $taskString );
	
	/**
	 * 从任务队列批量获取任务
	 * @param String $taskString 任务字符串
	 * @param Int $number 获取任务的个数
	 * 
	 * @return 成功返回 true 失败会抛出TaskQueueExceptionLib 异常
	 */
	public function getTasks ( $taskString, $number = 10 );
	
	/**
	 * 获取相关主机上的任务队列KEY信息
	 * 
	 * @param String $redisString 任务主机标识符
	 */
	public function getTaskKeys ( );
	
	
	/**
	 * 添加进程信息
	 * Enter description here ...
	 * @param Sting $taskString
	 * @param String $index
	 */
	public function addProcessInfo( $taskString, $processInfoString );
	
	/**
	 * 获取进程信息
	 * Enter description here ...
	 * @param unknown_type $taskString
	 */
	public function getProcessInfo( $taskString );
	
	/**
	 * 删除进程信息
	 * Enter description here ...
	 * @param unknown_type $taskString
	 */
	public function delProcessInfo( $taskString );
	
	
	/**
	 * 获取任务队列信息
	 */
	public function getTaskQueue(  );
	
	/**
	 * 设置任务队列信息
	 * Enter description here ...
	 * @param unknown_type $taskQueueArrString
	 */
	public function setTaskQueue(  $taskQueueArrString );
	
	/**
	 * 获取任务队列当前任务数量
	 * Enter description here ...
	 * @param unknown_type $taskString
	 */
	public function getListSize( $taskString );
	
	/**
	 * 添加计数器
	 * Enter description here ...
	 * @param String $counterString 计数器名称
	 */
	public function addCounter( $taskString, $counterString, $step = 0 );
	
	/**
	 * 更新主辅处理进程的心跳时间
	 */
	public function updateWorkProcessNoop( $ip );
	/**
	 * 获取主辅处理进程的心跳
	 */
	public function getWorkProcessNoop( );
}