<?php
/* An easy way to keep in track of external processes.
 * Ever wanted to execute a process in php, but you still wanted to have somewhat controll of the process ? Well.. This is a way of doing it.
 * @compability: Linux only. (Windows does not work).
 * @author: Peec
 */
class ProcessLib{
    private $pid;
    private $command;
    private $logfiles;

    public function __construct( $cl=false, $logfiles=' >/dev/null  2>&1 ' ){
        if ($cl){
            $this->command = $cl;
            $this->logfiles = $logfiles;
            $this->runCom( );
        }
    }
    private function runCom( ){
        $cmd = 'nohup '.$this->command.' '.$this->logfiles.' & echo $!';
        echo "{$cmd}\n";
        exec($cmd,$op);
        echo $op . "\n";
        $this->pid = (int)$op[0];
    }

    public function setPid($pid){
        $this->pid = $pid;
    }

    public function getPid(){
        return $this->pid;
    }

    public function status(){
        $cmd = 'ps -p '.$this->pid;
        exec($cmd,$op);
        if (!isset($op[1])){
            return false;
        }
        return true;
    }

    public function start(){
        if ($this->command != '')
        {
        	$this->runCom();
        	return true;
        }
        else 
        {
        	return false;
        }
    }

    public function stop(){
        $cmd = 'kill '.$this->pid;
        exec($cmd);
        if (!$this->status()) {
            return true;
        }
        return false;
    }
}