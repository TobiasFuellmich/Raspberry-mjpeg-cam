<?php
// ss init|cancel|start init and cancel start and cancel script, start gives move command
if($_POST['ss']==="init"){
	$lines=shell_exec('ps -ef | grep -c "/var/www/html/stepper/step.py.lock"');
	if (substr($lines,0,1)==2){
		shell_exec('nohup python /var/www/html/stepper/step.py.lock > /dev/null 2>/dev/null &');
	}
}else{
	if($_POST['ss']==="cancel"){
		shell_exec('pkill -f /var/www/html/stepper/step.py.lock');
	}else{
		if($_POST['ss']==="start"){
			if($_POST['dir']==="forward" or $_POST['dir']==="left" or $_POST['dir']==="right" or $_POST['dir']==="stop"
			or $_POST['dir']==="backward"){
				file_put_contents("/var/www/html/stepper/step_keep.lock", $_POST['dir']);
			}
		}
	}
}
?>
