<?php
if($_POST['pw']!=='$2y$11$mqy8oNP5VxijYR5OBTj4O.hZxVqTojjct/q.1FjyynTthYQxO.Jni'){
	exit;
}
if($_POST['dir']==="up"){
	$lines=shell_exec('ps -ef | grep -c "/var/www/html/stepper/cam_step.py.lock"');
	if (substr($lines,0,1)==2){
		shell_exec('nohup python /var/www/html/stepper/cam_step.py.lock 1 > /dev/null 2>/dev/null &');
	}
}
if($_POST['dir']==="down"){
	$lines=shell_exec('ps -ef | grep -c "/var/www/html/stepper/cam_step.py.lock"');
	if (substr($lines,0,1)==2){
		shell_exec('nohup python /var/www/html/stepper/cam_step.py.lock 2 > /dev/null 2>/dev/null &');
	}
}
?>