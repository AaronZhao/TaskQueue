<?php
/**
 * 任务队列存储引擎 - RedisList实现类
 * 
 * @package mod
 * @author zhaowei
 * @ctime 2014/03/19
 *
 */
class TaskQueueRedisListMod implements TaskQueueStoreIMod{
	
	/*
	 * 详细注释参见接口文件 TaskQueueStoreIMod
	 */

	private $_redisString = "";
	
	private $_prefix = "";
	
	private $_exceptionFlag = 1;
	
	public function __construct ( $taskQueueStr = 'TaskQueue', $exp = 1 )
	{
	    $this->_redisString = $taskQueueStr;
		$this->_exceptionFlag = $exp;
		$this->_prefix = $taskQueueStr."::";
	}
	
	public function addTask( $taskString, $taskParams  )
	{
		if( empty( $taskString ) || empty( $taskParams ) )
		{
			if( 1 == $this->_exceptionFlag ){
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 时, 参数取值异常(taskString=".$taskString."; taskParams=".$taskParams."; taskQueueStr = ".$this->_redisString.")\n", 
					TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		
		$redisString = $this->_getRedisString( $taskString );
		
		$redis = redisLib::getRedis( $redisString );
		
		if( false === $redis->rpush( $this->_prefix.$taskString, $taskParams ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "向 ".$redisString." 的队列 "
					.$this->_prefix.$taskString." 插入信息（".$taskParams."）失败！\n", 
					TaskQueueExceptionLib::ERROR_REDIS_FALSE );
			}
			else
			{
				//其他处理方式
				return false;
			}
				
		}
		else
		{
			
			$redis->incr( self::TASK_QUEUE_COUNTER . $taskString."::PUSH::NUM" );
			
		}
		return true;
	}
	
	public function addHighTask( $taskString, $taskParams  )
	{
		if( empty( $taskString ) || empty( $taskParams ) )
		{
			if( 1 == $this->_exceptionFlag ){
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 时, 参数取值异常(taskString=".$taskString."; taskParams=".$taskParams.")\n", 
				TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		
		$redisString = $this->_getRedisString( $taskString );
		
		$redis = redisLib::getRedis( $redisString );
		
		if( false === $redis->lpush( $this->_prefix.$taskString, $taskParams ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
			
				throw new TaskQueueExceptionLib( "向 ".$redisString." 的队列 "
					.$this->_prefix.$taskString." 插入信息（".$taskParams."）失败！\n", 
					TaskQueueExceptionLib::ERROR_REDIS_FALSE );
			}
			else
			{
				return false;
			}
		}
		else
		{
			
			$redis->incr( self::TASK_QUEUE_COUNTER . $taskString."::PUSH::NUM" );
			
		}
		return true;
		
	}
	
	public function getTask( $taskString )
	{
		
		if( empty( $taskString ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 时, 参数取值异常(taskString=".$taskString.")\n", 
					TaskQueueExceptionLib::EXP_PARAMS );
			}
			else 
			{
				return false;
			}
		}
		
		$redisString = $this->_getRedisString( $taskString );
		
		$redis = redisLib::getRedis( $redisString );
		
		$result = $redis->lpop( $this->_prefix.$taskString );
		
		if( false === $result )
		{
			if( 1 == $this->_exceptionFlag )
			{
			
				throw new TaskQueueExceptionLib( "向 ".$redisString." 的队列 "
					.$this->_prefix.$taskString." 弹出信息失败！\n", 
					TaskQueueExceptionLib::ERROR_REDIS_FALSE );
			}
			else 
			{
				return false;
			}
		}
		else
		{
			
			$redis->incr( self::TASK_QUEUE_COUNTER . $taskString."::POP::NUM" );
			
		}
		return $result;
		
		
	}
	
	public function getTasks( $taskString, $number = 10 )
	{

		if( empty( $taskString ) || 0 > $number )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 时, 参数取值异常(taskString=".$taskString."; number=".$number.")\n", 
					TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		
		$redisString = $this->_getRedisString( $taskString );
		
		$redis = redisLib::getRedis( $redisString );
		
		$queueSize = $redis->lsize( $this->_prefix.$taskString );
		if( $number > $queueSize )
		{
			$number = $queueSize;
		}
		
		$resultArr = array();
		
		for( $i = 0; $i < $number; $i++ ){
			$result = $redis->lpop( $this->_prefix.$taskString );
			if( false === $result )
			{
				if( 1 == $this->_exceptionFlag )
				{
				
					throw new TaskQueueExceptionLib( "向 ".$redisString." 的队列 "
						.$this->_prefix.$taskString." 弹出信息失败！\n", taskQueueExceptionLib::ERROR_REDIS_FALSE );
				}
				else 
				{
					return false;
				}			
			}
			else
			{	
				array_push( $resultArr, $result );
				$redis->incr( self::TASK_QUEUE_COUNTER . $taskString."::POP::NUM");
				
			}
		}
		
		return $resultArr;
	}
	
	public function getTaskKeys(  )
	{
		if( empty( $this->_redisString ) ){
			if( 1 == $this->_exceptionFlag ){
				throw new TaskQueueExceptionLib( "获取任务主机KEY信息有误",
				TaskQueueExceptionLib::EXP_PARAMS_GETTASKKEYS );
			}
			else 
			{
				return false;
			}
		}
		
		$redis = redisLib::getRedis( $this->_redisString );
		
		return $redis->keys($this->_prefix.'*');
		
	}
	// 原设计是每个任务自己维护一个系统执行时间相关的KEY=>VALUE的值对,先改为统一维护TaskQueeueArr
	public function addProcessInfo($taskString, $processInfoString )
	{
		if( empty( $taskString ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 时, 参数取值异常(taskString=".$taskString.";)\n", 
					TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		
		$redisString = $this->_getRedisString( $taskString );	
		$redis = redisLib::getRedis( $redisString );
		$redis->set( self::TASK_QUEUE_PROCESS . $taskString, $processInfoString );
		return true;
	}
	
	
	public function getProcessInfo( $taskString )
	{
		if( empty( $taskString ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 时, 参数取值异常(taskString=".$taskString.";)\n", 
					TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		
		$redisString = $this->_getRedisString( $taskString );	
		$redis = redisLib::getRedis( $redisString );
		return $redis->get( self::TASK_QUEUE_PROCESS . $taskString);
	}
	
	public function delProcessInfo( $taskString )
	{
		if( empty( $taskString ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 时, 参数取值异常(taskString=".$taskString.";)\n", 
					TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		
		$redisString = $this->_getRedisString( $taskString );	
		$redis = redisLib::getRedis( $redisString );
		$redis->del( self::TASK_QUEUE_PROCESS . $taskString );
		return true;
	}
	
	public function getTaskQueue( )
	{
		if( empty( $this->_redisString ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 时, 参数取值异常\n", 
					TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		$redis = redisLib::getRedis( $this->_redisString );
		return $redis->get( "TaskQueueArrString" );
	}
	
	/**
	 * 设置当前工作进程
	 * @see TaskQueueStoreIMod::updateWorkProcessNoop()
	 */
	public function updateWorkProcessNoop( $ip )
	{
		if( empty( $this->_redisString ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
						" 时, 参数取值异常\n",
						TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		
		$mcKey    = 'workProcess' . $this->_prefix;
		$redis    = redisLib::getRedis( $this->_redisString );
		return $redis->setex($mcKey, 180, $ip);       // 设置3分钟超时
	}
	
	/**
	 * 获取当前工作进程的IP
	 * @see TaskQueueStoreIMod::getWorkProcessNoop()
	 */
	public function getWorkProcessNoop()
	{
		if( empty( $this->_redisString ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
						" 时, 参数取值异常\n",
						TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		
		$mcKey    = 'workProcess' . $this->_prefix;
		$redis    = redisLib::getRedis( $this->_redisString );
		return $redis->get($mcKey);       // 设置3分钟超时
	}
	
	public function setTaskQueue( $taskQueueArrString )
	{
		if( empty( $taskQueueArrString ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 时, 参数取值异常\n", 
					TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		
		$redis = redisLib::getRedis( $this->_redisString );
		
		return $redis->set( "TaskQueueArrString", $taskQueueArrString );
		
	}

	public function delTaskList( $taskString )
	{
		if( empty( $taskString ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 时, 参数取值异常(taskString=".$taskString."; n)\n", 
					TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		$redisString = $this->_getRedisString( $taskString );	
		$redis = redisLib::getRedis( $redisString );
		$redis->del($this->_prefix.$taskString );
	}
	
	public function getListSize( $taskString )
	{
		if( empty( $taskString ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 时, 参数取值异常(taskString=".$taskString."; )\n", 
					TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		$redisString = $this->_getRedisString( $taskString );	
		$redis = redisLib::getRedis( $redisString );
		$result = $redis->lSize( $this->_prefix.$taskString );
		if( false === $result ){
			throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 获取队列长度时提供的KEY,不是一个List,(taskString=".$taskString."; )\n", 
					TaskQueueExceptionLib::EXP_PARAMS );
		}
		return $result;
	}
	
	public function addCounter( $taskString, $counterString, $step = 0 )
	{
		if( empty( $counterString ) )
		{
			if( 1 == $this->_exceptionFlag )
			{
				throw new TaskQueueExceptionLib( "调用  ".__CLASS__." 中的方法 ".__FUNCTION__.
					" 时, 参数取值异常($counterString)为空", 
					TaskQueueExceptionLib::EXP_PARAMS );
			}
			else
			{
				return false;
			}
		}
		$redisString = $this->_getRedisString( $taskString );	
		$redis = redisLib::getRedis( $redisString );
		if( 0 == $step )
		{
			$redis->incr( $counterString );
		}
		else 
		{
			$redis->incrby( $counterString, $step );
		}
	}
	
	
	//将来可以将任务队列的redis服务器负载用此方法实现
	private function _getRedisString( $taskString )
	{
	    if ( $taskString ) {
            return $this->_redisString;
        }
        return $this->_redisString;
		
	}

}