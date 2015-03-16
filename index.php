<?php

require_once('default_config.php');

$link = mysql_connect($mysql_host, $mysql_login, $mysql_password)  or die ("Unable to connect to DB. ");
mysql_set_charset('utf8',$link);
$selected = mysql_select_db($mysql_db, $link);

?><!DOCTYPE html>
<html>
<head>
<title>Lab 1</title>
<meta charset="utf-8">
<style>
	body {
		margin: 0;
		padding: 0;
		overflow: hidden;
		font-size:14px; 
		font-family:Arial;
		-webkit-user-select: none; /* Chrome/Safari */        
		-moz-user-select: none; /* Firefox */
		-ms-user-select: none; /* IE10+ */
		background-color:#000;
	}
	
	#info {
		position: absolute;
		top: 0px;
		font-size: 30px;
		left: 10px;
		text-align: center;
		z-index: 100;
		display:block;
		background-image:url('counter.png');
		width:131px;
		height:192px;
	}
	#counter1 {
		position: absolute;
		left:50px;
		top:50px;
		color:#A00;
	}
	#counter2 {
		position: absolute;
		left:50px;
		top:125px;
		color:#00A;
	}
	.window {
		position: absolute;
		width: 500px;
		height: 400px;
		margin-left: -250px;
		margin-top: -200px;
		background-color: #555;
		visibility: hidden;
		left:50%;
		top:50%;
		padding-left:20px;
		padding-top:15px;
	}
	
	#record_list {
		width: calc(100% - 20px);
		height: calc(100% - 170px);
		background-color: #999;
		overflow-y: scroll;
	}
	
	.button {
		text-align:center;
		line-height:30px;
		vertical-align:middle;
		padding-left:10px;
		padding-right:10px;
		background-color:#999;
		margin-top:10px;
		width:100px;
		cursor:default;
	}
	
	.button:hover {
		background-color:#AAA;
	}
	
	#start_button {
		visibility:hidden;
		background-color: #669966;
	}
	
	#start_button:hover {
		visibility:hidden;
		background-color: #7A7;
	}
	
	#progress_bar {
		width:300px;
		height:20px;
		background-color: #555;
		position:absolute;
		left:calc(50% - 150px);
		top:calc(100% - 40px);
	}
	
	#progress_line {
		width: 0%;
		height:100%;
		background-color: #2F2;
	}
	
</style>
<script src="//vk.com/js/api/xd_connection.js?2" type="text/javascript"></script>

<!-- libs -->
<script src="js/three.js" type="text/javascript"></script>
<script src="js/Mirror.js" type="text/javascript"></script>
<script src="js/loaders/ObjLoader.js" type="text/javascript"></script>
<script src="client.js" type="text/javascript"></script>
<script src="tools.js" type="text/javascript"></script>

