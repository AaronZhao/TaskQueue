<?php

class TaskSyncTest extends TaskQueueWorkerA{

    function __construct()
    {
        $this->taskString = 'ActiveUser';
        $this->taskType = TaskQueueHost::TASK_TYPE_SYNC;
        $this->processNum = 2;
        $this->taskMaxNum = 2000;
    }

    public function run()
    {
        $pidTm = utilLib::getSysPidFilectime('taskQueuePid');
        while(1)
        {
            //增加重启进程机制 start
            $pidTm2	= utilLib::getSysPidFilectime('taskQueuePid');
            if ($pidTm2 != $pidTm)
            {
                echo "kill by bin/taskQueuePid \n";
                exit(0);
            }
            //增加重启进程机制 end
            echo "1\n";
            sleep(5);
        }
    }
}