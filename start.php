<?php
/**
 * Elgg Video Plugin
 * This plugin allows users to create a library of videos
 *
 * @package Elgg
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Prateek Choudhary <synapticfield@gmail.com>
 * @copyright Prateek Choudhary
 */

elgg_register_event_handler('init', 'system', 'videolist_init');

function videolist_init() {
	
	elgg_register_library('elgg:videolist', elgg_get_plugins_path() . 'videolist/lib/videolist.php');

	// add a site navigation item
	$item = new ElggMenuItem('videolist', elgg_echo('videolist'), 'videolist/all');
	elgg_register_menu_item('site', $item);

	// Extend system CSS with our own styles
	elgg_extend_view('css','videolist/css');

	// Load the language file - default is english
	register_translations(elgg_get_plugins_path() . "videolist/languages/");

	// Register a page handler, so we can have nice URLs
	elgg_register_page_handler('videolist', 'videolist_page_handler');
	
	// Language short codes must be of the form "videolist:key"
	// where key is the array key below
	elgg_set_config('videolist', array(
		'video_url' => 'url',
		'title' => 'text',
		'tags' => 'tags',
		'access_id' => 'access',
	));

	//extend this plugin for groups
	elgg_extend_view('groups/tool_latest','videolist/groupprofile_videolist');

	if (is_callable('register_notification_object')) {
		register_notification_object('object', 'videolist', elgg_echo('videolist:new'));
	}
	
	// Register a handler for adding videos
	elgg_register_event_handler('create', 'videolist', 'videolist_create_event_listener');

	// Register a handler for delete videos
	elgg_register_event_handler('delete', 'videolist', 'videolist_delete_event_listener');
	
	elgg_register_event_handler('pagesetup','system','videolist_pagesetup');
	elgg_register_event_handler('annotate','all','videolist_object_notifications');

	elgg_register_plugin_hook_handler('object:notifications','object','videolist_object_notifications_intercept');

	// Register URL handler
	elgg_register_entity_url_handler('object', 'videolist', 'video_url');
	elgg_register_entity_url_handler('object', 'watch', 'video_url');

	//register entity url handler
	elgg_register_entity_url_handler('object', 'videolist', 'videolist_url');

	// Register entity type
	elgg_register_entity_type('object','videolist');

	elgg_register_plugin_hook_handler('profile_menu', 'profile', 'videolist_profile_menu');

	// register for embed
	elgg_register_plugin_hook_handler('embed_get_sections', 'all', 'videolist_embed_get_sections');
	elgg_register_plugin_hook_handler('embed_get_items', 'videolist', 'videolist_embed_get_items');

	// override icons for ElggEntity::getIcon()
	elgg_register_plugin_hook_handler('entity:icon:url', 'user', 'profile_usericon_hook');
	
	// Register actions
	$actions_path = elgg_get_plugins_path() . "videolist/actions/videolist";
	elgg_register_action("videolist/add", "$actions_path/add.php");
	elgg_register_action("videolist/edit", "$actions_path/edit.php");
	elgg_register_action("videolist/tubesearch", "$actions_path/tubesearch.php");
	elgg_register_action("videolist/delete", "$actions_path/delete.php");
}

/**
 * Dispatches blog pages.
 * URLs take the form of
 *  All videos:       videolist/all
 *  User's videos:    videolist/owner/<username>
 *  Friends' videos:  videolist/friends/<username>
 *  Video watch:      videolist/watch/<guid>/<title>
 *  Video browse:     videolist/browse
 *  New video:        videolist/add/<guid>
 *  Edit video:       videolist/edit/<guid>/<revision>
 *  Group videos:     videolist/group/<guid>/all
 *
 * Title is ignored
 *
 * @param array $page
 * @return NULL
 */
function videolist_page_handler($page) {
	
	if (!isset($page[0])) {
		$page[0] = 'all';
	}

	$videolist_dir = elgg_get_plugins_path() . 'videolist/pages/videolist';

	$page_type = $page[0];
	switch ($page_type) {
		case 'owner':
			include "$videolist_dir/owner.php";
			break;
		case 'friends':
			include "$videolist_dir/friends.php";
			break;
		case 'watch':
			set_input('guid', $page[1]);
			include "$videolist_dir/watch.php";
			break;
		case 'add':
			include "$videolist_dir/add.php";
			break;
		case 'edit':
			set_input('guid', $page[1]);
			include "$videolist_dir/edit.php";
			break;
		case 'browse':
			include "$videolist_dir/browse.php";
			break;
		case 'group':
			include "$videolist_dir/owner.php";
			break;
		case 'all':
		default:
			include "$videolist_dir/all.php";
			break;
	}
}


