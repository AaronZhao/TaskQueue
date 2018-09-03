<?php
/**
 * 任务队列报警mail检测程序 对相同异常类型的报警 每分钟发送一次
 
 */

class TaskCheckMail extends TaskQueueWorkerA{
	
	private $_popNum = 10;

	function __construct()
	{
		$this->taskString = 'CheckMail';
		$this->taskType = TaskQueueHost::TASK_TYPE_ASYNC;
		$this->taskInterval = 60;
		$this->taskMaxNum = 2000;		
	}
	
	public function run()
	{
		$mailList = array();
		$taskQueueStore = $this->getTaskQueueStore(  );
		$str = '';
		do{
		    $mailList = $taskQueueStore->getTasks($this->taskString, $this->_popNum );
			if( empty( $mailList ) ) {
				break;
			}
			foreach( $mailList as $val ){
				$str .= $val."\n\n\n----------------------------------------------------\n\n\n";
			}
 			
		}while( 1 );
		
		if( !empty( $str ) ){
			$alarmobj = new AlarmUserAct(AlarmUserInfoMod::GROUP_PHP);
			$alarmobj->sendEmailByGroupIdMsg($str,"任务队列异常");
		}
	}
}