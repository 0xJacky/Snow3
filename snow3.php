<?php
/*
Plugin Name: Snow3
Plugin URI: https://jackyu.cn/projects/snow3
Description: 3D效果的飘雪插件，移植 Typecho 版 Snow（作者：清馨雅致）
Version: 1.1
Author: Jacky
Author URI: https://jackyu.cn/
License: GPL2
*/

/*  Copyright 2017  0xJacky  (email : me@jackyu.cm)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('admin_menu', 'snow3_settings');

function snow3_settings() {
  //create new top-level menu
  add_menu_page('Snow3', 'Snow3', 'administrator', __FILE__, 'snow3_settings_page');
  //call register settings function
  add_action( 'admin_init', 'register_snow3_settings' );
}

function register_snow3_settings() {
  register_setting('snow3_options', 'snow3_jquery');
  register_setting('snow3_options', 'snow3_load');
  register_setting('snow3_options', 'snow3_style');
  register_setting('snow3_options', 'snow3_way');
  register_setting('snow3_options', 'snow3_speed');
  register_setting('snow3_options', 'snow3_front');
  register_setting('snow3_options', 'snow3_rotateX');
  register_setting('snow3_options', 'snow3_rotateY');
  register_setting('snow3_options', 'snow3_gravity');
  register_setting('snow3_options', 'snow3_num');
}

function snow3_settings_page() {
  $jquery = is_numeric(get_option('snow3_jquery')) ? esc_attr(get_option('snow3_jquery')) : 0;
  $load = is_numeric(get_option('snow3_load')) ? esc_attr(get_option('snow3_load')) : 0;
  $style = is_numeric(get_option('snow3_style')) ? esc_attr(get_option('snow3_style')) : 1;
  $way = is_numeric(get_option('snow3_way')) ? esc_attr(get_option('snow3_way')) : 0;
  $speed = is_numeric(get_option('snow3_speed')) ? esc_attr(get_option('snow3_speed')) : 1;
  $front = is_numeric(get_option('snow3_front')) ? esc_attr(get_option('snow3_front')) : 0;
  $rotateX = is_numeric(get_option('snow3_rotateX')) ? esc_attr(get_option('snow3_rotateX')) : 45;
  $rotateY = is_numeric(get_option('snow3_rotateY')) ? esc_attr(get_option('snow3_rotateY')) : 360;
  $gravity = is_numeric(get_option('snow3_gravity')) ? esc_attr(get_option('snow3_gravity')) : 0;
  $num = is_numeric(get_option('snow3_num')) ? esc_attr(get_option('snow3_num')) : 500;
?>
<div class="wrap" style="margin: 10px">
  <h1>Snow3 飘雪设置</h1>
  <form method="post" action="options.php">
    <?php settings_fields( 'snow3_options' ); ?>
    <?php do_settings_sections( 'snow3_options' ); ?>
    <hr>
    <fieldset>
      <legend>jQuery 加载方式</legend>
      <input type="radio" name="snow3_jquery" value="0" <?php echo checked( 0, $jquery, false); ?>/>手动加载
      <input type="radio" name="snow3_jquery" value="1" <?php echo checked( 1, $jquery, false); ?>/>自动加载
      <p>"手动加载"需要你手动加载jQuery到主题(如果主题已经加载了jQuery请选择手动加载),若选择"自动加载",插件会自动加载jQuery到主题里。</p>
    </fieldset>
    <fieldset>
      <legend>插件加载方式</legend>
      <input type="radio" name="snow3_load" value="0" <?php echo checked( 0, $load, false); ?>/>自动加载
      <input type="radio" name="snow3_load" value="1" <?php echo checked( 1, $load, false); ?>/>手动加载
      <p>选择手动加载,
      请添加函数到需要加载的页面文件，例如 single.php，将函数放置在<span style="padding: 2px 4px;color: #00A7EB;background-color: #fbfbfb;border: 1px solid #e1e1e8;white-space: nowrap;margin: 0 5px;letter-spacing: 0.015em">add_snow_style();</span>
      置于<span style="padding: 2px 4px;color: #00A7EB;background-color: #fbfbfb;border: 1px solid #e1e1e8;white-space: nowrap;margin: 0 5px;letter-spacing: 0.015em">get_header();</span>
      的上方。并将<span style="padding: 2px 4px;color: #00A7EB;background-color: #fbfbfb;border: 1px solid #e1e1e8;white-space: nowrap;margin: 0 5px;letter-spacing: 0.015em">&lt;?php snow3();?&gt;</span>
      置于<span style="padding: 2px 4px;color: #00A7EB;background-color: #fbfbfb;border: 1px solid #e1e1e8;white-space: nowrap;margin: 0 5px;letter-spacing: 0.015em">&lt;?php wp_footer();?&gt;</span> 的上方。
      <br />当使用自动加载时，手动加载函数将会返回空值。</p>
    </fieldset>
    <fieldset>
      <legend>雪花样式</legend>
      <input type="radio" name="snow3_style" value="1" <?php echo checked( 1, $style, false); ?>/>圆形3D风格
      <input type="radio" name="snow3_style" value="2" <?php echo checked( 2, $style, false); ?>/>不规则3D风格
    </fieldset>
    <fieldset>
      <legend>雪花方向</legend>
      <input type="text" name="snow3_way" value="<?php echo $way; ?>" />
      <p>雪花飘动方向，填写数值，正值从左向右飘，加“-”号从右向左飘。<font color="#F40">注意：默认为 0，即向向下飘动。数值越大，速度越快。</font></p>
    </fieldset>
    <fieldset>
      <legend>雪花速度</legend>
      <input type="text" name="snow3_speed" value="<?php echo $speed; ?>" />
      <p>建议1~10，数值越大雪花速度越快，<font color="#F40">注意：太大的数值使得雪花速度太快将无法看到雪花，建议不超过1000，测试最大速度为2800。</font></p>
    </fieldset>
    <br>
    <fieldset>
      <legend>雪花方向</legend>
      <input type="text" name="snow3_front" value="<?php echo $front; ?>" />
      <p>雪花飘动方向为正面朝向，填写数值，正值从屏幕内朝向屏幕飘，加“-”号反之。<font color="#F40">注意：默认为 0，数值越大，速度越快。</font></p>
    </fieldset>
    <fieldset>
      <legend>雪花X轴旋转</legend>
      <input type="text" name="snow3_rotateX" value="<?php echo $rotateX; ?>" />
      <p>雪花X轴旋转 <font color="#F40">注意：默认为 45。</font></p>
    </fieldset>
    <fieldset>
      <legend>雪花Y轴旋转</legend>
      <input type="text" name="snow3_rotateY" value="<?php echo $rotateY; ?>" />
      <P>雪花Y轴旋转 <font color="#F40">注意：默认为 360。</font></P>
    </fieldset>
    <fieldset>
      <legend>雪花重力加速度</legend>
      <input type="text" name="snow3_gravity" value="<?php echo $gravity; ?>" />
      <P>雪花下降的重力加速度，<font color="#F40">建议数值在0.5以下。默认为 0。</font></P>
    </fieldset>
    <fieldset>
      <legend>雪花数量</legend>
      <input type="text" name="snow3_num" value="<?php echo $num; ?>" />
      <p>当前屏幕中显示的雪花数量，数值越大雪花数量越多，<font color="#F40">注意：建议不要太多，以免影响访问速度和增加内存消耗。默认为 500。</font></p>
    </fieldset>
    <hr>
    <?php submit_button(); ?>
  </form>
</div>
<?php
}

function add_snow_style() {
  $jquery = is_numeric(get_option('snow3_jquery')) ? esc_attr(get_option('snow3_jquery')) : 0;
  wp_register_style( 'SnowCSS', plugins_url('css/Snow.css', __FILE__) );
  if ( $jquery ) {
  wp_register_script( 'jqueryJS', '//cdn.bootcss.com/jquery/3.1.1/jquery.min.js' );
  }
  wp_register_script( 'SnowJS', plugins_url('js/three.js', __FILE__) );
  wp_enqueue_script('SnowJS');
  wp_enqueue_style('SnowCSS');
  wp_enqueue_script('jqueryJS');
}

function add_snow_script() {
  $style = is_numeric(get_option('snow3_style')) ? esc_attr(get_option('snow3_style')) : 1;
  $way = is_numeric(get_option('snow3_way')) ? esc_attr(get_option('snow3_way')) : 0;
  $speed = is_numeric(get_option('snow3_speed')) ? esc_attr(get_option('snow3_speed')) : 1;
  $front = is_numeric(get_option('snow3_front')) ? esc_attr(get_option('snow3_front')) : 0;
  $rotateX = is_numeric(get_option('snow3_rotateX')) ? esc_attr(get_option('snow3_rotateX')) : 45;
  $rotateY = is_numeric(get_option('snow3_rotateY')) ? esc_attr(get_option('snow3_rotateY')) : 360;
  $gravity = is_numeric(get_option('snow3_gravity')) ? esc_attr(get_option('snow3_gravity')) : 0;
  $num = is_numeric(get_option('snow3_num')) ? esc_attr(get_option('snow3_num')) : 500;

  $options = array(
            'snowstyle'  => $style,
            'snowway'    => $way,
            'snowspeed' => $speed,
            'snowfront' => $front,
            'snowrotateX' => $rotateX,
            'snowrotateY' => $rotateY,
            'snowgravity' => $gravity,
            'snownum' => $num
        );
  $imgUrl = plugins_url('img/Snow'.$style.'.png', __FILE__);
  $snowjs = '<script type="text/javascript">';
  $snowjs .= 'function randomRange(t,i){return Math.random()*(i-t)+t}Particle3D=function(t){THREE.Particle.call(this,t),this.velocity=new THREE.Vector3('.$options['snowway'].',-'.$options['snowspeed'].','.$options['snowfront'].'),this.velocity.rotateX(randomRange(-'.$options['snowrotateX'].','.$options['snowrotateX'].')),this.velocity.rotateY(randomRange(0,'.$options['snowrotateY'].')),this.gravity=new THREE.Vector3(0,-'.$options['snowgravity'].',0),this.drag=1},Particle3D.prototype=new THREE.Particle,Particle3D.prototype.constructor=Particle3D,Particle3D.prototype.updatePhysics=function(){this.velocity.multiplyScalar(this.drag),this.velocity.addSelf(this.gravity),this.position.addSelf(this.velocity)};var TO_RADIANS=Math.PI/180;THREE.Vector3.prototype.rotateY=function(t){cosRY=Math.cos(t*TO_RADIANS),sinRY=Math.sin(t*TO_RADIANS);var i=this.z,o=this.x;this.x=o*cosRY+i*sinRY,this.z=o*-sinRY+i*cosRY},THREE.Vector3.prototype.rotateX=function(t){cosRY=Math.cos(t*TO_RADIANS),sinRY=Math.sin(t*TO_RADIANS);var i=this.z,o=this.y;this.y=o*cosRY+i*sinRY,this.z=o*-sinRY+i*cosRY},THREE.Vector3.prototype.rotateZ=function(t){cosRY=Math.cos(t*TO_RADIANS),sinRY=Math.sin(t*TO_RADIANS);var i=this.x,o=this.y;this.y=o*cosRY+i*sinRY,this.x=o*-sinRY+i*cosRY};$(function(){var container=document.querySelector(".Snow");if(/MSIE 6|MSIE 7|MSIE 8/.test(navigator.userAgent)){return}else{if(/MSIE 9|MSIE 10/.test(navigator.userAgent)){$(container).css("height",$(window).height()).bind("click",function(){$(this).fadeOut(1000,function(){$(this).remove()})})}}var containerWidth=$(container).width();var containerHeight=$(container).height();var particle;var camera;var scene;var renderer;var mouseX=0;var mouseY=0;var windowHalfX=window.innerWidth/2;var windowHalfY=window.innerHeight/2;var particles=[];var particleImage=new Image();particleImage.src="'.$imgUrl.'";';
  $snowjs .= 'var snowNum='.$options['snownum'].';function init(){camera=new THREE.PerspectiveCamera(75,containerWidth/containerHeight,1,10000);camera.position.z=1000;scene=new THREE.Scene();scene.add(camera);renderer=new THREE.CanvasRenderer();renderer.setSize(containerWidth,containerHeight);var material=new THREE.ParticleBasicMaterial({map:new THREE.Texture(particleImage)});for(var i=0;i<snowNum;i++){particle=new Particle3D(material);particle.position.x=Math.random()*2000-1000;particle.position.y=Math.random()*2000-1000;particle.position.z=Math.random()*2000-1000;particle.scale.x=particle.scale.y=1;scene.add(particle);particles.push(particle)}container.appendChild(renderer.domElement);document.addEventListener("mousemove",onDocumentMouseMove,false);setInterval(loop,1000/40)}function onDocumentMouseMove(event){mouseX=event.clientX-windowHalfX;mouseY=event.clientY-windowHalfY}function loop(){for(var i=0;i<particles.length;i++){var particle=particles[i];particle.updatePhysics();with(particle.position){if(y<-1000){y+=2000}if(x>1000){x-=2000}else{if(x<-1000){x+=2000}}if(z>1000){z-=2000}else{if(z<-1000){z+=2000}}}}camera.position.x+=(mouseX-camera.position.x)*0.005;camera.position.y+=(-mouseY-camera.position.y)*0.005;camera.lookAt(scene.position);renderer.render(scene,camera)}init()});</script>';
  $snowjs .= '<div class="Snow"></div>';

  echo $snowjs;
}
$load = is_numeric(get_option('snow3_load')) ? esc_attr(get_option('snow3_load')) : 0;
if ( $load == 0 ) {
  add_action( 'wp_enqueue_scripts', 'add_snow_style' );
  add_action( 'wp_footer', 'add_snow_script' );
}

function snow3() {
  $load = is_numeric(get_option('snow3_load')) ? esc_attr(get_option('snow3_load')) : 0;
  	if ( $load == 1 ){
    echo add_snow_script();
  }
}
?>
