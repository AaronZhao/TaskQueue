<?php
/**
 * 任务队列系统任务，－系统任务 心跳报告
 * 
 * 每隔10秒任务队列像相应的主机更新自己的心跳时间，由crontabMain执行的taskCheckDeamon程序来检查相应任务队列的更新时间，
 * 当一个主机的更新时间超过1分钟，则启用备用的任务队列主机，并由taskCheckDeamon程序发出警告信息。
 * @author zhaowei
 *
 */
class TaskTaskQueueDeamonHeartbeat extends TaskQueueWorkerA
{
    function __construct()
    {
        $this->taskString = 'TaskQueueDeamonHeartbeat';
        $this->taskType = TaskQueueHost::TASK_TYPE_ASYNC;
        $this->taskInterval = 10;
    }
    
    public function run()
    {
        $ipaddr = file_get_contents('/etc/sysconfig/network-scripts/ifcfg-eth0');//读取服务器配置文件
        preg_match('/IPADDR=[\"]?([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/', $ipaddr, $arr);//定位提取IP
        $ipaddr = $arr[1];
        
        $taskQueueStore = $this->getTaskQueueStore( );
        $res    = $taskQueueStore->updateWorkProcessNoop($ipaddr);
        
        if( $res === false )
        {
            file_put_contents(ROOT_DIR . "bin/taskQueuePid", date(DATE_FORMAT_ALL ) . "\n", FILE_APPEND);
            throw new TaskQueueExceptionLib( '任务队列:'.$this->taskQueueStr.' 更新主机状态时间失败！',TaskQueueExceptionLib::EXP_SYS );
        }
    }
}   