<script>
	var userId = <?php if ($_GET["viewer_id"]) echo $_GET["viewer_id"]; else echo "-1";?>;
	var apiId = <?php if ($_GET["api_id"]) echo $_GET["api_id"]; else echo "-1";?>;
	var authKey = "<?php if ($_GET["auth_key"]) echo $_GET["auth_key"]; else echo "-1";?>";
    var scene, camera, renderer;
    var geometry, material, material1;
    var mesh;
    var tank1;
    var tank2;
    var tank3;
    var object = {};
    var circleRadius = 10;
    var circleSegments = 10;
    var circleCount = 1000;
    var collusions = false;
    var speedX = 0;
    var speedY = 0;
    var bullets = [];
    var bulletGeometry;
    var headAngle = 0;
    var enemies = [];
    var counter = 0;
    var counterBlue = 0;
    var enemyWidth = 25;
    var playerSpeed = 6;
    var playerAngleSpeed = 0.05;
    var play = false;
    var bodyAngleSpeed = 0;
    var player = {x:0,y:0,angle:0}
    var field = 0;
    var sunLight;
    var moonLight;
    var sunAngle = 0;
    var lightDist = 100;
    var mapHeight = 200;
    var mapWidth = 200;
    var train = 0;
    var trainPause = 500;
    var bossCount = 0;
    var verticalMirror;
    var verticalMirror2;
    var verticalMirror3;
    var tree;
    var client = new Client("backend.php");
    var timerId;
    var shadows = false;
    var mirrors = false;
    var trainSpeed = 2;
    var tankLevels = [];
    var curTankLevel = 0;
    var maxEnemyCount = 5;
    var maxEnemySpeed = 0;
    var isInitialized = false;
    var records = [];
    var fullLoadCount = 0;
    var star = null;
    var starTemplate = null;

	function getWidth() {
		if (self.innerWidth) {
		   return self.innerWidth;
		}
		else if (document.documentElement && document.documentElement.clientHeight){
			return document.documentElement.clientWidth;
		}
		else if (document.body) {
			return document.body.clientWidth;
		}
		return 0;
	}

	function getHeight() {
		if (self.innerHeight) {
			return self.innerHeight;
		}

		if (document.documentElement && document.documentElement.clientHeight) {
			return document.documentElement.clientHeight;
		}

		if (document.body) {
			return document.body.clientHeight;
		}
		return 0;
	}

	function isInRange(start,end,value)
	{
		if ((value > start) && (value < end))
			return true;
		return false;
	}

	function isCollusion(mesh, point, size)
	{
		if (isInRange(mesh.position.x - size, mesh.position.x + size, point.x) &&
				isInRange(mesh.position.y - size, mesh.position.y + size, point.y))
			return true;
		return false;
	}

	function createEnemy(x,y,z,health,color,type,size)
	{
		var enemyMesh = new THREE.Mesh( new THREE.SphereGeometry(size, 6, 6 ), 
											new THREE.MeshPhongMaterial( { color:color }));
		enemyMesh.position.x = x;
		enemyMesh.position.y = y;
		enemyMesh.position.z = z;
		enemies[enemies.length] = {mesh:enemyMesh, health:health, type:type, speed: maxEnemySpeed};
		enemyMesh.castShadow = true;
		enemyMesh.receiveShadow = true;
		scene.add(enemyMesh);
	}

	function genEnemy(pos)
	{
		var posList = [{x: -300,y: -300}, {x: 300,y: -300}, {x: 300,y: 300}, {x: -300,y: 300}];
		var x = getRand(posList[pos].x, - posList[pos].x);
		var y = posList[pos].y;
		createEnemy(x,y,20,2, 0xff2200, "red",15);
	}

	function enemyCtrl()
	{
		if (enemies.length < maxEnemyCount)
			genEnemy(getRand(0,3));
			
		for (var j in bullets)
		{
			var bullet = bullets[j].mesh;
			if ((bullet.position.x > 400) || (bullet.position.x < - 400) ||
					(bullet.position.y > 400) || (bullet.position.y < - 400))
			{
				bullets.splice(j,1);
				scene.remove(bullet);	
			}
		}
		for (var i in enemies)
		{
			if (isCollusion(mesh,enemies[i].mesh.position,30))
			{
				gameOver();
				break;
			}
			var d1 = -enemies[i].mesh.position.x + mesh.position.x;
			var d2 = enemies[i].mesh.position.y - mesh.position.y;
			var angle = Math.atan2(d2,d1);
			maxEnemySpeed =  2 * (counter + 50) / 200
			if (maxEnemySpeed > 4)
				maxEnemySpeed = 4;
			if (maxEnemySpeed < 1)
				maxEnemySpeed = 1;
			maxEnemyCount = counter / 30;
			if (maxEnemyCount < 5)
				maxEnemyCount = 5;
			enemies[i].mesh.position.x += Math.cos(angle) * enemies[i].speed;
			enemies[i].mesh.position.y += -Math.sin(angle) * enemies[i].speed;
			if (enemies[i].speed < maxEnemySpeed)
				enemies[i].speed += 0.01;
			for (var j in bullets)
			{
				if (isCollusion(enemies[i].mesh,bullets[j].mesh.position,enemyWidth / 2))
				{
					scene.remove(bullets[j].mesh);			
					bullets.splice(j,1);
					enemies[i].health -= tankLevels[curTankLevel].power;
					enemies[i].speed =  - maxEnemySpeed / 3;
					if (enemies[i].health <= 0)
					{
						scene.remove(enemies[i].mesh);
						if (enemies[i].type == "red")
							counter += 1;
						if (enemies[i].type == "blue")
						{
							counter += 20;
						}
						enemies.splice(i,1);
						testTankLevel();
					}
				}
			}
		}
		document.getElementById("counter1").innerHTML = counter;
		document.getElementById("counter2").innerHTML = counterBlue;
	}

	function trainCtrl()
	{
		trainPause -= 1;
		if (trainPause <= 0)
		{
			train.position.y += trainSpeed;
			if ((train.position.y > 0) && (train.position.y < 10) && (bossCount < 1))
			{
				createEnemy(-340,train.position.y + 15,30,10,0x4444ff,"blue",20);
				bossCount += 1;
			}
			if (train.position.y > 500)
			{
				trainPause = 500;
				train.position.y = -600;
				bossCount = 0;
			}
		}
	}



	function starsCtrl()
	{
		if (star == null)
			star = setObject(starTemplate,getRand(-200,200),getRand(-200,200), 50,0);
		star.rotation.z += 0.1;
		star.rotation.я += 0.01;
		if (isCollusion(mesh,star.position,50))
		{
			scene.remove(star);
			star = null;
			counter += Math.floor(5 * maxEnemySpeed);
			testTankLevel();
		}
	}

	function getRand(min, max)
	{
	  return Math.floor(Math.random() * (max - min) + min);
	}

	function createShape()
	{
		var mesh;
		var rectLength = 120, rectWidth = 40;
		var rectShape = new THREE.Shape();
		rectShape.moveTo( 0,0 );
		rectShape.lineTo( 0, rectWidth);
		rectShape.lineTo( rectLength, rectWidth );
		rectShape.lineTo( rectLength, 0 );
		rectShape.lineTo( 0, 0 );

		var rectGeom = new THREE.ShapeGeometry( rectShape );
		var mesh = new THREE.Mesh( rectGeom, new THREE.MeshBasicMaterial( { color: 0xff0000 } ) ) ;	
		return mesh;
	}


