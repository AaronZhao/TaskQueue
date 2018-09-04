<?php
    /**
     * Created by PhpStorm.
     * User: zhaowei
     * Date: 2018/9/1
     * Time: 下午6:01
     */
    require_once 'TaskQueueConfig.php';
    $isDev = '';
    try{
        $taskString = isset( $argv[1] ) ? $argv[1] : "";
        $taskQueueStr = isset( $argv[2] ) ? $argv[2] : DEFAULT_QUEUE_HOST;
        $isDev  = isset( $argv[3] ) ? $argv[3] : '';

        if( empty( $taskString ) || empty( $taskQueueStr) )
        {
            throw new TaskQueueExceptionLib( $taskQueueStr.'::worker 进程获取参数获取失败! taskString'.$taskString.' taskQueueStr:'.$taskQueueStr,
                TaskQueueExceptionLib::EXP_PARAMS );
        }

        if( !isset( TaskQueueHost::$hostConfig[$taskQueueStr] )  )
        {
            throw new TaskQueueExceptionLib($taskQueueStr."::任务队列字符串无法识别!",TaskQueueExceptionLib::ERROR_SYS);
        }
        $taskQueueWorker = new TaskQueueWorker( );
        $className = 'Task'.$taskString;
        $pid = posix_getpid();
        $startTime = time();
        if( !file_exists(TASK_ROOT.'do/'.$className.'.php'))
        {
            throw new TaskQueueExceptionLib($taskQueueStr.'::任务文件不存在! 文件名:'.TASK_ROOT.'do/'.$className.".php \n",
                TaskQueueExceptionLib::ERROR_TASKFILE_NOT_EXISTS );
        }

        require_once TASK_ROOT.'do/'.$className.'.php';
        if( !class_exists( $className ) ){
            throw new TaskQueueExceptionLib($taskQueueStr.'::任务类不存在! 文件名:'.TASK_ROOT.'do/'.$className.'.php  类名:'.$className."\n",
                TaskQueueExceptionLib::ERROR_TASKCLASS_NOT_EXISTS );
        }
        echo
        $processInfo = $taskQueueWorker->getProcessInfo( $taskString, $taskQueueStr );

        $processInfo[TASK_PID] = $pid;
        $processInfo[TASK_START_TIME] = $startTime;
        $processInfo[TASK_END_TIME] = "";

        $taskQueueWorker->addProcessInfo( $taskString, $processInfo , $taskQueueStr );

        $obj = new $className( );
        $obj->taskQueueStr = $taskQueueStr;
        $taskQueueWorker -> run( $obj );
        $endTime = time();
        echo "endtime {$endTime} \n";

        $processInfo = $taskQueueWorker->getProcessInfo( $taskString, $taskQueueStr );

        $processInfo[TASK_END_TIME] = $endTime;

        $taskQueueWorker->addProcessInfo( $taskString, $processInfo, $taskQueueStr );

    }catch( TaskQueueExceptionLib $e ){
        $e->handle( $taskQueueStr, $isDev );
    }