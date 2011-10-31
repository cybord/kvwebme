<?php
/**
	* widget for displaying a list of products or categories
	*
	* PHP version 5.2
	*
	* @category None
	* @package  None
	* @author   Kae Verens <kae@kvsites.ie>
	* @license  GPL 2.0
	* @link     http://kvsites.ie/
	*/

// { functions
/**
	* get a list of sub-categories in UL format
	*
	* @param int $pid product category ID
	*
	* @return string $html the UL
	*/
function Products_categoriesListSubCats($pid) {
	$cats=dbAll(
		'select id,name from products_categories '
		.'where parent_id='.$pid.' and enabled order by sortNum,name'
	);
	if (!$cats || !count($cats)) {
		return '';
	}
	$html='<ul>';
	foreach ($cats as $c) {
		$cat=ProductCategory::getInstance($c['id']);
		$html.='<li><a href="'.$cat->getRelativeUrl().'">'.$c['name'].'</a>';
		$html.='</li>';
	}
	return $html.'</ul>';
}
// }

$html='';
$widget_type=isset($vars->widget_type) && $vars->widget_type
	?$vars->widget_type
	:'List Categories';
$diameter=isset($vars->diameter) && $vars->diameter?$vars->diameter:280;
$parent_cat=isset($vars->parent_cat)?((int)$vars->parent_cat):0;
$cats=dbAll(
	'select id,name,associated_colour as col from products_categories '
	.'where parent_id='.$parent_cat.' and enabled order by sortNum,name'
);

switch ($widget_type) {
	case 'Pie Chart': // { Pie Chart
		$id='products_categories_'.md5(rand());
		$html.='<div id="'.$id.'" class="products-widget" style="width:'.$diameter
			.'px;height:'.($diameter+30).'px">loading...</div>'
			.'<script>$(function(){'
			.'products_widget("'.$id.'",'.json_encode($cats).');'
			.'});</script>';
		$html.='<!--[if IE]><script src="/ww.plugins/products/frontend/excanvas.js">'
			.'</script><![endif]-->';
		WW_addScript('/ww.plugins/products/frontend/jquery.canvas.js');
		WW_addScript('/ww.plugins/products/frontend/widget.js');
	break; // }
	case 'Products': // { Products
		$html='<div class="products-widget-products">';
		$products=Products::getByCategory($parent_cat);
		foreach ($products->product_ids as $pid) {
			$product=Product::getInstance($pid);
			$iid=$product->getDefaultImage();
			$img=$iid
				?'<a href="'.$product->getRelativeURL().'"><img src="/kfmget/'
				.$iid.'&amp;width=100&amp;height=100"/></a>'
				:'';
			$html.='<table class="product"><tr><td rowspan="2">'.$img
				.'</td><td><strong>'.htmlspecialchars($product->name).'</strong>'
				.'<p class="base-price">was: '.$_SESSION['currency']['symbol']
				.$product->vals['online-store']['_price'].'</p>'
				.'<p class="sale-price">now: '.$_SESSION['currency']['symbol']
				.$product->vals['online-store']['_sale_price'].'</p>'
				.'</td></tr>'
				.'<tr><td><a href="'.$product->getRelativeURL().'">more info</a>'
				.'</td></tr></table>';
		}
		$html.='</div>';
	break; // }
	case 'Tree View': // { Tree View
		$html='<div class="product-categories-tree"><ul>';
		foreach ($cats as $c) {
			$cat=ProductCategory::getInstance($c['id']);
			$html.='<li><a href="'.$cat->getRelativeUrl().'">'.$c['name'].'</a>';
			$html.=Products_categoriesListSubCats($c['id']);
			$html.='</li>';
		}
		$html.='</ul></div>';
		WW_addScript('/j/jstree/jquery.jstree.js');
		WW_addScript('/ww.plugins/products/j/categories-tree.js');
	break; // }
	default: // { List Categories
		$html='<ul>';
		foreach ($cats as $c) {
			$html.='<li><a href="/_r?type=products&product_cid='.$c['id'].'">'
				.$c['name'].'</a></li>';
		}
		$html.='</ul>';
	break; // }
}
