<?php

/**
  * Shows all comments for the site with a delete link
  *
  * PHP Version 5
  *
  * @category   CommentsPlugin
  * @package    WebworksWebme
  * @subpackage CommentsPlugin
  * @author     Belinda Hamilton <bhamilton@webworks.ie>
  * @license    GPL Version 2.0
  * @link       www.webworks.ie
**/

// { global options
echo '<table>';
// { use moderation
echo '<table><tr><th>Don\'t moderate comments for this site?</th>';
$noModeration = 0;
$sql = 'select value from site_vars where name = "comments_no_moderation"';
if (dbOne($sql, 'value')) {
	$noModeration = dbOne($sql, 'value');
}
echo '<td><script>noModeration = '.$noModeration.'</script>';
echo '<input type="checkbox" id="no_moderation"';
if ($noModeration) {
	echo ' checked = "checked"';
}
echo ' onchange="set_moderation();" /></td></tr>';
// }
// { use captchas
echo '<tr><th>Don\'t use captchas for spam filtering?</th>';
$noCaptchas = 0;
$sql = 'select value from site_vars where name = "comments_no_captchas"';
if (dbOne($sql, 'value')) {
	$noCaptchas = dbOne($sql, 'value');
}
echo '<td><input type="checkbox" id="no_captchas"';
if ($noCaptchas) {
	echo ' checked = "checked"';
}
echo ' onchange="set_captchas();" /></td></tr>';
// }
echo '</table>';
// }
echo '<strong>Comments</strong>';
$comments = dbAll('select * from comments');
echo '<div style="width:80%">';
echo '<table id="comments-table" style="width:100%"><thead><tr>';
echo '<th>Date</th>';
echo '<th>Name</th>';
echo '<th>Email</th>';
echo '<th>URL</th>';
echo '<th>Comment</th>';
echo '<th>Mod</th>';
echo '<th>Edit</th>';
echo '<th>Delete</th>';
echo '</tr></thead><tbody>';
foreach ($comments as $comment) {
	$id = $comment['id'];
	echo '<tr id="comment-'.$id.'">';
	echo '<td>'.$comment['cdate'].'</td>';
	echo '<td>'.$comment['name'].'</td>';
	echo '<td>'.$comment['email'].'</td>';
	echo '<td>'.$comment['homepage'].'</td>';
	echo '<td>'.$comment['comment'].'</td>';
	echo '<td>';
	echo '<a href="javascript:;" onclick="start_moderation('
		.$id.','.((-1*$comment['isvalid'])+1).')">';
	if ($comment['isvalid']) {
		echo 'Unapprove';
	}
	else {
		echo 'Approve';
	}
	echo '</a></td>';
	echo '<td><a href="javascript:;" '
		.'"onclick="start_edit('.$id.',\''.$comment['comment'].'\');">';
	echo 'edit</a></td>';
	echo '<td><a href="javascript:;" onclick="start_delete('.$id.')">[x]</a>';
	echo '</td>';
	echo '</tr>';
}
echo '</tbody></table>';
echo '</div>';
ww_addScript('/ww.plugins/comments/admin/comments.js');
