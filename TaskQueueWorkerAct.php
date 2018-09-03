<?php
class TaskQueueWorkerAct{
	
	/**
	 * 从任务队列获取任务
	 * @param String $taskString 任务队列字符串
	 * @param String $taskParmas 任务参数字符串
	 * @param String taskQueueStr 任务队列字符串
	 * @param String $store 任务队列存储引擎 (目前只有RedisList; 默认 RedisList )
	 * 
	 * @return Boolean 成功返回 true 失败会抛出 TaskQueueExceptionLib 异常 或 返回 false
	 */
	
	public static function getTask( $taskString, $number=1, $taskQueueStr = 'TaskQueue', $store='RedisList' )
	{
		$className = 'TaskQueue'.$store.'Mod';
		//为了提高程序的性能,暂时不做文件或类似都存在的检查,减少系统调用

		$taskQueueClientMod = new $className( $taskQueueStr);
		$taskQueueWorkerAct = new TaskQueueWorkerAct();
		
		return $taskQueueWorkerAct->_call( $taskQueueClientMod, $taskString, $number );
	}
	
	private function _call( TaskQueueStoreIMod $taskQueueStore, $taskString, $number )
	{
		if( 1 == $number )
		{
			return $taskQueueStore->getTask( $taskString );
		}
		else
		{
			return $taskQueueStore->getTasks( $taskString, $number );
		}
		
	}
}