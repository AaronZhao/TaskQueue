<?php

$isDev  = 'real';
if ($argc >= 2 && $argv[1] == 'dev' )
{
    define('ENV', 2);
    $isDev = 'dev';
}

$isCheck    = true;
if ($isDev == 'dev')
{
    require_once '/data1/www-test/live/config/LiveConfig.php';
    require_once '/data1/www-test/live/bin/taskQueue/taskQueueConfig.php';
}
else
{
    require_once '/usr/local/www/live/config/LiveConfig.php';
    require_once '/usr/local/www/live/bin/taskQueue/taskQueueConfig.php';
}

define( 'LOGFILE', '/da0/logs/taskQueue/checkDeamon.log' );

$redisQueueAry  = array(
	'TaskQueue',
);

$ipaddr = file_get_contents('/etc/sysconfig/network-scripts/ifcfg-eth0');//读取服务器配置文件
preg_match('/IPADDR=[\"]?([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/', $ipaddr, $arr);//定位提取IP
$ipaddr = $arr[1];

foreach ($redisQueueAry as $queueStr)
{
    $className  = 'TaskQueue'.TaskQueueHost::$hostConfig[$queueStr].'Mod';
    $obj        = new $className ( $queueStr );
    
    $isRun      = isRunDeamon($queueStr, $isDev);
    
    $workIp     = $obj->getWorkProcessNoop();
    if (! $workIp || $workIp == $ipaddr)
    {
        runDeamon($queueStr, $isDev);
    }
    elseif ($isRun && $workIp != $ipaddr)
    {
        echo "error: work is run but ip error {$workIp} {$ipaddr}\n";
        file_put_contents(ROOT_DIR . "bin/taskQueuePid", 'checkDeamon ' . date("Y-m-d H:i:s") . "\n", FILE_APPEND);
    }
}

function isRunDeamon($redisQueueStr, $isDev)
{
    $output = `ps awx | grep TaskQueueDeamon.php`;
    $opter = " {$redisQueueStr} {$isDev}";
    $flag = strpos($output, $opter);
    if ($output && false === $flag)
    {
        return false;
    }
    elseif($output)
    {
        return true;
    }
    return false;
}

function runDeamon($redisQueueStr, $isDev)
{
    $output = `ps awx | grep TaskQueueDeamon.php`;
    $opter = " {$redisQueueStr} {$isDev}";
    $flag = strpos($output, $opter);
    $op = array();
    if ($output && false === $flag)
    {
        $comm   = 'nohup /usr/local/bin/php ' . ROOT_DIR . 'bin/taskQueue/taskQueueDeamon.php ' . $redisQueueStr . ' '.$isDev. ' >> /da0/logs/taskQueue/taskQueueDeamon_' . $redisQueueStr . '.log 2>>/da0/logs/taskQueue/taskQueueDeamon_' . $redisQueueStr . '.log & echo $!';
        exec( $comm,$op,$return );
        var_dump($op);
        var_dump( $return );       
        $str = date('Y-m-d H:i:s')." :: taskQueueDeamon 程序运行非正常停止,以重新启动.进程ID:".$op[0]."\n\n\n";
        unset( $op );
        file_put_contents( LOGFILE, $str, FILE_APPEND );
    }
}
?>