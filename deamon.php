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
        $taskHost = !isset( $argv[1] ) ? DEFAULT_QUEUE_HOST : $argv[1];
        $isDev = isset( $argv[2] ) && $argv[2] == 'dev' ?  'dev' : '';
        $TaskQueueDeamon = new TaskQueueDeamon( $taskHost );
        $TaskQueueDeamon->run();
    }catch(TaskQueueExceptionLib $e ){
        $e->handle( $taskHost, $isDev );
    }