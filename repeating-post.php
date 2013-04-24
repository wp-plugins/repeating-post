<?php
/*
Plugin Name: Repeating Posts
Plugin URI: http://shynnergy.com
Description: Schedules repeating posts.
Version: 0.2
Author: Chris Yeoh
Author URI: http://shynnergy.com
License: GPL2
*/

/*  Copyright 2013  Chris Yeoh  (email : shynnergy@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (is_admin()){
  require_once (dirname(__FILE__).'/repeating-post-admin.php');
  wp_enqueue_script('/jquery-ui-datepicker');
}

?>