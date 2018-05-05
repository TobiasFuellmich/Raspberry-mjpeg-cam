<?php
if($_POST['pw']!=='$2y$11$mqy8oNP5VxijYR5OBTj4O.hZxVqTojjct/q.1FjyynTthYQxO.Jni'){
	exit;
}
$lines=shell_exec('ps -ef | grep -c "/var/www/html/vid_stream/camera.py.lock"');
if($_POST['com']==='start'){
	if (substr($lines,0,1)==2){
		shell_exec("python /var/www/html/vid_stream/camera.py.lock > /dev/null 2>/dev/null &");
	}
}
if(strcmp($_POST['com'],'stop')===0){
	if (substr($lines,0,1)==3){
		shell_exec('pkill -f /var/www/html/vid_stream/camera.py.lock');
	}
}
?>