var textureList = [];
var textureCount = 0;
var imageCount = 0;
var onLoaded = 0;
var manager = 0;
var loadCount = 0;
	

	function loadTexture(imageLoader,imgName)
	{
		imageLoader.load(imgName , function ( image ) 
		{
			var texture = new THREE.Texture();
			texture.image = image;
			texture.needsUpdate = true;
			textureList[imgName] = texture;
			textureCount++;
			if (textureCount == imageCount)
				onLoaded(manager,textureList);
		} );
	}

	function loadTextures(list, manager, onLoaded)
	{
		var imageLoader = new THREE.ImageLoader(manager);
		imageCount = list.length;
		this.onLoaded = onLoaded;
		this.manager = manager;
		for (var i in list)
		{
			loadTexture(imageLoader,list[i]);
		}
	}
	
	function loadObject(manager, name, textureList, textureName,onLoaded)
	{
		loadCount++;
		if (loadCount > fullLoadCount)
			fullLoadCount = loadCount;
		var objLoader = new THREE.OBJLoader(manager);
		objLoader.load(name, function(object) 
		{
			object.traverse( function ( child ) 
			{
				if ( child instanceof THREE.Mesh ) 
				{
					child.material.map = textureList[textureName];
					child.receiveShadow = true;
					child.castShadow = true;
				}
			});
			loadCount--;
			if (loadCount == 0)
				objectsLoaded();
			onLoaded(object);
		});
	}

	function objectsLoaded()
	{
		var button = document.getElementById("start_button");
		button.style.visibility = "inherit";
	}

	function testTankLevel()
	{
		if (counter > tankLevels[curTankLevel].nextLevel)
			curTankLevel += 1;
		if (tankLevels[curTankLevel].tank != mesh)
		{
			scene.remove(mesh);
			mesh = tankLevels[curTankLevel].tank;
			scene.add(mesh);
		}
	}

	function setObject(object,x,y,z,angle)
	{

			var clone = object.clone();
			clone.position.x = x;
			clone.position.z = z;
			clone.position.y = y;
			clone.rotation.z = angle;
			scene.add(clone);
			return clone;
	}

	function loadObjects(manager, textureList)
	{
		// load model
		loadObject(manager,'star.obj',textureList,'star.png', function (object){
			starTemplate = object;
			});
		loadObject(manager,'tank1.obj',textureList,'tank1.png', function (object){
			tank1 = object;
			});
		loadObject(manager,'tank2.obj',textureList,'tank2.png', function (object){
			tank2 = object;
			});
		loadObject(manager,'tank3.obj',textureList,'tank3.png', function (object){
			tank3 = object;
			});
		loadObject(manager,'tree.obj',textureList,'tree.png', function (object){
			tree = object;
			setObject(tree,270,150,40,0.5);
			setObject(tree,290,-150,40,0.3);
			setObject(tree,340,-100,40,0.3);
			setObject(tree,310,100,40,-0.6);
			setObject(tree,350,190,40,0);
			});
		loadObject(manager,'field.obj',textureList,'grass.png', function (object){
			field = object;
			scene.add(object); });
		loadObject(manager,'train.obj',textureList,'train.png', function (object){
			train = object;
			train.rotation.z = -Math.PI / 2;
			train.position.x = -340;
			train.position.y = -600;
			train.position.z = 50;
			scene.add(object); });

	}

	function showLoading()
	{
		var window;
		window = document.getElementById("loading_window");
		window.style.visibility = "visible";
	}
	
	function showStartWindow()
	{
		setName();
		showLoading();
	}

	function initMap()
	{
		camera = new THREE.PerspectiveCamera( 50, window.innerWidth / window.innerHeight, 1, 1000 );
		camera.position.z = 700;

        geometry = new THREE.CircleGeometry( circleRadius, circleSegments );	

		tankLevels = [	{tank:tank1, nextLevel:100, addBullets:addBullets1, power: 2},
						{tank:tank2, nextLevel:250, addBullets:addBullets2, power: 1},
						{tank:tank3, nextLevel:10000, addBullets:addBullets3, power: 1}]
						
		testTankLevel();
		
		sunLight = new THREE.DirectionalLight( 0xffffaa, 1.3 );
		if (shadows)
			sunLight.castShadow = true;
		sunLight.position.set( lightDist, lightDist, 400 );
		scene.add( sunLight );
		
        renderer = new THREE.WebGLRenderer();
        renderer.setSize( window.innerWidth, window.innerHeight );
        if (shadows)
        {
			renderer.shadowMapEnabled = true;
			renderer.shadowMapSoft = true;

			renderer.shadowCameraNear = 3;
			renderer.shadowCameraFar = camera.far;
			renderer.shadowCameraFov = 50;

			renderer.shadowMapBias = 0.0039;
			renderer.shadowMapDarkness = 1;
			renderer.shadowMapWidth = 1024;
			renderer.shadowMapHeight = 1024;
		}
		// MIRORR planes

		if (mirrors)
        {
			verticalMirror = new THREE.Mirror( renderer, camera, { clipBias: 0.003, textureWidth: 2000, textureHeight: 500, color:0x225599 } );
			verticalMirror2 = new THREE.Mirror( renderer, camera, { clipBias: 0.003, textureWidth: 2000, textureHeight: 500, color:0x225599 } );
	
			var verticalMirrorMesh = new THREE.Mesh( new THREE.PlaneBufferGeometry( 600, 200 ), verticalMirror.material );
			verticalMirrorMesh.add( verticalMirror );
			verticalMirrorMesh.position.y = 280;
			verticalMirrorMesh.position.z = 40;
			verticalMirrorMesh.position.x = 20;
			verticalMirrorMesh.rotation.x = Math.PI / 2;
			scene.add( verticalMirrorMesh );
			
			verticalMirrorMesh = new THREE.Mesh( new THREE.PlaneBufferGeometry( 600, 200 ), verticalMirror2.material );
			verticalMirrorMesh.add( verticalMirror2 );
			verticalMirrorMesh.position.y = -280;
			verticalMirrorMesh.position.x = 20;
			verticalMirrorMesh.position.z = 40;
			verticalMirrorMesh.rotation.x = Math.PI / 2;
			verticalMirrorMesh.rotation.y = Math.PI;
			scene.add( verticalMirrorMesh );
		}
        document.body.appendChild( renderer.domElement );
        window.addEventListener( 'resize', onWindowResize, false );
	}

	function findUser(id,usersInfo)
	{
		var result = null;  
		for (var i in usersInfo)
		{
			if(id == usersInfo[i].uid)
			{
				result = usersInfo[i];
				break;
			}
		}
		return result;
	}

	function usersLoaded(data)
	{
		console.log(data);
		var usersJSON = eval(data).response;
		var recordList = document.getElementById("record_list");
		recordList.innerHTML = "";
		for (var i in records)
		{
			var user = findUser(records[i].user_id,usersJSON);
			if (user == null)
				continue;
			recordList.innerHTML += "<div><img width=25 src='" + user.photo_50 + "' />"+user.first_name + " " + user.last_name + " " + records[i].counter_red+"</div>";
		}
		
	}

	function loadUsers(onLoaded)
	{
		client.sendRequest("getrecords", {},"GET",function(data){
			records = data.data;
			console.log(data);
			var userList = [];
			for (var i in records)
				userList[userList.length] = records[i].user_id;
			console.log(userList);
			VK.api('users.get',{user_ids:userList,fields:"photo_50"},onLoaded);
		},
		function(){console.log("ERROR");});
	}

    function initApp() {

		if (isInitialized == false)
		{
			showStartWindow();	
			scene = new THREE.Scene();
			// loading manager
			var manager = new THREE.LoadingManager();
			manager.onProgress = function ( item, loaded, total ) 
			{
				//document.getElementById("loading_box").innerHTML += "Resource: " + item + " " + loaded + " " + total + "<br />";
				//document.getElementById("progress_line").style.width = ((fullLoadCount - loadCount) * 100 / fullLoadCount).toFixed(2) + "%";
				document.getElementById("progress_line").style.width = (loaded * 100 / total).toFixed(2) + "%";
				
			};
			loadTextures(['star.png','tank1.png','tank2.png','tank3.png','grass.png','train.png','tree.png'], manager, loadObjects);
		}
		else
		{
			for (var j in bullets)
				scene.remove(bullets[j].mesh);	
			for (var i in enemies)
				scene.remove(enemies[i].mesh);	
			bullets = [];
			enemies = [];
			counter = 0;
			counterBlue = 0;
			player = {x:0,y:0,angle:0}
			trainPause = 500;
			bossCount = 0;
			curTankLevel = 0;
			maxEnemyCount = 5;
			maxEnemySpeed = 0;
			train.position.x = -340;
			train.position.y = -600;
			testTankLevel();
			document.getElementById("finish_window").style.visibility = "hidden";
			showLoading();
		}
		loadUsers(usersLoaded);
    }

	function sign(a)
	{
		return a?a<0?-1:1:0;
	}

	function onWindowResize() {
		camera.left = window.innerWidth / - 2;
		camera.right = window.innerWidth / 2;
		camera.top = window.innerHeight / 2; 
		camera.bottom = window.innerHeight / - 2;
		camera.updateProjectionMatrix();
        
        renderer.setSize( window.innerWidth, window.innerHeight );
	}

	function getDist(mesh1,mesh2)
	{
		return Math.sqrt(Math.pow(mesh1.position.x - mesh2.position.x,2) + 
					Math.pow(mesh1.position.y - mesh2.position.y,2));
	}

	function render()
	{
		if (mirrors)
		{
			verticalMirror.render();
			verticalMirror2.render();
		}
		renderer.render( scene, camera );
	}
	
	function recalc()
	{
		if (!play)
			return;
		if (!mesh)
			return;
			
		player.angle -= bodyAngleSpeed * playerAngleSpeed;
		player.x += Math.cos(player.angle + Math.PI / 2) * playerSpeed * speedY;
		player.y += Math.sin(player.angle + Math.PI / 2) * playerSpeed * speedY;
		if (player.x > mapWidth)
			player.x = mapWidth;
		if (player.x < -mapWidth)
			player.x = -mapWidth;
		if (player.y > mapHeight)
			player.y = mapHeight;
		if (player.y < -mapHeight)
			player.y = -mapHeight;
		
		mesh.rotation.z = player.angle;
		mesh.position.x = player.x;
		mesh.position.y = player.y;
		
		mesh.traverse( function ( child ) 
		{
			if (child.name.indexOf("head") > -1) 
				child.rotation.z = -headAngle - player.angle;
		});
	
		
		for (var i in bullets)
		{
			var angle = bullets[i].angle - Math.PI / 2;
			bullets[i].mesh.position.x += Math.cos(angle) * 10;
			bullets[i].mesh.position.y += -Math.sin(angle) * 10;
		}
		
		if (shadows)
		{
			sunAngle += 0.005;
		
			sunLight.position.x = Math.cos(sunAngle) * lightDist;
			sunLight.position.y = Math.sin(sunAngle) * lightDist;
		}
		
		enemyCtrl();
		trainCtrl();
		starsCtrl();
	}
	
    function animate() {
		if (play)
			requestAnimationFrame( animate );
		render();
    }

	function getKey()
	{
		var key = getCookie("key");
		if (!key)
		{
			var d = new Date();
			var n = d.getTime();
			key = MD5(n.toString());
			setCookie("key",key,{expires:3600 * 24 * 30});
		}
		return key;

	}

	function setName()
	{
		client.sendRequest("setname", {name:encodeURIComponent(name), user_id:userId, auth_key:authKey},"POST",onSuccess,onError);
	}

	function gameOver()
	{
		if (play == false)
			return;
		play = false;
		var window = document.getElementById("finish_window");
		window.innerHTML = "Игра окончена. <br />Ваш счет: " + counter + "<br /> <a href='#' onclick='initApp()'>Начать заново</a><br />"
		window.style.visibility = "visible";
		
		client.sendRequest("gamefinish", {counter_red:counter, counter_blue:counterBlue, user_id:userId, auth_key:authKey},"POST",onSuccess,onError);
	}
	
	function onSuccess(data)
	{
		console.log("Data is sended.");
	}
	
	function onError(errCode,errMsg)
	{
		console.log(errMsg);
	}

	function startGame()
	{
		var loadingWindow = document.getElementById("loading_window");
		loadingWindow.style.visibility = "hidden";
		var progressBar = document.getElementById("progress_bar");
		progressBar.style.visibility = "hidden";
		if (isInitialized == false)
		{
			shadows = document.getElementById("shadows_input").checked;
			mirrors = document.getElementById("mirrors_input").checked;
			initMap();
			timerId = setInterval(recalc, 40);
			isInitialized = true;
		}
		play = true;
		animate();
		
	}

	function onKeyDown(e)
	{
		if (e.keyCode == 65)
		{
			bodyAngleSpeed = -1;
		}
		if (e.keyCode == 68)
		{
			bodyAngleSpeed = 1;
		}
		if (e.keyCode == 87)
		{
			speedY = 1;
		}
		if (e.keyCode == 83)
		{
			speedY = -1;
		}
	}

	function onKeyUp(e)
	{
		if (e.keyCode == 65)
		{
			bodyAngleSpeed = 0;
		}
		if (e.keyCode == 68)
		{
			bodyAngleSpeed = 0;
		}
		if (e.keyCode == 87)
		{
			speedY = 0;
		}
		if (e.keyCode == 83)
		{
			speedY = 0;
		}
	}

	function onMouseMove(e)
	{
		var x = e.pageX - getWidth() / 2;
		var y = -e.pageY + getHeight() / 2;
		var d1 = x - mesh.position.x;
		var d2 = y - mesh.position.y;
		headAngle = Math.atan2(d1,d2);
	}

	function addBullet(x,y,angle,size)
	{
		var bulletMesh = new THREE.Mesh( new THREE.CircleGeometry( size,8), new THREE.MeshBasicMaterial( {color: 0xff9900} ) );
		bulletMesh.position.x = x;
		bulletMesh.position.y = y;
		bulletMesh.position.z = mesh.position.z + 4 ;
		bullets[bullets.length] = {mesh:bulletMesh, angle:angle};
		scene.add(bulletMesh);
	}

	function addBullets1(xOffset,yOffset)
	{
		addBullet(mesh.position.x - xOffset / 2 + 0 * xOffset + 30 * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 0 * yOffset - 30 * Math.sin(headAngle - Math.PI / 2),headAngle,5);
	}

	function addBullets2(xOffset,yOffset)
	{
		addBullet(mesh.position.x - xOffset / 2 + 0 * xOffset + 30 * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 0 * yOffset - 30 * Math.sin(headAngle - Math.PI / 2),headAngle,3);
		addBullet(mesh.position.x - xOffset / 2 + 1 * xOffset + 30 * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 1 * yOffset - 30 * Math.sin(headAngle - Math.PI / 2),headAngle,3);
	}

	function addBullets3(xOffset,yOffset)
	{
		addBullet(mesh.position.x - xOffset / 2 + 0 * xOffset + 30 * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 0 * yOffset - 30 * Math.sin(headAngle - Math.PI / 2),headAngle,3);
		addBullet(mesh.position.x - xOffset / 2 + 1 * xOffset + 30 * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 1 * yOffset - 30 * Math.sin(headAngle - Math.PI / 2),headAngle,3);
		addBullet(mesh.position.x - xOffset / 2 + 0 * xOffset + 30 * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 0 * yOffset - 30 * Math.sin(headAngle - Math.PI / 2),headAngle + Math.PI / 9,3);
		addBullet(mesh.position.x - xOffset / 2 + 1 * xOffset + 30 * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 1 * yOffset - 30 * Math.sin(headAngle - Math.PI / 2),headAngle - Math.PI / 9,3);
	}

	function onClick(e)
	{
		var xOffset = 10 * Math.cos(headAngle);
		var yOffset = 10 * Math.sin(headAngle);
		
		tankLevels[curTankLevel].addBullets(xOffset,yOffset);
	}
	
	function onMusicCheck(e)
	{
		var audio = document.getElementById("music");
		if (e.checked)
		{
			audio.currentTime = 0;
			audio.autoplay = true;
		}
		else
		{
			audio.pause();
			audio.currentTime = 0;
			audio.autoplay = false;
		}
	}

