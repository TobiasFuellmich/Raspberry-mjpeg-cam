<?php
/*$options = [
    'cost' => 11,
];
echo password_hash("", PASSWORD_BCRYPT, $options);*/
/*if(password_verify ( $_POST['pw'], '$2y$11$qIJ18JhwTFojIrwKKXv30e6khtIi4YYY0GUZqbKAfe6fZombAlabG')!=1){
	readfile("password.html.lock");
	exit;
}*/
$lines=shell_exec('ps -ef | grep -c "/var/www/html/vid_stream/camera.py.lock"');
if(strcmp($_POST['com'],'start')===0){
	if (substr($lines,0,1)==2){
		shell_exec("python /var/www/html/vid_stream/camera.py.lock &");
	}
}
if(strcmp($_POST['com'],'stop')===0){
	if (substr($lines,0,1)==3){
		shell_exec('pkill -f /var/www/html/vid_stream/camera.py.lock');
	}
}
?>