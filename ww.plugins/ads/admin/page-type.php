<?php
/**
  * Ads page type
  *
  * PHP Version 5
  *
  * @category   None
  * @package    None
  * @subpackage None
  * @author     Kae Verens <kae@kvsites.ie>
  * @license    GPL Version 2
  * @link       www.kvweb.me
 */

$c='<div class="tabs">'
	.'<ul>'
	.'<li><a href="#ads-header">Header</a></li>'
	.'<li><a href="#ads-footer">Footer</a></li>'
	.'<li><a href="#ads-payment-details">Options</a></li>'
	.'</ul>';
// { header
$c.='<div id="ads-header">'
	.'<p>'.__('This text will appear above the contents.').'</p>';
$c.=ckeditor('body', $page['body']);
$c.='</div>';
// }
// { footer
$c.='<div id="ads-footer">'
	.'<p>'.__('This text will appear below the contents.').'</p>';
$c.=ckeditor('page_vars[footer]', (isset($vars['footer'])?$vars['footer']:''));
$c.='</div>';
// }
// { payment details
$c.='<div id="ads-payment-details"><table>'
	.'<tr><th>'.__('Paypal address').'</th><td><input name="page_vars[ads-paypal]" value="'
	.htmlspecialchars($vars['ads-paypal']).'" type="email" /></td></tr>'
	.'<tr><th>'.__('Profile Page Payment Tab').'</th><td>'
	.ckeditor('page_vars[ads-profile-page]', (isset($vars['ads-profile-page'])?$vars['ads-profile-page']:''))
	.'</td></tr>'
	.'<tr><th>Fullpage parent</th><td>'
	.'<select id="ads_fullpage_parent" name="'
	.'page_vars[ads_fullpage_parent]">';
if (@$vars['ads_fullpage_parent']) {
	$parent=Page::getInstance($vars['ads_fullpage_parent']);
	$c.='<option value="'.$parent->id.'">'
		.htmlspecialchars(__FromJSON($parent->name))
		.'</option>';
}
else {
	$page_vars['ads_fullpage_parent']=0;
	$c.='<option value="0"> -- none -- </option>';
}
$c.='</select></td></tr>'
	.'</table></div>';
// }
$c.='</div>';
$c.='<script src="/ww.plugins/ads/admin/page-type.js"></script>';