function videolist_pagesetup() {
	$page_owner = elgg_get_page_owner_entity();

	if ($page_owner instanceof ElggGroup && elgg_in_context("groups")) {
		//add_submenu_item(sprintf(elgg_echo("videolist:group"), page_owner_entity()->name), elgg_get_site_url() . "videolist/owned/" . page_owner_entity()->username);
	} else if (elgg_in_context("videolist")) {
		/**********************************************************************************************
		****if user is OR is not registered user then show him following page menus to choose from
		***********************************************************************************************/
		/*
		add_submenu_item(elgg_echo('videolist:home'),elgg_get_site_url()."videolist/". $page_owner->username);

		add_submenu_item(elgg_echo('videolist:new'),elgg_get_site_url()."videolist/new");

		add_submenu_item(elgg_echo('videolist:find'),elgg_get_site_url()."videolist/search/");
		*/
	} else if (elgg_get_context("group")) {
		//add_submenu_item(sprintf(elgg_echo("videolist:home"),page_owner_entity()->name), elgg_get_site_url() . "videolist/owned/" . page_owner_entity()->username);
		if ($page_owner && $page_owner->canEdit()) {
			//add_submenu_item(sprintf(elgg_echo('videolist:browsemenu'),page_owner_entity()->name), elgg_get_site_url() . "videolist/browse/". page_owner_entity()->username);
			//add_submenu_item(sprintf(elgg_echo('videolist:new'),page_owner_entity()->name), elgg_get_site_url() . "videolist/new/". page_owner_entity()->username);
		}
	}
}

function video_url($entity) {
	$video_id = $entity->video_id;
	return elgg_get_site_url() . "videolist/watch/" . $entity->getGUID() . "/" . $video_id;
}

function videolist_url($videolistpage) {
	$owner = $videolistpage->container_guid;
	$userdata = get_entity($owner);
	$title = $videolistpage->title;
	$title = friendly_title($title);
	return elgg_get_site_url() . "videolist/watch/" . $videolistpage->getGUID();
}

/**
 * Event handler for videolist
 *
 */
function videolist_object_notifications($event, $object_type, $object) {
	static $flag;
	if (!isset($flag)) {
		$flag = 0;
	}

	if (is_callable('object_notifications')) {
		if ($object instanceof ElggObject) {
			if ($object->getSubtype() == 'videolist') {
				if ($flag == 0) {
					$flag = 1;
					object_notifications($event, $object_type, $object);
				}
			}
		}
	}
}

/**
 * Intercepts the notification on an event of new video being created and prevents a notification from going out
 * (because one will be sent on the annotation)
 *
 * @param unknown_type $hook
 * @param unknown_type $entity_type
 * @param unknown_type $returnvalue
 * @param unknown_type $params
 * @return unknown
 */
function videolist_object_notifications_intercept($hook, $entity_type, $returnvalue, $params) {
	if (isset($params)) {
		if ($params['event'] == 'create' && $params['object'] instanceof ElggObject) {
			if ($params['object']->getSubtype() == 'videolist') {
				return true;
			}
		}
	}
	return null;
}

function videolist_profile_menu($hook, $entity_type, $return_value, $params) {
	$return_value[] = array(
		'text' => elgg_echo('videolist'),
		'href' => elgg_get_site_url() . "videolist/owned/{$params['owner']->username}",
	);

	return $return_value;
}


/**
 * Register videolist as an embed type.
 *
 * @param unknown_type $hook
 * @param unknown_type $type
 * @param unknown_type $value
 * @param unknown_type $params
 */
function videolist_embed_get_sections($hook, $type, $value, $params) {
	$value['videolist'] = array(
		'name' => elgg_echo('videolist'),
		'layout' => 'list',
		'icon_size' => 'medium',
	);

	return $value;
}

/**
 * Return a list of videos for embedding
 *
 * @param unknown_type $hook
 * @param unknown_type $type
 * @param unknown_type $value
 * @param unknown_type $params
 */
function videolist_embed_get_items($hook, $type, $value, $params) {
	$options = array(
		'owner_guid' => get_loggedin_userid(),
		'type_subtype_pair' => array('object' => 'videolist'),
		'count' => TRUE
	);

	$count = elgg_get_entities($options);
	$value['count'] += $count;

	unset($options['count']);
	$options['offset'] = $params['offset'];
	$options['limit'] = $params['limit'];

	$items = elgg_get_entities($options);

	$value['items'] = array_merge($items, $value['items']);

	return $value;
}

/**
 * Returns the URL of the icon for $entity at $size.
 *
 * @param ElggEntity $entity
 * @param string $size Not used yet.  Not sure if possible.
 */
function videolist_get_entity_icon_url(ElggEntity $entity, $size = 'medium') {

	// tiny thumbnails are too small to be useful, so give a generic video icon
	if ($size == 'tiny') {
		return elgg_get_site_url() . "mod/videolist/graphics/video_icon_tiny.png";
	}

	return $entity->thumbnail;
}
