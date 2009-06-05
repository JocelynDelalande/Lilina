<?php
/**
 * Common administration helpers
 *
 * @author Ryan McCue <cubegames@gmail.com>
 * @package Lilina
 * @version 1.0
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/**
 * generate_nonce() - Generates nonce
 *
 * Uses the current time
 * @global array Need settings for user and password
 * @param string $nonce Supplied nonce
 * @return bool True if nonce is equal, false if not
 */
function generate_nonce() {
	$user_settings = get_option('auth');
	$time = ceil(time() / 43200);
	return md5($time . get_option('auth', 'user') . get_option('auth', 'pass'));
}

/**
 * check_nonce() - Checks whether supplied nonce matches current nonce
 * @global array Need settings for user and password
 * @param string $nonce Supplied nonce
 * @return bool True if nonce is equal, false if not
 */
function check_nonce($nonce) {
	$user_settings = get_option('auth');
	$time = ceil(time() / 43200);
	$current_nonce = md5($time . get_option('auth', 'user') . get_option('auth', 'pass'));
	if($nonce !== $current_nonce) {
		return false;
	}
	return true;
}


function admin_header($title, $parent_file = false) {
	$self = preg_replace('|^.*/admin/|i', '', $_SERVER['PHP_SELF']);
	$self = preg_replace('|^.*/plugins/|i', '', $self);

	header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo $title ?> &mdash; <?php echo get_option('sitename'); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="<?php echo get_option('baseurl'); ?>admin/resources/jquery-ui.css" media="screen"/>
<link rel="stylesheet" type="text/css" href="<?php echo get_option('baseurl'); ?>admin/resources/core.css" media="screen"/>
<link rel="stylesheet" type="text/css" href="<?php echo get_option('baseurl'); ?>admin/resources/full.css" media="screen"/>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<script type="text/javascript" src="<?php echo get_option('baseurl'); ?>inc/js/jquery.js"></script>
<script type="text/javascript" src="<?php echo get_option('baseurl'); ?>inc/js/json2.js"></script>
<script type="text/javascript" src="<?php echo get_option('baseurl'); ?>inc/js/jquery.ui.js"></script>
<script type="text/javascript" src="<?php echo get_option('baseurl'); ?>inc/js/jquery.scrollTo.js"></script>
<script type="text/javascript" src="<?php echo get_option('baseurl'); ?>inc/js/humanmsg.js"></script>
<script type="text/javascript" src="<?php echo get_option('baseurl'); ?>admin/admin.js"></script>
</head>
<body id="admin-<?php echo $self; ?>" class="admin-page">
<div id="header">
	<p id="sitetitle"><a href="<?php echo get_option('baseurl'); ?>"><?php echo get_option('sitename'); ?></a></p>
	<ul id="navigation">
<?php
	$navigation = array(
		array(_r('Dashboard'), 'index.php', ''),
		array(_r('Feeds'), 'feeds.php', 'feeds'),
		array(_r('Settings'), 'settings.php', 'settings'),
	);
	$navigation = apply_filters('navigation', $navigation);

	$subnavigation = apply_filters('subnavigation', array(
		'index.php' => array(
			array(_r('Home'), 'index.php', 'home'),
		),
		'feeds.php' => array(
			array(_r('Add/Manage'), 'feeds.php', 'feeds'),
			array(_r('Import'), 'feed-import.php', 'feeds'),
		),
		'settings.php' => array(
			array(_r('General'), 'settings.php', 'settings'),
		),
	), $navigation, $self);

	foreach($navigation as $nav_item) {
		$class = 'item';
		if((strcmp($self, $nav_item[1]) == 0) || ($parent_file && ($nav_item[1] == $parent_file))) {
			$class .= ' current';
		}

		if(isset($subnavigation[$nav_item[1]]) && count($subnavigation[$nav_item[1]]) > 1)
			$class .= ' has-submenu';

		echo "<li class='$class'><a href='{$nav_item[1]}'>{$nav_item[0]}</a>";
		
		if(!isset($subnavigation[$nav_item[1]]) || count($subnavigation[$nav_item[1]]) < 2) {
			echo "</li>";
			continue;
		}
		
		echo '<ul class="submenu">';
		foreach($subnavigation[$nav_item[1]] as $subnav_item) {
			echo '<li' . ((strcmp($self, $subnav_item[1]) == 0) ? ' class="current"' : '') . "><a href='{$subnav_item[1]}'>{$subnav_item[0]}</a></li>";
		}
		echo '</ul></li>';
		
	}
?>
			<li id="page_item_logout" class="seperator"><a href="admin.php?logout=logout" title="<?php _e('Log out of your current session'); ?>"><?php _e('Log out'); ?></a></li>
	</ul>
</div>
<div id="main">
<?php
	if($result = implode('</p><p>', MessageHandler::get())) {
		echo '<div id="alert" class="fade"><p>' . $result . '</p></div>';
	}
	do_action('admin_header');
	do_action("admin_header-$self");
	do_action('send_headers');
}

function admin_footer() {
?>
</div>
<p id="footer"><?php
_e('Powered by <a href="http://getlilina.org/">Lilina News Aggregator</a>');
do_action('admin_footer'); ?> | <a href="http://getlilina.org/docs/start"><?php _e('Documentation') ?></a> | <a href="http://getlilina.org/forums/" title="<?php _e('Support on the Forums') ?>"><?php _e('Support') ?></a></p>
<?php gsfn_feedback_widget() ?>
</body>
</html>
<?php
}
?>