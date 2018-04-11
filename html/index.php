<?php
/*$options = [
    'cost' => 11,
];
echo password_hash("", PASSWORD_BCRYPT, $options);
if(password_verify ( $_POST['pw'], '')!=1){
	readfile("password.html.lock");
	exit;
}*/
?>
<html>
<head>
<title>stream cam</title>
<script>
'use strict';
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
		connect.send("fgsdgdgh+456zh54tgh6!2dde#");
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
					frame_c=frame_c+156;
				}
				if (frame<=frame_c){
					console.log(frame+"->"+first_frame+" skipped:"+(first_frame-frame));
					if (frame==-1){
						frame=first_frame;
						window.requestAnimationFrame(process_vid);
					}else{
						frame=first_frame;
						img.src=imgsrc[frame];
						t=performance.now()+100;
					}
				}
				if(cam_status.isloading){
					img.src=imgsrc[frame];
					t=performance.now()+100;
				}
				last_stored_frame=(first_frame+fetched_frames)%156;
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
function send_req(file,command){
	if(!in_request){
		in_request=true;
		var rand=Math.round(100000*Math.random());
		xhttp.open("POST", file, true);
		xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhttp.timeout=5000;
		xhttp.send(command+"&rand="+rand);
		lastfile=file;
		lastcom=command;
		return true;
	}else{
		if(instatus){
			alert("xhttp waiting on:" + lastfile + "?" + lastcom)
		}
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
				ctx.drawImage(img, 0, 0);
				while((t2 -t)>=spf[frame]){
					t=t+spf[frame];
					frame++;
					if (frame==156){
						frame=0;
					}
				}
				if (frame!=last_stored_frame){
					img.src=imgsrc[frame];
				}else{
					if(!cam_status.isloading){
						cam_status.show_status("loading","block");
						cam_status.isloading=true;
						console.log("waiting ...");
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
	canvas = document.getElementById('camcanvas');
	ctx = canvas.getContext('2d');
	img = new Image(640,480);
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
</script>
<link rel="stylesheet" type="text/css" href="shared.css"/>
<style>
#cam_filter{
	background:#c55151;
	position: absolute;
	left: 50%;
	top:70px;
	margin-left: -490px;
	width: 130px;
	height: 480px;
}
.cam_filter_b{
	position:relative;
	width: 130px;
	height: 80px;
	float:left;
	box-shadow:inset 0px 0px 10px black;
}
.cam_filter_b:hover{
	box-shadow:inset 0px 0px 15px black;
	cursor:pointer;
}
#cam{
	background:#333333;
	position: absolute;
	left: 50%;
	top:70px;
	margin-left: -350px;
	width: 640px;
	height: 480px;
}
#cam_status_symbols{
	position:absolute;
	width:100px;
	height:100px;
	left:50%;
	margin-left:-50px;
	top:190px;
}
#cam_status_symbols svg{
	position:absolute;
	display:none;
}
#cam_status_symbols:hover{
	cursor:pointer;
}
#cam_any{
	background:#c55151;
	position: absolute;
	left: 50%;
	top:70px;
	margin-left: 300px;
	width: 190px;
	height: 280px;
	color:#eeeeee;
	text-align:center;
}
.cam_any_control{
	background:#333333;
	margin-left:20px;
	margin-top:10px;
	width:150px;
	height:20px;
	box-shadow:0px 0px 10px black;
}
.cam_any_control:hover{
	box-shadow:0px 0px 5px black;
	cursor:pointer;
}
#cam_move{
	background:#c55151;
	position: absolute;
	left: 50%;
	top:360px;
	margin-left: 300px;
	width: 190px;
	height: 190px;
}
.cam_move_b{
	position:absolute;
}
</style>
</head>
<body onload="setup();">
<!--here was a header-->
	<div id="cam_filter">
		<div class="cam_filter_b" id="framec"></div>
		<div class="cam_filter_b"></div>
		<div class="cam_filter_b"></div>
		<div class="cam_filter_b"></div>
		<div class="cam_filter_b"></div>
		<div class="cam_filter_b"></div>
	</div>
	<div id="cam" onclick="play_pause_stream();">
		<canvas style="" id="camcanvas" width="640" height="480"></canvas>
		<div id="cam_status_symbols">
			<svg width="100" height="100" id="status_symbol_pause">
				<circle cx="50" cy="50" r="47" fill="#c55151" stroke-width="3px" stroke="#eeeeee"/>
				<rect x="25" y="25" width="20" height="50" fill="#333333"/>
				<rect x="55" y="25" width="20" height="50" fill="#333333"/>
			</svg>
			<svg width="100" height="100" id="status_symbol_play">
				<circle cx="50" cy="50" r="47" fill="#c55151" stroke-width="3px" stroke="#eeeeee"/>
				<path d="M30 20 l0 60 l60 -30 Z" fill="#333333" />
			</svg>
			<svg width="100" height="100" id="status_symbol_loading">
				<circle cx="50" cy="50" r="47" fill="#c55151" stroke-width="3px" stroke="#eeeeee"/>
				<rect x="25" y="25" width="20" height="20" fill="#333333" visibility="hidden">
					<animate begin="0s; anim4.end-10ms" dur="0.01s" attributeName="visibility" values="hidden;visible" fill="freeze"/>
					<animate begin="0s; anim4.end" dur="0.5s" attributeName="width" values="20;50;20"/>
					<animate begin="0.25s; anim4.end + 0.25s" dur="0.25s" attributeName="x" values="25;55"/>
					<animate id="anim1" begin="0.49s; anim4.end + 0.49s" dur="0.01s" attributeName="visibility" values="visible;hidden" fill="freeze"/>
				</rect>
				<rect x="55" y="25" width="20" height="20" fill="#333333" visibility="hidden">
					<animate begin="anim1.end-10ms" dur="0.01s" attributeName="visibility" values="hidden;visible" fill="freeze"/>
					<animate begin="anim1.end" dur="0.5s" attributeName="height" values="20;50;20"/>
					<animate begin="anim1.end + 0.25s" dur="0.25s" attributeName="y" values="25;55"/>
					<animate id="anim2" begin="anim1.end + 0.49s" dur="0.01s" attributeName="visibility" values="visible;hidden" fill="freeze"/>
				</rect>
				<rect x="55" y="55" width="20" height="20" fill="#333333" visibility="hidden">
					<animate begin="anim2.end-10ms" dur="0.01s" attributeName="visibility" values="hidden;visible" fill="freeze"/>
					<animate begin="anim2.end" dur="0.5s" attributeName="width" values="20;50;20"/>
					<animate begin="anim2.end" dur="0.5s" attributeName="x" values="55;25;25"/>
					<animate id="anim3" begin="anim2.end + 0.49s" dur="0.01s" attributeName="visibility" values="visible;hidden" fill="freeze"/>
				</rect>
				<rect x="25" y="55" width="20" height="20" fill="#333333" visibility="hidden">
					<animate begin="anim3.end-10ms" dur="0.01s" attributeName="visibility" values="hidden;visible" fill="freeze"/>
					<animate begin="anim3.end" dur="0.5s" attributeName="height" values="20;50;20"/>
					<animate begin="anim3.end" dur="0.5s" attributeName="y" values="55;25;25"/>
					<animate id="anim4" begin="anim3.end + 0.49s" dur="0.01s" attributeName="visibility" values="visible;hidden" fill="freeze"/>
				</rect>
			</svg>
			<svg width="100" height="100" id="status_symbol_camoff">
				<circle cx="50" cy="50" r="47" fill="#c55151" stroke-width="3px" stroke="#eeeeee"/>
				<circle cx="38" cy="39" r="8" fill="#333333"/>
				<circle cx="50" cy="41" r="6" fill="#333333"/>
				<rect x="25" y="45" width="38" height="25" fill="#333333"/>
				<path d="M61 52 l14 -7 l0 25 l-14 -7 Z" fill="#333333"/>
				<path d="M22 22 l56 56" stroke="#eeeeee" stroke-width="3px"/>
				<path d="M78 22 l-56 56" stroke="#eeeeee" stroke-width="3px"/>
			</svg>
		</div>
	</div>
	<div id="cam_any">
	<div class="cam_any_control" onclick="start_vid();">Starte Kamera</div>
	<div class="cam_any_control" onclick="stop_vid();">Stoppe Kamera</div>
	</div>
	<div id="cam_move">
		<svg width="50" height="50" class="cam_move_b" style="left:70px;top:10px;transform:rotate(0deg)">
			<path d="M25 50 l0 -50 l-25 50 Z" style="fill:#222222;" />
			<path d="M25 50 l0 -50 l25 50 Z" style="fill:#444444;" />
		</svg>
		<svg width="50" height="50" class="cam_move_b" style="left:130px;top:70px;transform:rotate(90deg)">
			<path d="M25 50 l0 -50 l-25 50 Z" style="fill:#222222;" />
			<path d="M25 50 l0 -50 l25 50 Z" style="fill:#444444;" />
		</svg>
		<svg width="50" height="50" class="cam_move_b" style="left:70px;top:130px;transform:rotate(180deg)">
			<path d="M25 50 l0 -50 l-25 50 Z" style="fill:#222222;" />
			<path d="M25 50 l0 -50 l25 50 Z" style="fill:#444444;" />
		</svg>
		<svg width="50" height="50" class="cam_move_b" style="left:10px;top:70px;transform:rotate(270deg)">
			<path d="M25 50 l0 -50 l-25 50 Z" style="fill:#222222;" />
			<path d="M25 50 l0 -50 l25 50 Z" style="fill:#444444;" />
		</svg>
	</div>
</body>
</html>

