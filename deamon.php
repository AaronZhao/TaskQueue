<?php
    /**
     * Created by PhpStorm.
     * User: zhaowei
     * Date: 2018/9/1
     * Time: 下午5:41
     */
    try{
        $isDev = '';
        require_once 'TaskQueueConfig.php';
        if( empty( $argv[1] ) )
        {
            throw new TaskQueueExceptionLib('任务队列字符串为空!',TaskQueueExceptionLib::ERROR_SYS );
        }
        $taskHost = !isset( $argv[1] ) ? DEFAULT_QUEUE_HOST : $argv[1];
        $isDev = isset( $argv[2] ) && $argv[2] == 'dev' ?  'dev' : '';
        $TaskQueueDeamon = new TaskQueueDeamon( $taskHost );
        $TaskQueueDeamon->run();
    }catch(TaskQueueExceptionLib $e ){
        $e->handle( $taskHost, $isDev );
    }