<?php
class TaskQueueClientAct{
	
	/**
	 * 向任务队列添加任务
	 * @param String $taskString 任务队列字符串
	 * @param String $taskParmas 任务参数字符串
	 * @param String $taskQueueStr 任务队列字符串
	 * @param Int $high 任务优先级 (1.高优先级任务; 0.一般任; 默认为0)
	 * @param String $store 任务队列存储引擎 (目前只有RedisList; 默认 RedisList )
	 * 
	 * @return Boolean 成功返回 true 失败会抛出 TaskQueueExceptionLib 异常 或 返回 false
	 */
	
	public static function addTask( $taskString, $taskParams, $taskQueueStr = 'TaskQueue', $high = 0, $store='RedisList' )
	{
		$className = 'TaskQueue'.$store.'Mod';
		//为了提高程序的性能,暂时不做文件或类似都存在的检查,减少系统调用

		$taskQueueClientMod = new $className($taskQueueStr,0);
		
		$taskQueueClientAct = new TaskQueueClientAct();
		
		return $taskQueueClientAct->_call( $taskQueueClientMod, $taskString, $taskParams, $high );
		
	}
	
	private function _call( TaskQueueStoreIMod $taskQueueStore, $taskString, $taskParams,  $high )
	{
		if( 0 == $high )
		{
			return $taskQueueStore->addTask( $taskString, $taskParams  );
		}
		else 
		{
			return $taskQueueStore->addHighTask( $taskString, $taskParams );
		}
	}
}