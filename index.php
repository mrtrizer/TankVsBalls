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
<link type="text/css" href="style.css" rel="stylesheet">
<script src="//vk.com/js/api/xd_connection.js?2" type="text/javascript"></script>

<!-- libs -->
<script src="js/three.js" type="text/javascript"></script>
<script src="js/Mirror.js" type="text/javascript"></script>
<script src="js/loaders/ObjLoader.js" type="text/javascript"></script>
<script src="client.js" type="text/javascript"></script>
<script src="tools.js" type="text/javascript"></script>

<script>
	var userId = <?php if (array_key_exists("viewer_id",$_GET)) echo $_GET["viewer_id"]; else echo "-1";?>;
	var apiId = <?php if (array_key_exists("api_id",$_GET)) echo $_GET["api_id"]; else echo "-1";?>;
	var authKey = "<?php if (array_key_exists("auth_key",$_GET)) echo $_GET["auth_key"]; else echo "-1";?>";
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
    var waves = [
    {redCount:3, blackCount:5, pause:10000},
    {redCount:10, blackCount:10, pause:20000},
    {redCount:15, blackCount:20, pause:35000},
    {redCount:20, blackCount:30, pause:40000},
    {redCount:30, blackCount:40, pause:50000},
    {redCount:40, blackCount:50, pause:55000},
    {redCount:45, blackCount:55, pause:10000},
    {redCount:0, blackCount:0, pause:0}]
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
    var mapWidth = 420;
    var bossCount = 0;
    var verticalMirror;
    var verticalMirror2;
    var verticalMirror3;
    var tree;
    var client = new Client("backend.php");
    var timerId;
    var shadows = false;
    var mirrors = false;
    var tankLevels = [];
    var curAmmo = 0;
    var maxEnemyCount = 5;
    var maxEnemySpeed = 0;
    var isInitialized = false;
    var records = [];
    var fullLoadCount = 0;
    var star = null;
    var starTemplate = null;
    var helpShow = false;
    var anonymous = false;
    var boxes = [];
    var waveTimer = null;
    var curWave = 0;
    var starCount = 3;
    var curAmmo = 0;
    var ammoTypes = [];
    var stars = [];
    var turels = [];
    var starPoses = [{x:-100,y:50},{x:150,y:50},{x:350,y:50}];

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

	function createEnemy(x,y,z,health,color,type,size,target)
	{
		var enemyMesh = new THREE.Mesh( new THREE.SphereGeometry(size, 6, 6 ), 
											new THREE.MeshPhongMaterial( { color:color }));
		enemyMesh.position.x = x;
		enemyMesh.position.y = y;
		enemyMesh.position.z = z;
		var n = 0;
		for (var i = 0; i < 100; i++)
		{
			n = getRand(0,2);
			if (stars[n] == null)
				continue;
			break;
		}
		enemies[enemies.length] = {starN:n, mesh:enemyMesh, health:health, type:type, speed: maxEnemySpeed, target:target};
		enemyMesh.castShadow = true;
		enemyMesh.receiveShadow = true;
		scene.add(enemyMesh);
	}

	function genEnemy(pos)
	{
		var posList = [{x1: -300,y1: -300, x2: 300, y2:-700}];
		var x = getRand(posList[pos].x1, posList[pos].x2);
		var y = getRand(posList[pos].y1, posList[pos].y2);
		var target = "player";
		var color = 0xff2200;
		var size = 15;
		if (getRand(0,6) > 1)
		{
			target = "star";
			var size = 10;
			color = 0x000000;
		}
		createEnemy(x,y,20,4, color, "red",size,target);
	}

	function enemyCtrl()
	{
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
			var target = null;
			if (enemies[i].target == "player")
				target = mesh;
			if (enemies[i].target == "star")
			{
				var star = stars[enemies[i].starN]
				if (star == null)
					for (var j in stars)
						if (stars[j] != null)
						{
							enemies[i].starN = j;
							star = stars[j];
							break;
						}
				target = star;
				if (isCollusion(mesh,enemies[i].mesh.position,20) && (speedY != 0))
				{
					scene.remove(enemies[i].mesh);
					counter += 1;
					enemies.splice(i,1);
					break;
				}
			}
			if (target == null)
				break;
			if (isCollusion(target,enemies[i].mesh.position,10))
			{
				if (enemies[i].target == "star")
				{
					scene.remove(target);
					stars[enemies[i].starN] = null;
					starCount--;
					if (starCount <= 0)
						gameOver();
				}
				else
					gameOver();
				break;
			}
			var d1 = -enemies[i].mesh.position.x + target.position.x;
			var d2 = enemies[i].mesh.position.y - target.position.y;
			var angle = Math.atan2(d2,d1);
			maxEnemySpeed =  1.5;
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
					enemies[i].health -= ammoTypes[curAmmo].power;
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
					}
				}
			}
		}
		document.getElementById("counter1").innerHTML = counter;
	}

	function boxCtrl()
	{
		for (var i in boxes)
			if (isCollusion(mesh,boxes[i].position,30))
			{
				tankLevels[boxes[i].ammoType].count += boxes[i].ammoCount;
				scene.remove(boxes[i]);
				boxes.splice(i,1);
			}
	}

	function starsCtrl()
	{
		for (var i in stars)
		{
			if (stars[i] == null)
				continue;
			stars[i].rotation.z += 0.1;
		}
	}

	function turelCtrl()
	{
		for (var i in turels)
		{
			turels[i].counter += 1;
			if (turels[i].counter < 20)
				continue;
			else
				turels[i].counter = 0;
			for (var j in enemies)
			{
				if (isCollusion(turels[i],enemies[j].mesh.position,200))
				{
					var dX = enemies[j].mesh.position.x - turels[i].position.x; 
					var dY = enemies[j].mesh.position.y - turels[i].position.y;
					var angle = Math.atan2(dX,dY);
					var xOffset = 10 * Math.cos(angle);
					var yOffset = 10 * Math.sin(angle);
					addBullets2(turels[i], angle, xOffset,	yOffset, 30);
					break;
				}
			}
		}
	}

	function getRand(min, max)
	{
	  return Math.floor(Math.random() * (max - min) + min);
	}

	function createBox(x,y)
	{
		var geometry = new THREE.BoxGeometry( 30, 30, 30 );
		var material = new THREE.MeshBasicMaterial( {color: 0x61380B} );
		var cube = new THREE.Mesh( geometry, material );
		cube.ammoType = getRand(0,5);
		cube.ammoCount = ammoTypes[cube.ammoType].countInBox;
		boxes[boxes.length] = cube;
		cube.position.x = x;
		cube.position.y = y;
		scene.add( cube );
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
		if (getRand(1,1000) < 3)
		{
			createBox(getRand(-200,200),getRand(-200,200));
		}
		for (var i = 0; i < tankLevels.length; i++)
		{
			if (curAmmo == i)
				document.getElementById("ammo" + (i + 1)).className = "ammo_slot ammo_slot_selected";
			else 
				document.getElementById("ammo" + (i + 1)).className = "ammo_slot";
			document.getElementById("ammo" + (i + 1)).innerHTML = tankLevels[i].count;
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
			setObject(tree,570,150,40,0.5);
			setObject(tree,590,-150,40,0.3);
			setObject(tree,640,-100,40,0.3);
			setObject(tree,610,100,40,-0.6);
			setObject(tree,650,190,40,0);
			});
		loadObject(manager,'field.obj',textureList,'grass.png', function (object){
			field = object;
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

		ammoTypes = [
			{countInBox:100, power: 2, addBullets:addBullets1, expense:1}, 
			{countInBox:200, power: 1, addBullets:addBullets2, expense:2}, 
			{countInBox:100, power: 1, addBullets:addBullets3, expense:4}, 
			{countInBox:100, power: 2, addBullets:addBullets4, expense:1},
			{countInBox:1, power: 1, addBullets:addBullets5, expense:1}];

        geometry = new THREE.CircleGeometry( circleRadius, circleSegments );	
						
		testTankLevel();
		
		mesh = tank1;
		scene.add(mesh);
		
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
			var n = parseInt(i) + 1;
			recordList.innerHTML += "<div class='record_item'><div class='record_n'>" + n + "</div><img class='user_img' width=25 src='"+user.photo_50+"' /><div class='user_name'>"+user.first_name+" "+user.last_name+" </div><div class='score_value'>"+records[i].counter_red+"</div></div>";
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
			if (VK)
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
				document.getElementById("progress_line").style.width = (loaded * 100 / total).toFixed(2) + "%";
				
			};
			loadTextures(['star.png','tank1.png','tank2.png','tank3.png','grass.png','tree.png'], manager, loadObjects);
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
			bossCount = 0;
			maxEnemyCount = 4;
			maxEnemySpeed = 0;
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
		camera.position.x = player.x;
		camera.position.y = player.y;
		
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
		starsCtrl();
		boxCtrl();
		testTankLevel();
		turelCtrl();
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
		var text = "Игра окончена. <br />Ваш счет: " + counter;
		text += "<br /> <div class='button' onclick='initApp()'>В меню</div><br />";
		//text += "<br>Если вам есть что сказать, напишите комментарий. Ваше мнение очень важно для нас!<br />";
		//text += "<textarea cols=50 rows=5></textarea><br />";
		//text += "<div class='button' onclick='initApp()'>Отправить</div>"
		window.innerHTML = text;
		window.style.visibility = "visible";
		document.getElementById("anonymous").checked = false;
		client.sendRequest("gamefinish", {counter_red:counter, counter_blue:counterBlue, user_id:userId, auth_key:authKey, anonymous:anonymous?1:0},"POST",onSuccess,onError);
	}
	
	function onSuccess(data)
	{
		console.log("Data is sended.");
	}
	
	function onError(errCode,errMsg)
	{
		console.log(errMsg);
	}

	function createWave(n)
	{
		for (var i = 0; i < waves[n].redCount + waves[n].blackCount; i++)
			genEnemy(0);
		if (waves[n].pause == 0)
			gameOver();
		waveTimer = setTimeout(function(){createWave(n+1);},waves[n].pause)
	}

	function startGame()
	{
		tankLevels = [	{count: 100},
						{count: 100},
						{count: 0},
						{count:0},
						{count:1}]
		var loadingWindow = document.getElementById("loading_window");
		loadingWindow.style.visibility = "hidden";
		var progressBar = document.getElementById("progress");
		progressBar.style.visibility = "hidden";
		var progressBar = document.getElementById("info");
		progressBar.style.visibility = "visible";
		anonymous = document.getElementById("anonymous").checked;
		if (helpShow == false)
		{
			var controls = document.getElementById("controls");
			controls.style["top"] = "50px";
			setTimeout(function(){controls.style["top"] = "-150px";},4000);
			helpShow = true;
		}
		if (isInitialized == false)
		{
			shadows = document.getElementById("shadows_input").checked;
			mirrors = document.getElementById("mirrors_input").checked;
			initMap();
			timerId = setInterval(recalc, 40);
			isInitialized = true;
		}
		play = true;
		for (var i = 0; i < 3; i++)
			stars[i] = setObject(starTemplate,starPoses[i].x,starPoses[i].y, 50,0);
		createWave(curWave);
		animate();
		
	}

	function onKeyDown(e)
	{
		if (e.keyCode == 65)
		{
			bodyAngleSpeed = -2;
		}
		if (e.keyCode == 68)
		{
			bodyAngleSpeed = 2;
		}
		if (e.keyCode == 87)
		{
			speedY = 1;
		}
		if (e.keyCode == 83)
		{
			speedY = -1;
		}
		if (e.keyCode == 49)
		{
			curAmmo = 0;
		}
		if (e.keyCode == 50)
		{
			curAmmo = 1;
		}
		if (e.keyCode == 51)
		{
			curAmmo = 2;
		}
		if (e.keyCode == 52)
		{
			curAmmo = 3;
		}
		if (e.keyCode == 53)
		{
			curAmmo = 4;
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
		var d1 = x;
		var d2 = y;
		headAngle = Math.atan2(d1,d2);
	}

	function selectAmmo(n)
	{
		curAmmo = n;
	}

	function addBullet(x,y,angle,size,color)
	{
		var bulletMesh = new THREE.Mesh( new THREE.SphereGeometry( size,8), new THREE.MeshBasicMaterial( {color: color} ) );
		bulletMesh.position.x = x;
		bulletMesh.position.y = y;
		bulletMesh.position.z = mesh.position.z + 4 ;
		bullets[bullets.length] = {mesh:bulletMesh, angle:angle};
		scene.add(bulletMesh);
	}

	function addBullets1(mesh, headAngle, xOffset,yOffset,dist)
	{
		addBullet(mesh.position.x + dist * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y - dist * Math.sin(headAngle - Math.PI / 2),headAngle,5,0x0000FF);
	}

	function addBullets2(mesh, headAngle, xOffset,yOffset,dist)
	{
		addBullet(mesh.position.x - xOffset / 2 + 0 * xOffset + dist * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 0 * yOffset - dist * Math.sin(headAngle - Math.PI / 2),headAngle,3,0x00BFFF);
		addBullet(mesh.position.x - xOffset / 2 + 1 * xOffset + dist * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 1 * yOffset - dist * Math.sin(headAngle - Math.PI / 2),headAngle,3,0x00BFFF);
	}

	function addBullets3(mesh, headAngle, xOffset,yOffset,dist)
	{
		addBullet(mesh.position.x - xOffset / 2 + 0 * xOffset + dist * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 0 * yOffset - dist * Math.sin(headAngle - Math.PI / 2),headAngle,3,0x00BFFF);
		addBullet(mesh.position.x - xOffset / 2 + 1 * xOffset + dist * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 1 * yOffset - dist * Math.sin(headAngle - Math.PI / 2),headAngle,3,0x00BFFF);
		addBullet(mesh.position.x - xOffset / 2 + 0 * xOffset + dist * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 0 * yOffset - dist * Math.sin(headAngle - Math.PI / 2),headAngle + Math.PI / 9,3,0xff9900);
		addBullet(mesh.position.x - xOffset / 2 + 1 * xOffset + dist * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 1 * yOffset - dist * Math.sin(headAngle - Math.PI / 2),headAngle - Math.PI / 9,3,0xff9900);
	}

	function addBullets4(mesh, headAngle, xOffset,yOffset,dist)
	{
		addBullet(mesh.position.x - xOffset / 2 + 0 * xOffset + dist * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 0 * yOffset - dist * Math.sin(headAngle - Math.PI / 2),headAngle,5,0x00BFFF);
		addBullet(mesh.position.x - xOffset / 2 + 1 * xOffset + dist * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 1 * yOffset - dist * Math.sin(headAngle - Math.PI / 2),headAngle,5,0x00BFFF);
		addBullet(mesh.position.x - xOffset / 2 + 0 * xOffset + dist * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 0 * yOffset - dist * Math.sin(headAngle - Math.PI / 2),headAngle + Math.PI / 9,5,0x00BFFF);
		addBullet(mesh.position.x - xOffset / 2 + 1 * xOffset + dist * Math.cos(headAngle - Math.PI / 2),
					mesh.position.y + yOffset / 2 - 1 * yOffset - dist * Math.sin(headAngle - Math.PI / 2),headAngle - Math.PI / 9,5,0x00BFFF);
	}

	function addBullets5(mesh, headAngle, xOffset,yOffset,dist)
	{
		var turel = new THREE.Mesh( new THREE.SphereGeometry(30, 6, 6 ), 
											new THREE.MeshPhongMaterial( { color:0x666666 }));
		turel.position.x = mesh.position.x;
		turel.position.y = mesh.position.y;
		turel.position.z = 0;
		turel.castShadow = true;
		turel.receiveShadow = true;
		turel.counter = 0;
		scene.add(turel);
		turels[turels.length] = turel;
		
	}

	function onClick(e)
	{
		var xOffset = 10 * Math.cos(headAngle);
		var yOffset = 10 * Math.sin(headAngle);
		
		if (tankLevels[curAmmo].count <= 0)
			return;
		else
		{
			tankLevels[curAmmo].count -= ammoTypes[curAmmo].expense;
			ammoTypes[curAmmo].addBullets(mesh, headAngle, xOffset,yOffset,55);
		}
	}
	
	function onMusicCheck(e)
	{
		var audio = document.getElementById("music");
		if (e.checked)
		{
			audio.currentTime = 0;
			audio.play();
		}
		else
		{
			audio.pause();
			audio.currentTime = 0;
		}
	}

	function bodyLoaded()
	{
		if (typeof VK !== 'undefined')
			VK.init(initApp);
		else
			initApp();
	}

</script>
</head>
<body onload="bodyLoaded()" onkeydown="onKeyDown(event)" onkeyup="onKeyUp(event)" onmousemove="onMouseMove(event)" onclick="onClick(event)">
	<audio loop id="music">
	  <source src="./music.mp3" type="audio/mpeg">
	</audio>
	<div id="loading_window" class="start_menu">
		<div class="content">
			<div id="records">
				Рекорды:
				<div id="record_list">
				</div>
			</div>
			<div id="config">
				<input id="shadows_input" type="checkbox" checked>Включить тени<br />
				<input id="mirrors_input" type="checkbox" checked>Включить отражения<br />
				<input id="music_input" type="checkbox" onclick="onMusicCheck(this)">Включить музыку<br />
			</div>
			<div id="game_start">
				<input id="anonymous" type="checkbox">Тренировка<br />(Результат не сохраняется)<br />
				<div class="button" id="start_button" onclick="startGame()">Начать игру</div>
			</div>

		</div>
		<div id="progress">
			Загрузка:
			<div id="progress_bar"><div id="progress_line"></div></div>
		</div>
	</div>

	<div id="finish_window" class="window">
		Игра окончена.
	</div>
	<div id="info">
		<div id="counter1">
		</div>
	</div>
	<div id="ammo_panel">
		<div id="ammo1" class="ammo_slot ammo_slot_selected" onclick="selectAmmo(0);"></div>
		<div id="ammo2" class="ammo_slot" onclick="selectAmmo(1);"></div>
		<div id="ammo3" class="ammo_slot" onclick="selectAmmo(2);"></div>
		<div id="ammo4" class="ammo_slot" onclick="selectAmmo(3);"></div>
		<div id="ammo5" class="ammo_slot" onclick="selectAmmo(4);"></div>
	</div>
	<div id="controls">
		
	</div>
	<div id="logo"><img src="images/logo.png" width=25>v0.2 beta</div>
</body>
</html>
