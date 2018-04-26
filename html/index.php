<?php
$pwhash='';
/*$options = [
    'cost' => 11,
];
echo password_hash("", PASSWORD_BCRYPT, $options);
if(password_verify ( $_POST['pw'], $pwhash)!=1){
	readfile("password.html.lock");
	exit;
}*/
?>
<html>
<head>
<meta name="viewport" content="width=960, user-scalable=no">
<link rel="manifest" href="/manifest.json">
<title>stream cam</title>
<script>
'use strict';
var resolution=[960,540];
var in_request=false;
var xhttp;
var data="";
var canvas;
var ctx;
var img;
var lastfile="";
var lastcom="";
var imgsrc=new Array(160);
var spf=new Array(160);//secondes per frame
var frame=-1;
var last_stored_frame=0;
var vid_running=false;
var vid_stopped=false;
var connect;
var camoff_counter=0;
function setconnect(){
	var href=window.location.href;
	href=href.replace("http://","");
	href=href.replace("index.php","");
	connect=new WebSocket('ws://'+href);
	connect.binaryType="arraybuffer";
	connect.onopen=function (){
		connect.send("<?php echo $pwhash ?>")
	};
	connect.onclose=function(){
		console.log('Server closed WebSocket!');
	};
	connect.onerror =function (error){
		console.log('WebSocket Error '+error);
	};
	connect.onmessage =function(e){
		if(typeof e.data === "string"){
			camoff_counter++;
			if(camoff_counter==20){
				cam_status.iscamoff=true;
				cam_status.show_status("camoff","block");
				cam_status.show_status("loading","none");
			}
		}else{
			camoff_counter=0;
			if(cam_status.iscamoff){
				cam_status.iscamoff=false;
				if(!cam_status.ispaused){
				cam_status.show_status("loading","block");
				}
				cam_status.show_status("camoff","none");
			}
			var temp_ar=e.data;
			if (temp_ar){
				var fetched_frames=0;
				var info_sliced=new Uint8Array(temp_ar.slice(0,4));
				var infostr="";
				for (var j = 0; j < info_sliced.length; j++) {
					infostr += String.fromCharCode(info_sliced[j]);
				}
				fetched_frames=parseInt(infostr);
				var info_sliced=new Uint8Array(temp_ar.slice(0,4+13*fetched_frames));
				var infostr="";
				for (var j = 0; j < info_sliced.length; j++) {
					infostr += String.fromCharCode(info_sliced[j]);
				}
				//console.log(infostr+"ff: "+fetched_frames);//console.log(temp_ar.byteLength);
				var info = infostr.split("/");
				var strlen=info.slice(fetched_frames+1,fetched_frames*2+1);//gets all lengthes of images
				var temp_ar=temp_ar.slice(4+13*fetched_frames);
				var intlen=0,oldintlen=0;
				var first_frame=last_stored_frame;
				for(i=first_frame;i<first_frame+fetched_frames;i++){
					spf[i]=null;
					spf[i]=info[i-first_frame+1]*1000;
				}
				for(var i=first_frame;i<first_frame+fetched_frames;i++){
					intlen=oldintlen+parseInt(strlen[i-first_frame]);
					var ar = new Uint8Array(temp_ar.slice(oldintlen,intlen));
					var raw = "";
					for (var j = 0; j < ar.length; j++) {
						raw += String.fromCharCode(ar[j]);
					}
					//var raw = String.fromCharCode.apply(null,ar);
					imgsrc[i]=null;
					imgsrc[i]="data:image/jpeg;base64,"+btoa(raw);
					oldintlen=intlen;
				}
				temp_ar=null;
				ar=null;
				raw=null;
				var frame_c=first_frame-fetched_frames;
				if (frame_c<0){
					frame_c=frame_c+180;
				}
				if (frame<=frame_c){
					//console.log(frame+"->"+first_frame+" skipped:"+(first_frame-frame));
					if (frame==-1){
						frame=first_frame;
						window.requestAnimationFrame(process_vid);
					}else{
						frame=first_frame;
						img.src=imgsrc[frame];
						t=performance.now();
					}
				}
				if(cam_status.isloading){
					img.src=imgsrc[frame];
					t=performance.now();
				}
				last_stored_frame=(first_frame+fetched_frames)%180;
			}
		}
	};
}
if (window.XMLHttpRequest) {
    xhttp = new XMLHttpRequest();
    } else {
    //for IE6, IE5
    xhttp = new ActiveXObject("Microsoft.XMLHTTP");
}
xhttp.onreadystatechange = function() {
	if (xhttp.readyState == 4 && xhttp.status == 200) {
		//lädt
	}
	if (xhttp.readyState == 4) {
		in_request=false;
		//alert(xhttp.responseText);
	}
};
xhttp.ontimeout=function(){
	//timeout
};
function send_req(file,command,ismute){
	if (typeof ismute !== 'boolean') {
		ismute=false;
	}
	if(!in_request){
		in_request=true;
		var rand=Math.round(100000*Math.random());
		xhttp.open("POST", file, true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.timeout=2000;
		xhttp.send(command+"&pw=<?php echo $pwhash ?>&rand="+rand);
		lastfile=file;
		lastcom=command;
		return true;
	}else{
		if(!ismute){
			alert("Failed to send request, to busy.");
		}
		//alert("xhttp waiting on:" + lastfile + "?" + lastcom)
		return false;
	}
}
//process part
var d, t, t2;
//older browser
function process_vid(){
	if(!vid_running){
		vid_stopped=true;
		console.log("Stream stopped!");
	}else{
		t2=performance.now();
		if (frame!=last_stored_frame){
			if ((t2 -t)>=spf[frame]){
				if(cam_status.isloading){
					cam_status.show_status("loading","none");
					cam_status.isloading=false;
				}
				//document.getElementById("framec").innerHTML=frame;
				ctx.drawImage(img, 0, 0, resolution[0], resolution[1]);
				while((t2 -t)>=spf[frame]){
					t=t+spf[frame];
					frame++;
					if (frame==180){
						frame=0;
					}
				}
				if (frame!=last_stored_frame){
					img.src=imgsrc[frame];
				}else{
					if(!cam_status.isloading){
						cam_status.show_status("loading","block");
						cam_status.isloading=true;
						//console.log("waiting ...");
					}
					img.src="";
				}
			}
		}else{
			t=performance.now();
		}
		window.requestAnimationFrame(process_vid);
	}
}
function start_vid(){
	send_req('/vid_stream/m_jpeg_data_collecter.php', "com=start");
}
function stop_vid(){
	send_req('/vid_stream/m_jpeg_data_collecter.php', "com=stop");
}
function setup(){
	document.getElementById("status_symbol_play").style.display="block";
	document.getElementById("cam_config").style.display="none";
	window.addEventListener('resize', resize_screen);
	window.addEventListener('mouseup', cancel_movement);
	window.addEventListener('touchend', cancel_movement);
	canvas = document.getElementById('camcanvas');
	ctx = canvas.getContext('2d');
	img = new Image(960,540);
}
var turned=false;
var isfullscreen=false;
function resize_screen(){
	canvas = document.getElementById('camcanvas');
	ctx = canvas.getContext('2d');
	if (isfullscreen){
		document.getElementById("bg_cover").style.display="block";
		if(!turned){
			var body_width=document.body.clientWidth;
			var body_height=document.body.clientHeight;
			var resx=body_width/960;
			var resy=body_height/540;
			if(resx<resy){
				resolution=[resx*960,resx*540];
				document.getElementById("cam").style.left=(body_width*0.5-resolution[0]*0.5)+"px";
				document.getElementById("car_controls").style.left=(body_width*0.5-resolution[0]*0.5)+"px";
			}else{
				resolution=[resy*960,resy*540];
				document.getElementById("cam").style.left=(body_width*0.5-resolution[0]*0.5)+"px";
				document.getElementById("car_controls").style.left=(body_width*0.5-resolution[0]*0.5)+"px";
			}
			document.getElementById("cam_car_container").style.width=body_width+"px";
			document.getElementById("cam_car_container").style.height=body_height+"px";
			document.getElementById("cam_car_container").style.marginTop="0px";
			document.getElementById("cam_side_controlbar").style.left="auto";
			document.getElementById("cam_side_controlbar").style.right="10px";
			document.getElementById("cam_car_container").style.transform='rotate(0deg)';
		}else{
			var body_width=document.body.clientWidth;
			var body_height=document.body.clientHeight;
			var resx=body_height/960;
			var resy=body_width/540;
			if(resx<resy){
				resolution=[resx*960,resx*540];
				document.getElementById("cam").style.left=(body_height*0.5-resolution[0]*0.5)+"px";
				document.getElementById("car_controls").style.left=(body_height*0.5-resolution[0]*0.5)+"px";
			}else{
				resolution=[resy*960,resy*540];
				document.getElementById("cam").style.left=(body_height*0.5-resolution[0]*0.5)+"px";
				document.getElementById("car_controls").style.left=(body_height*0.5-resolution[0]*0.5)+"px";
			}
			document.getElementById("cam_car_container").style.width=body_height+"px";
			document.getElementById("cam_car_container").style.height=body_width+"px";
			document.getElementById("cam_car_container").style.marginTop=body_height+"px";
			document.getElementById("cam_side_controlbar").style.left="10px";
			document.getElementById("cam_side_controlbar").style.right="auto";
			document.getElementById("cam_car_container").style.transform='rotate(-90deg)';
		}
		document.getElementById("placeholder_main").style.width="0px";
		document.getElementById("placeholder_main").style.minWidth="auto";
		document.getElementById("cam_car_container").style.marginLeft="0px";
		document.getElementById("cam").style.height=resolution[1]+"px";
		document.getElementById("cam").style.top="0px";
		document.getElementById("cam").style.width=resolution[0]+"px";
		document.getElementById("car_controls").style.width=resolution[0]+"px";
		document.getElementById("car_controls").style.top="auto";
		document.getElementById("car_controls").style.bottom="0px";
		document.getElementById("car_controls").style.background="none";
		document.getElementById("content_body").style.overflow="hidden";
		document.getElementById("content_body").style.minHeight="initial";
		document.getElementById("content_body").style.minWidth="initial";
	}else{
		document.getElementById("bg_cover").style.display="none";
		resolution=[960,540];
		document.getElementById("placeholder_main").style.width="50%";
		document.getElementById("placeholder_main").style.minWidth="480px";
		document.getElementById("cam_car_container").style.marginLeft="-480px";
		document.getElementById("cam").style.height="540px";
		document.getElementById("cam").style.top="120px";
		document.getElementById("cam").style.width="960px";
		document.getElementById("cam").style.left="auto";
		document.getElementById("car_controls").style.left="auto";
		document.getElementById("car_controls").style.width="960px";
		document.getElementById("car_controls").style.top="690px";
		document.getElementById("car_controls").style.bottom="auto";
		document.getElementById("car_controls").style.background="#2d3e52";
		document.getElementById("content_body").style.overflow="auto";
		document.getElementById("cam_car_container").style.width="960px";
		document.getElementById("cam_car_container").style.height="830px";
		document.getElementById("cam_car_container").style.marginTop="auto";
		document.getElementById("cam_side_controlbar").style.left="auto";
		document.getElementById("cam_side_controlbar").style.right="10px";
		document.getElementById("cam_car_container").style.transform='rotate(0deg)';
	}
	img = new Image(resolution[0],resolution[1]);
	ctx.canvas.width=resolution[0];
	ctx.canvas.height=resolution[1];
}
function fullscreen_toggle(){
	isfullscreen=!isfullscreen;
	if( navigator.userAgent.match(/Android/i)
	|| navigator.userAgent.match(/webOS/i)
	|| navigator.userAgent.match(/iPhone/i)
	|| navigator.userAgent.match(/iPad/i)
	|| navigator.userAgent.match(/iPod/i)
	|| navigator.userAgent.match(/BlackBerry/i)
	|| navigator.userAgent.match(/Windows Phone/i)
	){
		resize_screen();
	}else{
		if (isfullscreen){
			if(document.documentElement.requestFullScreen) {
				document.documentElement.requestFullScreen();
			}else if(document.documentElement.mozRequestFullScreen) {
				document.documentElement.mozRequestFullScreen();
			}else if(document.documentElement.webkitRequestFullScreen) {
				document.documentElement.webkitRequestFullScreen();
			}
		}else{
			if(document.exitFullscreen) {
				document.exitFullscreen();
			}else if(document.webkitExitFullscreen) {
				document.webkitExitFullscreen();
			}else if (document.mozCancelFullScreen) {
				document.mozCancelFullScreen();
			}else if(document.msExitFullscreen) {
				document.msExitFullscreen();
			}
		}
	}
}
function turned_toggle(){
	turned=!turned;
	resize_screen();
}
function config_toggle(){
	if(document.getElementById("cam_config").style.display=="none"){
		document.getElementById("cam_config").style.display="block";
		document.getElementById("cam_config_shadow").style.display="block";
	}else{
		document.getElementById("cam_config").style.display="none";
		document.getElementById("cam_config_shadow").style.display="none";
	}
}
var cam_status={
	iscamoff:false,
	isloading:false,
	ispaused:true,
	show_status:function(sname, setto){
		switch(sname){
			case "pause":
				this.ispaused=true;
				document.getElementById("status_symbol_"+sname).style.display="block";
				setTimeout(
					function(){
					document.getElementById("status_symbol_"+sname).style.display="none"
					}
					, 1000);
			break;
			case "play":
				this.ispaused=false;
				document.getElementById("status_symbol_"+sname).style.display="block";
				setTimeout(
					function(){
					document.getElementById("status_symbol_"+sname).style.display="none"
					}
					, 1000);
			break;
			case "loading":
				document.getElementById("status_symbol_"+sname).style.display=setto;
			break;
			case "camoff":
				document.getElementById("status_symbol_"+sname).style.display=setto;
			break;
		}
	}
};
var isfirst_play=true;
function play_pause_stream(){
	if(cam_status.ispaused){
		if(cam_status.isloading && !cam_status.iscamoff){
			cam_status.show_status("loading","block");
		}
		vid_running=true;
		if(vid_stopped){
			vid_stopped=false;
			t=performance.now();
			window.requestAnimationFrame(process_vid);
		}
		if(isfirst_play){
			cam_status.show_status("play","block");
			cam_status.show_status("loading","block");
			cam_status.isloading=true;
			setconnect();
			isfirst_play=false;
		}else{
			cam_status.show_status("play","block");
		}
	}else{
		vid_running=false;
		cam_status.show_status("pause","block");
		if(cam_status.isloading){
			cam_status.show_status("loading","none");
		}
	}
}
function toggle_loading_symbol(){
	if(document.getElementById("status_symbol_loading").style.opacity!="0"){
		document.getElementById("status_symbol_loading").style.opacity="0";
	}else{
		document.getElementById("status_symbol_loading").style.opacity="1";
	}
}
//mouse for car controle
var car_command="";
var car_mousedown=false;
var car_current_dir="";
var car_interval;
var car_isready=false;
var car_nr=1;
function step_start_move(nr){
	if(car_isready){
		document.getElementById("control_stepper"+nr+"_b_p2").style.display="none";
		document.getElementById("control_stepper"+nr+"_b_p1").style.fill="#eeeeee";
		switch(nr){
			case 1: car_current_dir ="left";
			break;
			case 2: car_current_dir ="forward";
			break;
			case 3: car_current_dir ="right";
			break;
		}
		car_nr=nr;
		car_mousedown=true;
		clearInterval(car_interval);
		continous_send();
		car_interval=setInterval(continous_send,3000);
	}
}
function step_end_move(nr){
	document.getElementById("control_stepper"+nr+"_b_p2").style.display="block";
	document.getElementById("control_stepper"+nr+"_b_p1").style.fill="#000000";
}
function continous_send(){
	if (car_mousedown){
		send_req('/stepper/stepper_move.php', "dir="+car_current_dir+"&ss=start");
	}
}
function cancel_movement() {
  if (car_mousedown){
	  clearInterval(car_interval);
	  if(send_req('/stepper/stepper_move.php', "dir=stop&ss=start", true)){
		  car_mousedown=false;
		  step_end_move(car_nr);
	  }else{
		  car_interval=setInterval(car_cancel,50);
	  }
  }
}
function car_cancel(){
	if(send_req('/stepper/stepper_move.php', "dir=stop&ss=start", true)){
		car_mousedown=false;
		step_end_move(car_nr);
		clearInterval(car_interval);
	}
}
function init_car_move(){
	send_req('/stepper/stepper_move.php', "ss=init");
	car_isready=true;
}
function cancel_car_move(){
	send_req('/stepper/stepper_move.php', "ss=cancel");
	car_isready=false;
}
function move_cam(dir){
	send_req('/stepper/cam_step.php', "dir="+dir);
}
</script>
<style>
body{
	-webkit-user-select: none;
	-khtml-user-select: none;
	-moz-user-select: none;
	-o-user-select: none;
	user-select: none;
	overflow:auto;
	margin:0px;
	white-space: nowrap;
}
#content_body{
	min-width:960px;
	min-height:830px;
	overflow:hidden;
	left:0px;
	top:0px;
	width:100%;
	height:100%;
}
#bg_svg{
	position:absolute;
	min-width:960px;
	min-height:830px;
	width:100%;
	height:100%;
	left:0px;
	top:0px;
}
#bg_cover{
	position:absolute;
	display:none;
	background:#2d3e52;
	min-width:960px;
	min-height:830px;
	overflow:hidden;
	left:0px;
	top:0px;
	width:100%;
	height:100%;
}
#placeholder_main{
	display: inline-block;
	float:left;
	min-width:460px;
	width:50%;
	height:100%;
}
#cam_car_container{
	display: inline-block;
	float:left;
	margin-left:-480px;
	width:960px;
	height:830px;
	transform-origin:top left;
}
#cam{
	background:#2d3e52;
	position: absolute;
	top:120px;
	width: 960px;
	height: 540px;
}
#cam_side_controlbar{
	position:absolute;
	min-height:540px;
	height:100%;
	width:50px;
	top:0px;
	right:10px;
}
#cam_fullscreen_b{
	position:absolute;
	width:50px;
	height:50px;
	top:10px;
	left:0px;
}
#cam_fullscreen_b svg{
	position:absolute;
	width:50px;
	height:50px;
	left:0px;
	top:0px;
}
#cam_fullscreen_b:hover svg{
	width:40px;
	height:40px;
	left:5px;
	top:5px;
}
#cam_fullscreen_b:hover{
	cursor:pointer;
}
.cam_stepper_b{
	position:absolute;
	width:50px;
	height:50px;
	bottom:40%;
	left:0px;
}
.cam_stepper_b:hover circle{
	fill:#2d3e52;
}
.cam_stepper_b:hover{
	cursor:pointer;
}
.cam_stepper_b svg{
	position:absolute;
	width:100%;
	height:100%;
}
#cam_status_symbols{
	position:absolute;
	height:16%;
	width:9%;
	max-height:120px;
	max-width:120px;
	left:45.5%;
	top:42%;
}
#cam_status_symbols svg{
	position:absolute;
	display:none;
	width:100%;
	height:100%;
}
#cam_status_symbols:hover{
	cursor:pointer;
}
#car_controls{
	background:#2d3e52;
	position: absolute;
	top:690px;
	width: 960px;
	height: 120px;
}
.control_stepper_b{
	position:absolute;
	height:90px;
	width:90px;
	top:15px;
	transform-origin:center center;
}
.control_stepper_b:hover circle{
	fill:#2d3e52;
}
.control_stepper_b:hover{
	cursor:pointer;
}
.control_stepper_b svg{
	position:absolute;
	width:100%;
	height:100%;
}
#config_opener_b{
	position:absolute;
	background:url("gear.png") no-repeat;
	width:3vw;
	height: 3vw;
	min-width:50px;
	min-height:50px;
	left:20px;
	top:20px;	
	background-size:auto 100%;
}
#config_opener_b:hover{
	cursor:pointer;
}
#cam_config{
	background:#354a5f;
	position: absolute;
	left: 0px;
	top:0px;
	width:192px;
	height: 100%;
	min-height: 830px;
	overflow:auto;
	display:none;
}
#cam_config_shadow{
	position:absolute;
	display:none;
	background:black;
	min-width:960px;
	min-height:830px;
	overflow:hidden;
	left:0px;
	top:0px;
	width:100%;
	height:100%;
	opacity:0.4;
}
.cam_config_b{
	background:#2d3e52;
	position:relative;
	float:left;
	width:180px;
	height:30px;
	top:33%;
	margin-top:10px;
	margin-left:5px;
	padding-left:2px;
	color:#eeeeee;
	font-size:21px;
	border-radius:2px;
}
.cam_config_b:hover{
	background:#243549;
	box-shadow: 0px 2px 0px #eeeeee;
	cursor:pointer;
}
</style>
</head>
<body onload="setup();">
<div id="content_body">
	<svg viewbox="0 0 120 120" id="bg_svg" preserveAspectRatio="none">
		<path d="M0 0 L40 0 L0 40 Z" fill="#c23a2c"/>
		<path d="M40 0 L80 0 L0 80 L0 40 Z" fill="#f2c311"/>
		<path d="M80 0 L120 0 L0 120 L0 80 Z" fill="#f39c11"/>
		<path d="M120 0 L120 40 L40 120 L0 120 Z" fill="#e77d25"/>
		<path d="M120 40 L120 80 L80 120 L40 120 Z" fill="#d55403"/>
		<path d="M120 80 L120 120 L80 120 Z" fill="#e84c3d"/>
	</svg>
	<div id="bg_cover"></div>
	<div id="placeholder_main"></div>
	<div id="cam_car_container">
		<div id="cam">
			<canvas style="" id="camcanvas" width="960" height="540" onclick="play_pause_stream();"></canvas>
			<div id="cam_side_controlbar">
				<div id="cam_fullscreen_b" onclick="fullscreen_toggle()">
					<svg viewbox="0 0 100 100">
						<path d="M5 30 L5 5 L30 5" stroke-width="5px" stroke="#eeeeee" fill="none"/>
						<path d="M70 5 L95 5 L95 30" stroke-width="5px" stroke="#eeeeee" fill="none"/>
						<path d="M95 70 L95 95 L70 95" stroke-width="5px" stroke="#eeeeee" fill="none"/>
						<path d="M30 95 L5 95 L5 70" stroke-width="5px" stroke="#eeeeee" fill="none"/>
					</svg>
				</div>
				<div class="cam_stepper_b" onclick="move_cam('up')" style="bottom:60%">
					<svg viewbox="0 0 50 50">
						<circle cx="25" cy="25" r="25" fill="#354a5f"/>
						<path d="M10 40 l15 -30 l15 30 Z" style="fill:#000000;" />
						<path d="M10 38 l15 -30 l15 30 Z" style="fill:#eeeeee;" />
					</svg>
					<div style="position:absolute;width:100%;height:100%;"></div>
				</div>
				<div class="cam_stepper_b" onclick="move_cam('down')" style="transform:rotate(180deg)">
					<svg viewbox="0 0 50 50">
						<circle cx="25" cy="25" r="25" fill="#354a5f"/>
						<path d="M10 40 l15 -30 l15 30 Z" style="fill:#000000;" />
						<path d="M10 38 l15 -30 l15 30 Z" style="fill:#eeeeee;" />
					</svg>
					<div style="position:absolute;width:100%;height:100%;"></div>
				</div>
			</div>
			<div id="cam_status_symbols" onclick="play_pause_stream();">
				<svg viewbox="0 0 120 120" id="status_symbol_pause">
					<circle cx="60" cy="60" r="60" fill="#354a5f"/>
					<rect x="35" y="35" width="20" height="50" fill="#eeeeee"/>
					<rect x="65" y="35" width="20" height="50" fill="#eeeeee"/>
				</svg>
				<svg viewbox="0 0 120 120" id="status_symbol_play">
					<circle cx="60" cy="60" r="60" fill="#354a5f"/>
					<path d="M40 30 l0 60 l60 -30 Z" fill="#eeeeee" />
				</svg>
				<svg viewbox="0 0 120 120" id="status_symbol_loading">
					<circle cx="60" cy="60" r="60" fill="#354a5f"/>
					<image xlink:href="loading.gif" x="0" y="0" height="120px" width="120px"/>
				</svg>
				<svg viewbox="0 0 120 120" id="status_symbol_camoff">
					<circle cx="60" cy="60" r="60" fill="#354a5f"/>
					<circle cx="48" cy="49" r="8" fill="#eeeeee"/>
					<circle cx="60" cy="51" r="6" fill="#eeeeee"/>
					<rect x="35" y="55" width="38" height="25" fill="#eeeeee"/>
					<path d="M71 62 l14 -7 l0 25 l-14 -7 Z" fill="#eeeeee"/>
					<path d="M32 32 l56 56" stroke="#eeeeee" stroke-width="3px"/>
					<path d="M88 32 l-56 56" stroke="#eeeeee" stroke-width="3px"/>
				</svg>
			</div>
		</div>
		<div id="car_controls">
			<div class="control_stepper_b" onmousedown="step_start_move(1);" ontouchstart="step_start_move(1);" ontouchend="step_end_move(1);" style="left:20%;transform:rotate(-90deg);">
				<svg viewbox="0 0 50 50">
					<circle cx="25" cy="25" r="25" fill="#354a5f"/>
					<path id="control_stepper1_b_p1" d="M10 40 l15 -30 l15 30 Z" style="fill:#000000;" />
					<path id="control_stepper1_b_p2" d="M10 38 l15 -30 l15 30 Z" style="fill:#eeeeee;" />
				</svg>
				<div style="position:absolute;width:100%;height:100%;"></div><!-- without mouseup will get triggered if moved over elements -->
			</div>
			<div class="control_stepper_b" onmousedown="step_start_move(2);" ontouchstart="step_start_move(2);" style="left:46%;">
				<svg viewbox="0 0 50 50">
					<circle cx="25" cy="25" r="25" fill="#354a5f"/>
					<path id="control_stepper2_b_p1" d="M10 40 l15 -30 l15 30 Z" style="fill:#000000;" />
					<path id="control_stepper2_b_p2" d="M10 38 l15 -30 l15 30 Z" style="fill:#eeeeee;" />
				</svg>
				<div style="position:absolute;width:100%;height:100%;"></div>
			</div>
			<div class="control_stepper_b" onmousedown="step_start_move(3);" ontouchstart="step_start_move(3);" ontouchend="step_end_move(3);" style="left:72%;transform:rotate(90deg);">
				<svg viewbox="0 0 50 50">
					<circle cx="25" cy="25" r="25" fill="#354a5f"/>
					<path id="control_stepper3_b_p1" d="M10 40 l15 -30 l15 30 Z" style="fill:#000000;" />
					<path id="control_stepper3_b_p2" d="M10 38 l15 -30 l15 30 Z" style="fill:#eeeeee;" />
				</svg>
				<div style="position:absolute;width:100%;height:100%;"></div>
			</div>
		</div>
	</div>
	<div id="cam_config_shadow" onclick="config_toggle()"></div>
	<div id="cam_config">
		<div class="cam_config_b" onclick="turned_toggle();">Rotate Screen</div>
		<div class="cam_config_b" onclick="toggle_loading_symbol();">Toggle Loading S.</div>
		<div class="cam_config_b" onclick="start_vid();">Camera ON</div>
		<div class="cam_config_b" onclick="stop_vid();">Camera OFF</div>
		<div class="cam_config_b" onclick="init_car_move();">Start Car</div>
		<div class="cam_config_b" onclick="cancel_car_move();">Stop Car</div>
	</div>
	<div id="config_opener_b" onclick="config_toggle()"></div>
</div>
</body>
</html>
