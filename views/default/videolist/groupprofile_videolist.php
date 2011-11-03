<?php
/**
 * Elgg Video Plugin
 * This plugin allows users to create a library of videos for groups
 *
 * @package ElggProfile
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU Public License version 2
 * @author Prateek Choudhary <synapticfield@gmail.com>
 * @copyright Prateek Choudhary
 */

?>
<div class="group_tool_widget video">
<span class="group_widget_link"><a href="<?php echo $vars['url'] . "videolist/owned/" . page_owner_entity()->username; ?>"><?php echo elgg_echo('link:view:all')?></a></span>
<h3><?php echo elgg_echo("videolist:group"); ?></h3>

<?php

//the number of files to display
$number = (int) $vars['entity']->num_display;
if (!$number)
	$number = 5;

//get the user's files
$videos = elgg_get_entities(array('types' => 'object', 'subtypes' => 'videolist', 'container_guids' => page_owner(), 'limit' => $number));

//if there are some files, go get them
if ($videos) {
	foreach($videos as $f){
		$mime = $f->mimetype;
		$owner = get_entity($f->getOwner());
		$numcomments = elgg_count_comments($f);
		echo "<div class='entity_listing clearfloat'>";
		echo "<div class='entity_listing_icon'><a href=\"{$vars['url']}videolist/watch/{$f->guid}\"><img src=\"".$f->thumbnail."\" border=\"0\" width=\"85\" /></a></div>";
		echo "<div class='entity_listing_info'>";
		echo "<p class='entity_title'><a href=\"{$vars['url']}videolist/watch/{$f->guid}\">" . $f->title ."</a></p><p class='entity_subtext'><a href=\"{$vars['url']}profile/{$owner->username}\">{$owner->name}</a> ";
		echo friendly_time($f->time_created) . "</p>";
		echo "</div></div>";

	}

} else {
	$upload_video = $vars['url'] . "videolist/browse/" . page_owner_entity()->username;
	echo "<p class='margin_top'><a href=\"{$upload_video}\">" . elgg_echo("videolist:add") . "</a></p>";
}
echo "</div>";
?>
