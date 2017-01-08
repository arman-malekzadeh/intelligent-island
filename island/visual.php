<!DOCTYPE html>
<html lang="en">
	<head>
		<title>three.js webgl - shadow map</title>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
		<style>
			body {
				font-family: Monospace;
				background-color: #000;
				color: #fff;
				margin: 0px;
				overflow: hidden;
			}
			#info {
				position: absolute;
				top: 10px;
				width: 100%;
				text-align: center;
				z-index: 100;
				display:block;
			}
			#info a { color: #f00; font-weight: bold; text-decoration: underline; cursor: pointer }
		</style>
	</head>

	<body>

		

		<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r79/three.js"></script>

		<script src="js/FirstPersonControls.js"></script>

		<script src="js/UnpackDepthRGBAShader.js"></script>
		<script src="js/ShadowMapViewer.js"></script>

		<script src="js/Detector.js"></script>
		<script src="js/stats.min.js"></script>

		<script>
			if ( ! Detector.webgl ) Detector.addGetWebGLMessage();
			var SHADOW_MAP_WIDTH = 2048, SHADOW_MAP_HEIGHT = 1024;
			var HUD_MARGIN = 0.05;
			var SCREEN_WIDTH = window.innerWidth;
			var SCREEN_HEIGHT = window.innerHeight;
			var FLOOR = - 250;
			var camera, controls, scene, renderer;
			var container, stats;
			var NEAR = 10, FAR = 3000;
			var mixer, morphs = [];
			var light;
			var lightShadowMapViewer;
			var clock = new THREE.Clock();
			var showHUD = false;
			init();
			animate();
			function init() {
				container = document.createElement( 'div' );
				document.body.appendChild( container );
				// SCENE CAMERA
				camera = new THREE.PerspectiveCamera( 23, SCREEN_WIDTH / SCREEN_HEIGHT, NEAR, FAR );
				camera.position.set( 700, 50, 1900 );
				controls = new THREE.FirstPersonControls( camera );
				controls.lookSpeed = 0.0125;
				controls.movementSpeed = 500;
				controls.noFly = false;
				controls.lookVertical = true;
				controls.constrainVertical = true;
				controls.verticalMin = 1.5;
				controls.verticalMax = 2.0;
				controls.lon = 250;
				controls.lat = 30;
				// SCENE
				scene = new THREE.Scene();
				scene.fog = new THREE.Fog( 0x59472b, 1000, FAR );
				// LIGHTS
				var ambient = new THREE.AmbientLight( 0x444444 );
				scene.add( ambient );
				light = new THREE.SpotLight( 0xffffff, 1, 0, Math.PI / 2 );
				light.position.set( 0, 1500, 1000 );
				light.target.position.set( 0, 0, 0 );
				light.castShadow = true;
				light.shadow = new THREE.LightShadow( new THREE.PerspectiveCamera( 50, 1, 1200, 2500 ) );
				light.shadow.bias = 0.0001;
				light.shadow.mapSize.width = SHADOW_MAP_WIDTH;
				light.shadow.mapSize.height = SHADOW_MAP_HEIGHT;
				scene.add( light );
				createHUD();
				createScene();
				// RENDERER
				renderer = new THREE.WebGLRenderer( { antialias: true } );
				renderer.setClearColor( scene.fog.color );
				renderer.setPixelRatio( window.devicePixelRatio );
				renderer.setSize( SCREEN_WIDTH, SCREEN_HEIGHT );
				container.appendChild( renderer.domElement );
				renderer.autoClear = false;
				//
				renderer.shadowMap.enabled = true;
				renderer.shadowMap.type = THREE.PCFShadowMap;
				// STATS
				stats = new Stats();
				//container.appendChild( stats.dom );
				//
				window.addEventListener( 'resize', onWindowResize, false );
				window.addEventListener( 'keydown', onKeyDown, false );
			}
			function onWindowResize() {
				SCREEN_WIDTH = window.innerWidth;
				SCREEN_HEIGHT = window.innerHeight;
				camera.aspect = SCREEN_WIDTH / SCREEN_HEIGHT;
				camera.updateProjectionMatrix();
				renderer.setSize( SCREEN_WIDTH, SCREEN_HEIGHT );
				controls.handleResize();
			}
			function onKeyDown ( event ) {
				switch ( event.keyCode ) {
					case 84:	/*t*/
						showHUD = ! showHUD;
						break;
				}
			}
			function createHUD() {
				lightShadowMapViewer = new THREE.ShadowMapViewer( light );
				lightShadowMapViewer.position.x = 10;
				lightShadowMapViewer.position.y = SCREEN_HEIGHT - ( SHADOW_MAP_HEIGHT / 4 ) - 10;
				lightShadowMapViewer.size.width = SHADOW_MAP_WIDTH / 4;
				lightShadowMapViewer.size.height = SHADOW_MAP_HEIGHT / 4;
				lightShadowMapViewer.update();
			}
			function createScene( ) {
				// GROUND
				var geometry = new THREE.PlaneBufferGeometry( 100, 100 );
				var planeMaterial = new THREE.MeshPhongMaterial( { color: 0xffdd99 } );
				var ground = new THREE.Mesh( geometry, planeMaterial );
				ground.position.set( 0, FLOOR, 0 );
				ground.rotation.x = - Math.PI / 2;
				ground.scale.set( 100, 100, 100 );
				ground.castShadow = false;
				ground.receiveShadow = true;
				scene.add( ground );
				// TEXT
				var loader = new THREE.FontLoader();
				loader.load( 'fonts/helvetiker_bold.typeface.json', function ( font ) {
					var textGeo = new THREE.TextGeometry( "JAZIRE", {
						font: font,
						size: 200,
						height: 50,
						curveSegments: 12,
						bevelThickness: 2,
						bevelSize: 5,
						bevelEnabled: true
					} );
					textGeo.computeBoundingBox();
					var centerOffset = - 0.5 * ( textGeo.boundingBox.max.x - textGeo.boundingBox.min.x );
					var textMaterial = new THREE.MeshPhongMaterial( { color: 0xff0000, specular: 0xffffff } );
					var mesh = new THREE.Mesh( textGeo, textMaterial );
					mesh.position.x = centerOffset;
					mesh.position.y = FLOOR + 67;
					mesh.castShadow = true;
					mesh.receiveShadow = true;
					scene.add( mesh );
				} );
				// CUBES
				var mesh = new THREE.Mesh( new THREE.BoxGeometry( 1500, 220, 150 ), planeMaterial );
				mesh.position.y = FLOOR - 50;
				mesh.position.z = 20;
				mesh.castShadow = true;
				mesh.receiveShadow = true;
				scene.add( mesh );
				var mesh = new THREE.Mesh( new THREE.BoxGeometry( 1600, 170, 250 ), planeMaterial );
				mesh.position.y = FLOOR - 50;
				mesh.position.z = 20;
				mesh.castShadow = true;
				mesh.receiveShadow = true;
				scene.add( mesh );
				// MORPHS
				mixer = new THREE.AnimationMixer( scene );
				function addMorph( geometry, speed, duration, x, y, z, fudgeColor ) {
					var material = new THREE.MeshLambertMaterial( { color: 0xffaa55, morphTargets: true, vertexColors: THREE.FaceColors } );
					if ( fudgeColor ) {
						material.color.offsetHSL( 0, Math.random() * 0.5 - 0.25, Math.random() * 0.5 - 0.25 );
					}
					var mesh = new THREE.Mesh( geometry, material );
					mesh.speed = speed;
					var clip = geometry.animations[ 0 ];
					mixer.clipAction( clip, mesh ).
							setDuration( duration ).
							// to shift the playback out of phase:
							startAt( - duration * Math.random() ).
							play();
					mesh.position.set( x, y, z );
					mesh.rotation.y = Math.PI / 2;
					mesh.castShadow = true;
					mesh.receiveShadow = true;
					scene.add( mesh );
					morphs.push( mesh );
				}
				var loader = new THREE.JSONLoader();
				loader.load( "models/horse.js", function( geometry ) {
					addMorph( geometry, 550, 1, 100 - Math.random() * 1000, FLOOR, 300, true );
					addMorph( geometry, 550, 1, 100 - Math.random() * 1000, FLOOR, 450, true );
					addMorph( geometry, 550, 1, 100 - Math.random() * 1000, FLOOR, 600, true );
					addMorph( geometry, 550, 1, 100 - Math.random() * 1000, FLOOR, - 300, true );
					addMorph( geometry, 550, 1, 100 - Math.random() * 1000, FLOOR, - 450, true );
					addMorph( geometry, 550, 1, 100 - Math.random() * 1000, FLOOR, - 600, true );
				} );
				loader.load( "models/flamingo.js", function( geometry ) {
					addMorph( geometry, 500, 1, 500 - Math.random() * 500, FLOOR + 350, 40 );
				} );
				loader.load( "models/stork.js", function( geometry ) {
					addMorph( geometry, 350, 1, 500 - Math.random() * 500, FLOOR + 350, 340 );
				} );
				loader.load( "models/parrot.js", function( geometry ) {
					addMorph( geometry, 450, 0.5, 500 - Math.random() * 500, FLOOR + 300, 700 );
				} );
			}
			function animate() {
				requestAnimationFrame( animate );
				render();
				stats.update();
			}
			function render() {
				var delta = clock.getDelta();
				mixer.update( delta );
				for ( var i = 0; i < morphs.length; i ++ ) {
					morph = morphs[ i ];
					morph.position.x += morph.speed * delta;
					if ( morph.position.x  > 2000 )  {
						morph.position.x = - 1000 - Math.random() * 500;
					}
				}
				controls.update( delta );
				renderer.clear();
				renderer.render( scene, camera );
				// Render debug HUD with shadow map
				if ( showHUD ) {
					lightShadowMapViewer.render( renderer );
				}
			}
		</script>

	</body>
</html>
