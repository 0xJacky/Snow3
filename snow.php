<?php
/*
Plugin Name: Snow
Plugin URI: https://jackyu.cn/projects/sonw
Description: 3D效果的飘雪插件，Typecho 作者：清馨雅致
Version: 1.0
Author: Jacky
Author URI: https://jackyu.cn/
License: GPL2
*/


function add_snow_style() {
  wp_register_style( 'SnowCSS', plugins_url('css/Snow.css', __FILE__) );
  wp_register_script( 'jqueryJS', '//cdn.bootcss.com/jquery/3.1.1/jquery.min.js' );
  wp_register_script( 'SnowJS', plugins_url('js/three.js', __FILE__) );
  wp_enqueue_script('SnowJS');
  wp_enqueue_style('SnowCSS');
  wp_enqueue_script('jqueryJS');
}
add_action( 'wp_enqueue_scripts', 'add_snow_style' );

function add_snow_script() {
  //wp_register_script( 'SnowJS', plugins_url('js/three.js', __FILE__) );
  //wp_register_script( 'jqueryJS', '//cdn.bootcss.com/jquery/3.1.1/jquery.min.js' );
//  wp_enqueue_script('SnowJS');
  //wp_enqueue_script('jqueryJS');
  $options->snowway = 0;
  $options->snowspeed = 2;
  $options->snowfront = 0;
  $options->snowrotateX = 0;
  $options->snowrotateY = 0;
  $options->snowgravity = 0;
  $options->snownum = 500;
  $imgUrl = plugins_url('img/Snow.png', __FILE__);
  echo '<script type="text/javascript">
  function randomRange(t,i){return Math.random()*(i-t)+t}Particle3D=function(t){THREE.Particle.call(this,t),this.velocity=new THREE.Vector3('.$options->snowway.',-'.$options->snowspeed.','.$options->snowfront.'),this.velocity.rotateX(randomRange(-'.$options->snowrotateX.','.$options->snowrotateX.')),this.velocity.rotateY(randomRange(0,'.$options->snowrotateY.')),this.gravity=new THREE.Vector3(0,-'.$options->snowgravity.',0),this.drag=1},Particle3D.prototype=new THREE.Particle,Particle3D.prototype.constructor=Particle3D,Particle3D.prototype.updatePhysics=function(){this.velocity.multiplyScalar(this.drag),this.velocity.addSelf(this.gravity),this.position.addSelf(this.velocity)};var TO_RADIANS=Math.PI/180;THREE.Vector3.prototype.rotateY=function(t){cosRY=Math.cos(t*TO_RADIANS),sinRY=Math.sin(t*TO_RADIANS);var i=this.z,o=this.x;this.x=o*cosRY+i*sinRY,this.z=o*-sinRY+i*cosRY},THREE.Vector3.prototype.rotateX=function(t){cosRY=Math.cos(t*TO_RADIANS),sinRY=Math.sin(t*TO_RADIANS);var i=this.z,o=this.y;this.y=o*cosRY+i*sinRY,this.z=o*-sinRY+i*cosRY},THREE.Vector3.prototype.rotateZ=function(t){cosRY=Math.cos(t*TO_RADIANS),sinRY=Math.sin(t*TO_RADIANS);var i=this.x,o=this.y;this.y=o*cosRY+i*sinRY,this.x=o*-sinRY+i*cosRY};$(function(){var container=document.querySelector(".Snow");if(/MSIE 6|MSIE 7|MSIE 8/.test(navigator.userAgent)){return}else{if(/MSIE 9|MSIE 10/.test(navigator.userAgent)){$(container).css("height",$(window).height()).bind("click",function(){$(this).fadeOut(1000,function(){$(this).remove()})})}}var containerWidth=$(container).width();var containerHeight=$(container).height();var particle;var camera;var scene;var renderer;var mouseX=0;var mouseY=0;var windowHalfX=window.innerWidth/2;var windowHalfY=window.innerHeight/2;var particles=[];var particleImage=new Image();particleImage.src="'.$imgUrl.'";var snowNum='.$options->snownum.';function init(){camera=new THREE.PerspectiveCamera(75,containerWidth/containerHeight,1,10000);camera.position.z=1000;scene=new THREE.Scene();scene.add(camera);renderer=new THREE.CanvasRenderer();renderer.setSize(containerWidth,containerHeight);var material=new THREE.ParticleBasicMaterial({map:new THREE.Texture(particleImage)});for(var i=0;i<snowNum;i++){particle=new Particle3D(material);particle.position.x=Math.random()*2000-1000;particle.position.y=Math.random()*2000-1000;particle.position.z=Math.random()*2000-1000;particle.scale.x=particle.scale.y=1;scene.add(particle);particles.push(particle)}container.appendChild(renderer.domElement);document.addEventListener("mousemove",onDocumentMouseMove,false);setInterval(loop,1000/40)}function onDocumentMouseMove(event){mouseX=event.clientX-windowHalfX;mouseY=event.clientY-windowHalfY}function loop(){for(var i=0;i<particles.length;i++){var particle=particles[i];particle.updatePhysics();with(particle.position){if(y<-1000){y+=2000}if(x>1000){x-=2000}else{if(x<-1000){x+=2000}}if(z>1000){z-=2000}else{if(z<-1000){z+=2000}}}}camera.position.x+=(mouseX-camera.position.x)*0.005;camera.position.y+=(-mouseY-camera.position.y)*0.005;camera.lookAt(scene.position);renderer.render(scene,camera)}init()});</script>' . "\n";
  echo '<div class="Snow"></div>' . "\n";
}
add_action( 'wp_footer', 'add_snow_script' );


/*if ( is_admin() ) {
	add_action('admin_menu', 'snow_menu');
}
function snow_menu() {
  add_options_page('Snow 设置页面', 'Snow', 'administrator','snow', 'display_snow_settings');
}

function display_snow_settings() {
// Comeing soon~
}*/

?>