</script>
</head>
<body onload="VK.init(initApp)" onkeydown="onKeyDown(event)" onkeyup="onKeyUp(event)" onmousemove="onMouseMove(event)" onclick="onClick(event)">
	<audio loop autoplay id="music">
	  <source src="./music.mp3" type="audio/mpeg">
	</audio>
	<div id="progress_bar"><div id="progress_line"></div></div>
	<div id="records_window" class="window">
		Придумайте название для вашего девайса.<br />
		Имя танка: <input type="text" id="name_input">
		<div class="button" onclick="setName(); showLoading();">Ok</div>
		<div class="button" onclick="showLoading()">В другой раз</div>
	</div>
	<div id="loading_window" class="window">
		Идет загрузка. <br />
		Управление: WASD + мышь. <br />
		Цель: Спасти себя от шаров.<br />
		<input id="shadows_input" type="checkbox" checked>Включить тени<br />
		<input id="mirrors_input" type="checkbox" checked>Включить отражения<br />
		<input id="music_input" type="checkbox" onclick="onMusicCheck(this)" checked>Включить музыку<br />
		Рекорды:
		<div id="record_list">
		</div>
		<div class="button" id="start_button" onclick="startGame()">Начать игру</div>
	</div>
	<div id="finish_window" class="window">
		Игра окончена.
	</div>
	<div id="info">
		<div id="counter1">
		</div>
		<div id="counter2">
		</div>
	</div>
</body>
</html>
