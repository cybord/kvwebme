<?php
/**
	* admin functions for Products
	*
	* PHP version 5.2
	*
	* @category None
	* @package  None
	* @author   Kae Verens <kae@kvsites.ie>
	* @license  GPL 2.0
	* @link     http://kvsites.ie/
	*/

// { Products_adminCategoriesGetJSTree

/**
	* get a recursive list of all categories
	*
	* @param array $params parameters
	* @param int   $pid    parent ID
	*
	* @return array categories
	*/
function Products_adminCategoriesGetJSTree($params=array(), $pid=0) {
	$sql='select id,name from products_categories where parent_id='.$pid
		.' order by sortNum, name';
	$cats=dbAll($sql, false, 'products_categories');
	$arr=array();
	foreach ($cats as $cat) {
		$obj=array(
			'data'=>$cat['name'],
			'attr'=>array(
				'id'=>'cat-'.$cat['id']
			)
		);
		$children=Products_adminCategoriesGetJSTree($params, $cat['id']);
		if (count($children)) {
			$obj['children']=$children;
		}
		$arr[]=$obj;
	}
	return $arr;
}

// }
// { Products_adminCategoriesGetRecursiveList

/**
	* get a recursive list of all categories
	*
	* @param array $params parameters
	* @param int   $pid    parent ID
	* @param int   $level  current level of the tree
	*
	* @return array categories
	*/
function Products_adminCategoriesGetRecursiveList(
	$params=array(),
	$pid=0,
	$level=0
) {
	$sql='select id,name from products_categories where parent_id='.$pid
		.' order by name';
	$cats=dbAll($sql, false, 'products_categories');
	$arr=array();
	foreach ($cats as $cat) {
		$arr[' '.$cat['id']]=str_repeat(' - ', $level).$cat['name'];
		$arr=array_merge(
			$arr,
			Products_adminCategoriesGetRecursiveList($params, $cat['id'], $level+1)
		);
	}
	return $arr;
}

// }
function Products_adminCategoriesGetByParent() {
	$pid=(int)$_REQUEST['pid'];
	return dbAll(
		'select id,name from products_categories where parent_id='.$pid
		.' order by sortNum', '', 'products_categories'
	);
}
function Products_adminCategoriesClean() {
	// { find broken product-category links
	$rs=ProductsCategoriesProducts::listActiveCategories();
	foreach ($rs as $r) {
		echo 'category '.$r.'<br/>';
		$c=dbRow('select id from product_categories where id='.$r);
		if (!$c) {
			$pids=ProductsCategoriesProducts::getByCategoryId($r);
			foreach ($pids as $p) {
				ProductsCategoriesProducts::delete($r, $p);
			}
			Products_categoriesRecount(array($ps));
			echo 'cleaned up '.$r
				.'<script>document.location="/a/p=products/f=adminCategoriesClean";</script>';
			exit;
		}
	}
	// }
}
function Products_adminCategoriesRecount() {
	$now=time();
	$id=isset($_REQUEST['id'])?(int)$_REQUEST['id']:0;
	do {
		$id=dbOne('select id from products where id>'.$id.' order by id limit 1', 'id');
		if ($id) {
			Products_categoriesRecount(array($id));
		}
		echo $id.' ';
	} while($id && time()<($now+10));
	if ($id) {
		echo '<script>document.location="/a/p=products/f=adminCategoriesRecount/id='.$id
			.'";</script>';
	}
	exit;
}
function Products_adminCategoriesRemoveEmpty() {
	$rs=dbAll('select a.id, a.name, (select count(id) from products_categories where parent_id=a.id) as cats, (select count(product_id) from products_categories_products where category_id=a.id) as products from products_categories as a;');
	foreach ($rs as $r) {
		if (!(int)$r['products'] && !(int)$r['cats']) {
			$_REQUEST['id']=$r['id'];
			Products_adminCategoryDelete();
		}
	}
}
// { Products_adminCategoryDelete

/**
	* delete a category
	*
	* @return null
	*/
function Products_adminCategoryDelete() {
	if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
		Core_quit();
	}
	$id=(int)$_REQUEST['id'];
	if ($id==1) {
		return array('status'=>0);
	}
	$parent=ProductCategory::getInstance($id)->vals['parent_id'];
	dbQuery(
		'update products_categories set parent_id='.$parent.' where parent='.$id
	);
	$pids=ProductsCategoriesProducts::getByCategoryId($id);
	ProductsCategoriesProducts::deleteByCategoryId($id);
	Products_categoriesRecount($pids);
	dbQuery('delete from products_categories where id='.$id);
	Core_cacheClear('products_categories');
	return array('status'=>1);
}

// }
// { Products_adminCategoryEdit

/**
	* edit a category
	*
	* @return array the category data
	*/
function Products_adminCategoryEdit() {
	if (!is_numeric(@$_REQUEST['id']) || @$_REQUEST['name']==''
	) {
		Core_quit();
	}
	$id=(int)$_REQUEST['id'];
	$sql='update products_categories set name="'.addslashes($_REQUEST['name']).'"'
		.', enabled="'.((int)$_REQUEST['enabled']).'"'
		.', thumbsize_w='.(int)$_REQUEST['thumbsize_w']
		.', thumbsize_h='.(int)$_REQUEST['thumbsize_h'];
	if (isset($_REQUEST['associated_colour']) && strlen($_REQUEST['associated_colour'])==6) {
		$sql.=', associated_colour="'.addslashes($_REQUEST['associated_colour']).'"';
	}
	$sql.=' where id='.$id;
	dbQuery($sql);
	Core_cacheClear('products_categories');
	$pageid=dbOne(
		'select page_id from page_vars where name="products_category_to_show" '
		.'and value='.$id,
		'page_id', 'page_vars'
	);
	if ($pageid) {
		dbQuery('update pages set special = special|2 where id='.$pageid);
		Core_cacheClear('pages');
	}
	$data=Products_adminCategoryGetFromID($id);
	return $data;
}

// }
// { Products_adminCategoryGetFromID

/**
	* get a category row from its id
	*
	* @param int $id the category ID
	*
	* @return array the data
	*/
function Products_adminCategoryGetFromID($id) {
	$pageid=dbOne(
		'select page_id from page_vars where name="products_category_to_show"'
		.' and value='.$id, 'page_id', 'page_vars'
	);
	$vals=ProductCategory::getInstance($id)->vals;
	$data=array(
		'attrs'=>array(
			'thumbsize_w'=>$vals['thumbsize_w'],
			'thumbsize_h'=>$vals['thumbsize_h'],
			'id'=>$vals['id'],
			'associated_colour'=>$vals['associated_colour'],
			'name'=>$vals['name'],
			'enabled'=>$vals['enabled'],
			'parent_id'=>$vals['parent_id']
		),
		'products'=>ProductsCategoriesProducts::getByCategoryId($id),
		'hasIcon'=>file_exists(USERBASE.'/f/products/categories/'.$id.'/icon.png')
			?filemtime(USERBASE.'/f/products/categories/'.$id.'/icon.png'):0
	);
	if (isset($pageid)) {
		$page= Page::getInstance($pageid);
		if ($page) {
			$url= $page->getRelativeUrl();
			$data['page']= $url;
		}
	}
	return $data;
}

// }
// { Products_adminCategoryGet

/**
	* get details about a category
	*
	* @return array the details
	*/
function Products_adminCategoryGet() {
	if (!isset($_REQUEST['id']) || !is_numeric($_REQUEST['id'])) {
		Core_quit();
	}
	return Products_adminCategoryGetFromID($_REQUEST['id']);
}

// }
// { Products_adminCategoryNew

/**
	* add a new category
	*
	* @return array category data
	*/
function Products_adminCategoryNew() {	
	if (!is_numeric(@$_REQUEST['parent_id']) || @$_REQUEST['name']=='') {	
		Core_quit();
	}
	dbQuery(
		'insert into products_categories set name="'.addslashes($_REQUEST['name'])
		.'",enabled=1,parent_id='.$_REQUEST['parent_id']
	);
	$id=dbOne('select last_insert_id() as id', 'id');
	Core_cacheClear('products_categories');
	$data=Products_adminCategoryGetFromID($id);	
	return $data;
}

// }
// { Products_adminCategoryMove

/**
	* move a category
	*
	* @return array status of the move
	*/
function Products_adminCategoryMove() {
	$cid=(int)$_REQUEST['id'];
	$pid=(int)$_REQUEST['parent_id'];
	dbQuery('update products_categories set parent_id='.$pid.' where id='.$cid);
	if (isset($_REQUEST['order'])) {
		$order=explode(',', $_REQUEST['order']);
		for ($i=0;$i<count($order);++$i) {
			$cid=(int)$order[$i];
			dbQuery('update products_categories set sortNum='.$i.' where id='.$cid);
		}
	}
	else if (isset($_REQUEST['index'])) {
		$rs=dbAll(
			'select id from products_categories where parent_id='.$pid
			.' and id!='.$cid
			.' order by sortNum',
			false, 'products_categories'
		);
		$index=(int)$_REQUEST['index'];
		for ($i=0;$i<count($rs);++$i) {
			$index2=($i>=$index)?$i+1:$i;
			dbQuery(
				'update products_categories set sortNum='.$index2
				.' where id='.$rs[$i]['id']
			);
		}
		dbQuery('update products_categories set sortNum='.$index.' where id='.$cid);
	}
	Core_cacheClear('products,products_categories');
	return Products_adminCategoryGetFromID($cid);
}

// }
// { Products_adminCategoryProductAdd

/**
	* add a product to a category
	*
	* @return null
	*/
function Products_adminCategoryProductAdd() {
	$pids=explode(',', $_REQUEST['pid']);
	$cid=(int)$_REQUEST['cid'];
	$arr=array();
	foreach ($pids as $pid) {
		$pid=(int)$pid;
		$arr[]=(int)$pid;
		ProductsCategoriesProducts::delete($cid, $pid);
	}
	foreach ($pids as $pid) {
		$pid=(int)$pid;
		ProductsCategoriesProducts::insert($cid, $pid);
	}
	Products_categoriesRecount($pids);
	dbQuery(
		'update products set date_edited=now() where id in ('.join(', ', $arr).')'
	);
	Core_cacheClear('products');
	return array('ok'=>1);
}

// }
// { Products_adminCategoryProductRemove

/**
	* remove a product from a category
	*
	* @return null
	*/
function Products_adminCategoryProductRemove() {
	if ($_REQUEST['pid']=='') {
		return array('ok'=>1);
	}
	$pids=explode(',', $_REQUEST['pid']);
	$cid=(int)$_REQUEST['cid'];
	$arr=array();
	foreach ($pids as $pid) {
		$pid=(int)$pid;
		$arr[]=$pid;
		ProductsCategoriesProducts::delete($cid, $pid);
	}
	Products_categoriesRecount($pids);
	dbQuery(
		'update products set date_edited=now() where id in ('.join(', ', $arr).')'
	);
	Core_cacheClear('products');
	return array('ok'=>1);
}

// }
function Products_adminCategoriesByCount() {
	return ProductsCategoriesProducts::listCategoriesByProductCount();
}
// { Products_adminCategoryProductsList

/**
	* get full list of products in all categories
	*
	* @return array
	*/
function Products_adminCategoryProductsList() {
	return ProductsCategoriesProducts::listAll();
}

// }
// { Products_adminCategorySetIcon

/**
	* set the icon of a category
	*
	* @return array result of upload
	*/
function Products_adminCategorySetIcon() {
	$cat_id=(int)$_REQUEST['cat_id'];
	$cat=ProductCategory::getInstance($cat_id);
	$dir=USERBASE.'/f/products/categories/'.$cat_id;
	@mkdir($dir, 0777, true);
	$tmpname=$_FILES['Filedata']['tmp_name'];
	list($width, $height)=getimagesize($tmpname);
	$thumbw=(int)$cat->vals['thumbsize_w'];
	$thumbh=(int)$cat->vals['thumbsize_h'];
	if ($width>$thumbw || $height>$thumbh) {
		CoreGraphics::resize($tmpname, $dir.'/icon.png', $thumbw, $thumbh);
	}
	else {
		move_uploaded_file($tmpname, $dir.'/icon.png');
	}
	return array('ok'=>1);
}

// }
// { Products_adminDatafieldsList

/**
	* get data fields in <option> format
	*
	* @return null
	*/
function Products_adminDatafieldsList() {
	$fields=array();
	$filter='';
	$arr=array(
		'_name'=>'Name',
		'_activates_on'=>'Publish date',
		'_expires_on'=>'Expiry date',
		'_os_base_price'=>'Price'
	);
	if ($_REQUEST['other_GET_params']) {
		if (is_numeric($_REQUEST['other_GET_params'])) { // product type
			$filter=' where id='.(int)$_REQUEST['other_GET_params'];
		}
		elseif (strpos($_REQUEST['other_GET_params'], 'c')===0) {
			$cat=(int)str_replace('c', '', $_REQUEST['other_GET_params']);
			if ($cat==0) {
				$rs=dbAll('select distinct product_type_id from products');
			}
			else {
				$arr2=ProductsCategoriesProducts::getByCategoryId($cat);
				if (!count($arr2)) {
					return $arr;
				}
				$sql='select distinct product_type_id from products where id in ('
					.join(',', $arr2).')';
				$rs=dbAll($sql, false, 'products');
			}
			$arr2=array();
			foreach ($rs as $r) {
				$arr2[]=$r['product_type_id'];
			}
			if (!count($arr2)) {
				return $arr;
			}
			$filter=' where id in ('.join(',', $arr2).')';
		}
	}
	$rs=dbAll('select data_fields from products_types'.$filter);
	foreach ($rs as $r) {
		$fs=json_decode($r['data_fields']);
		foreach ($fs as $f) {
			$fields[]=$f->n;
		}
	}
	$fields=array_unique($fields);
	asort($fields);
	foreach ($fields as $field) {
		$arr[$field]=$field;
	}
	return $arr;
}

// }
// { Products_adminExport

/**
  * Gets the data for all the products and prompts the user to save it
	*
	* @return null
	*/
function Products_adminExport() {
	$filename = 'webme_products_export_'.date('Y-m-d').'.csv';
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment; filename="'.$filename.'"');
	// { Get the headers
	$fields = dbAll('describe products');
	$row = '';
	foreach ($fields as $field) {
	    $row.= '"_'.$field['Field'].'",';
	}
	$row.="\"_categories\",\"_has_images\"\n";
	$contents = $row;
	// } 
	// { Get the data
	$results = dbAll('select * from products');
	foreach ($results as $product) {
		$row = '';
		foreach ($fields as $field) {
			$row.= '"'.str_replace('"', '""', $product[$field['Field']]).'",';
		}
		$cats=ProductsCategoriesProducts::getByProductId($product['id']);
		$catsArr=array();
		foreach ($cats as $cat) {
			$vals=ProductCategory::getInstance($cat)->vals;
			$info=array(
				'name'=>$vals['name'],
				'parent_id'=>$vals['parent_id']
			);
			$thisCat = '';
			$catName = $info['name'];
			$thisCat.=$catName;
			$parent = $info['parent_id'];
			while ($parent>0) {
				$vals=ProductCategory::getInstance($parent)->vals;
				$info=array(
					'name'=>$vals['name'],
					'parent_id'=>$vals['parent_id']
				);
				$parentName = $info['name'];
				$thisCat = $parentName.'>'.$thisCat;
				$parent = $info['parent_id'];
			}
			$catsArr[]=$thisCat;
		}
		$row.='"'.join('|', $catsArr).'"';
		// { has images
		$has_images=0;
		if ($product['images_directory']
			&& @is_dir(USERBASE.'/f/'.$product['images_directory'])
		) {
			$dir=new DirectoryIterator(USERBASE.'/f/'.$product['images_directory']);
			foreach ($dir as $f) {
				if ($f->isDot()) {
					continue;
				}
				if ($f->isFile()) {
					$has_images++;
				}
			}
		}
		$row.=',"'.($has_images?'Yes':'No').'"';
		// }
		$contents.=$row."\n";
	}
	echo $contents;
	// }
	Core_quit();
}

// }
// { Products_adminImportFile

/**
	* import from an uploaded file as a logged-in admin
	*
	* @return status
	*/
function Products_adminImportFile() {
	// { get import vals
	$vars=(object)AdminVars::getAllStartsWith('productsImport');
	return Products_importFile($vars);
}

// }

// }
// { Products_adminImportFileUpload

/**
	* handle an uploaded file for import
	*
	* @return status
	*/
function Products_adminImportFileUpload() {
	$vars=(object)AdminVars::getAllStartsWith('productsImport');
	if (!@$vars->productsImportFileUrl['varvalue']) {
		$vars->productsImportFileUrl=array(
			'varvalue'=>'ww.cache/products/import.csv'
		);
	}
	$fname=USERBASE.$vars->productsImportFileUrl['varvalue'];
	if (strpos($fname, '..')!==false) {
		return array('message'=>'invalid file url');
	}
	@mkdir(dirname($fname), 0777, true);
	$from=$_FILES['Filedata']['tmp_name'];
	move_uploaded_file($from, $fname);
	return array('ok'=>1);
}

// }
// { Products_adminImportImages

/**
	* import images into products
	*
	* @return status
	*/
function Products_adminImportImages() {
	$directory=$_REQUEST['directory'];
	$field=$_REQUEST['field'];
	if (strpos($directory, '..')!==false) {
		return array(
			'error'=>'no hacking please'
		);
	}
	$directory=USERBASE.'/'.$directory;
	if (!file_exists($directory) || !is_dir($directory)) {
		return array('error'=>'directory does not exist');
	}
	if ($field{0}=='_') { // {
		$field=preg_replace('/^./', '', $field);
		if (!in_array($field, array('stock_number', 'name', 'ean', 'id'))) {
			return array('error'=>'no hacking please');
		}
		$files=new DirectoryIterator($directory);
		$moved=0;
		$failedmove=0;
		$missingproduct=0;
		foreach ($files as $file) {
			if ($file->isDot()) {
				continue;
			}
			try {
				$ext=strtolower(preg_replace('/.*\./', '', $file->getFilename()));
				if (in_array($ext, array('jpg', 'jpeg', 'png', 'jpe'))) {
					$name=preg_replace('/\.[^\.]*$/', '', $file->getFilename());
					$r=dbRow(
						'select id,images_directory from products where '
						.$field.'="'.addslashes($name).'"'
					);
					if (!$r) {
						$missingproduct++;
						continue;
					}
					@mkdir(
						USERBASE.'/f/'.$r['images_directory'],
						0777,
						true
					);
					$success=rename(
						$directory.'/'.$file->getFilename(),
						USERBASE.'/f/'.$r['images_directory'].'/'.$file->getFilename()
					);
					if ($success) {
						$moved++;
					}
					else {
						$failedmove++;
					}
				}
			}
			catch (Exception $e) {
			}
		}
		return array(
			'ok'=>1,
			'moved'=>$moved,
			'failed_to_move'=>$failedmove,
			'missing_product'=>$missingproduct
		);
	}
	return array('error'=>'todo');
}

// }
// { Products_adminImportDataFromAmazonGetEan13CheckNum

/**
	* get the chucksum for an EAN number
	*
	* @param string $str the string to check
	*
	* @return boolean is it valid
	*/
function Products_adminImportDataFromAmazonGetEan13CheckNum($str) {
	//first change digits to a string then explode to an array
	$digits=str_split((string)$str);
	// 1. Add the values of the digits in the even-numbered positions: 2, 4, 6, etc.
	$even_sum=$digits[1]+$digits[3]+$digits[5]+$digits[7]+$digits[9]+$digits[11];
	// 2. Multiply this result by 3.
	$even_sum_three = $even_sum * 3;
	// 3. Add the values of the digits in the odd-numbered positions: 1, 3, 5, etc.
	$odd_sum=$digits[0]+$digits[2]+$digits[4]+$digits[6]+$digits[8]+$digits[10];
	// 4. Sum the results of steps 2 and 3.
	$total_sum = $even_sum_three + $odd_sum;
	// 5. find the check character
	$next_ten = (ceil($total_sum/10))*10;
	$check_digit = $next_ten - $total_sum;
	return $str.$check_digit;
}

// }
// { Products_adminImportDataFromAmazon

/**
	* retrieve an image from amazon for a product
	*
	* @return array array of products
	*/
function Products_adminImportDataFromAmazon() {
	$pid=(int)$_REQUEST['id'];
	$ean=$_REQUEST['ean'];
	if (strlen($ean)==12) {
		$ean=Products_adminImportDataFromAmazonGetEan13CheckNum($ean);
	}
	if (strlen($ean)!=13) {
		return array('message'=>'EAN too short');
	}
	$access_key=$_REQUEST['access_key'];
	$private_key=$_REQUEST['secret_key'];
	$associate_tag=$_REQUEST['associate_key'];
	$pdata=Product::getInstance($pid);
	// { image
	if (!isset($pdata->images_directory) 
		|| !$pdata->images_directory
		|| $pdata->images_directory=='/'
		|| !is_dir(USERBASE.'/f/'.$pdata->images_directory)
	) {
		if (!is_dir(USERBASE.'/f/products/product-images')) {
			mkdir(USERBASE.'/f/products/product-images', 0777, true);
		}
		$pdata->images_directory='/products/product-images/'
			.(int)($pid/1000).'/'.$pid;
		mkdir(USERBASE.'/f'.$pdata->images_directory, 0755, true);
		Product::getInstance($pid)
			->set('images_directory', $pdata->images_directory);
	}
	$image_exists=0;
	$dir=new DirectoryIterator(USERBASE.'/f'.$pdata->images_directory);
	foreach ($dir as $f) {
		if ($f->isDot()) {
			continue;
		}
		$image_exists++;
	}
	// }
	if ($image_exists) {
		return array('message'=>'already_exists');
	}
	$obj=new AmazonProductAPI($access_key, $private_key, $associate_tag);
	try{
		$result=$obj->getItemByEan($ean, '');
		if (!@$result->Items->Item) {
			return array('message'=>'not found');
		}
		// { description
		$description=(array)$result->Items->Item->EditorialReviews
						->EditorialReview->Content;
		$description=$description[0];
		$do_description=1;
		if ($description) {
			$meta=json_decode(
				dbOne(
					'select data_fields from products where id='.$pid,
					'data_fields'
				),
				true
			);
			foreach ($meta as $k=>$v) {
				if (!isset($v['n'])) {
					unset($meta[$k]);
					continue;
				}
				if ($v['n']=='description') {
					if ($v['v']) {
						$do_description=0;
					}
					else {
						unset($meta[$k]);
					}
				}
			}
			if ($do_description) {
				$meta[]=array(
					'n'=>'description',
					'v'=>$description
				);
			}
			Product::getInstance($pid)->set('data_fields', json_encode($meta));
		}
		// }
		// { image
		$img=(array)$result->Items->Item->LargeImage->URL;
		$img=$img[0];
		if (!$image_exists) {
			copy($img, USERBASE.'/f/'.$pdata->images_directory.'/default.jpg');
		}
		// }
		return array('message'=>'found and imported');
	}
	catch(Exception $e) {
		return array('message'=>'error... '.$e->getMessage());
	}
}

// }
// { Products_adminGetProductsWithEan

/**
	* get a list of all products that have an EAN
	*
	* @return array array of products
	*/
function Products_adminGetProductsWithEan() {
	return dbAll('select id,ean from products where ean');
}

// }
// { Products_adminPageDelete

/**
	* delete a product's page
	*
	* @return array status
	*/
function Products_adminPageDelete() {
	$pid=(int)$_REQUEST['pid'];
	$pageID=dbOne(
		'select page_id from page_vars where name= "products_product_to_show" '
		.'and value='.$pid, 'page_id', 'page_vars'
	);
	dbQuery('delete from pages where id='.$pageID);
	dbQuery('delete from page_vars where page_id='.$pageID);
	Core_cacheClear('pages,page_vars');
	return array('ok'=>1);
}

// }
// { Products_adminProductDatafieldsGet

/**
	* get details about the data fields a product has
	*
	* @return array data fields
	*/
function Products_adminProductDatafieldsGet() {
	$typeID = $_REQUEST['type'];
	$productID = $_REQUEST['product'];
	if (!is_numeric($typeID)||!is_numeric($productID)) {
		Core_quit('Invalid arguments');
	}
	if (!dbOne('select id from products_types where id = '.$typeID, 'id')) {
		return array('status'=>0, 'message'=>'Could not find this type');
	}
	$data = array();
	$typeData = dbRow(
		'select data_fields, is_for_sale from products_types '
		.'where id = '.$typeID
	);
	$typeFields = json_decode($typeData['data_fields']);
	$data['type'] = $typeFields;
	$data['isForSale'] = $typeData['is_for_sale'];
	if ($productID != 0) {
		$product 
			= dbRow(
				'select data_fields, product_type_id 
				from products where id = '.$productID
			);
		$productFields = json_decode($product['data_fields']);
		$oldType 
			= dbOne(
				'select data_fields 
				from products_types 
				where id = '.$product['product_type_id'],
				'data_fields'
			);
		$oldType = json_decode($oldType);
		$data['product'] = $productFields;
		$data['oldType'] = $oldType;
	}
	return $data;
}

// }
// { Products_adminProductDelete

/**
	* delete a product
	*
	* @return array status
	*/
function Products_adminProductDelete() {
	$pid=(int)$_REQUEST['id'];
	dbQuery('delete from products where id='.$pid);
	ProductsCategoriesProducts::deleteByProductId($pid);
	dbQuery('delete from products_relations where from_id='.$pid.' or to_id='.$pid);
	dbQuery('delete from products_reviews where product_id='.$pid);
	Core_cacheClear(
		'products,products_relations,products_reviews'
	);
	return array('ok'=>1);
}

// }
// { Products_adminProductsDisable

/**
	* disable a number of product
	*
	* @return array status
	*/
function Products_adminProductsDisable() {
	$ids_to_check=explode(',', $_REQUEST['ids']);
	if (!count($ids_to_check)) {
		return array('error'=>'no ids');
	}
	$ids=array();
	foreach ($ids_to_check as $id) {
		$ids[]=(int)$id;
	}
	foreach ($ids as $id) {
		Product::getInstance($id, false, true)->set('enabled', 0);
	}
	return array('ok'=>1);
}

// }
// { Products_adminProductsDelete

/**
	* delete a number of product
	*
	* @return array status
	*/
function Products_adminProductsDelete() {
	$ids_to_check=$_REQUEST['ids'];
	if (!count($ids_to_check)) {
		return array('error'=>'no ids');
	}
	$ids=array();
	foreach ($ids_to_check as $id) {
		$ids[]=(int)$id;
	}
	dbQuery('delete from products where id in ('.join(', ', $ids).')');
	ProductsCategoriesProducts::deleteByProductId($ids);
	dbQuery(
		'delete from products_relations where from_id in ('.join(', ', $ids).')'
		.' or to_id in ('.join(', ', $ids).')'
	);
	dbQuery(
		'delete from products_reviews where product_id in ('.join(', ', $ids).')'
	);
	Products_categoriesRecount($ids);
	Core_cacheClear('products_reviews,products_relations,products');
	return array('ok'=>1);
}

// }
// { Products_adminProductsEnable

/**
	* enable a number of product
	*
	* @return array status
	*/
function Products_adminProductsEnable() {
	$ids_to_check=explode(',', $_REQUEST['ids']);
	if (!count($ids_to_check)) {
		return array('error'=>'no ids');
	}
	$ids=array();
	foreach ($ids_to_check as $id) {
		$ids[]=(int)$id;
	}
	foreach ($ids as $id) {
		Product::getInstance($id, false, true)->set('enabled', 1);
	}
	return array('ok'=>1);
}

// }
// { Products_adminProductEditVal

/**
	* edit a single value of a product
	*
	* @return array status
	*/
function Products_adminProductEditVal() {
	$id=(int)$_REQUEST['id'];
	$name=strtolower($_REQUEST['name']);
	$value=$_REQUEST['val'];
	if ($name=='id') {
		return array('error'=>'field not allowed');
	}
	Product::getInstance($id, false, true)->set($name, $value);
	if ($name=='enabled') {
		if ($value=='0') {
			dbQuery(
				'update products set activates_on=now() where id='.$id
				.' and activates_on>now()'
			);
			dbQuery(
				'update products set expires_on=now() where id='.$id
				.' and expires_on>now()'
			);
		}
		else {
			dbQuery(
				'update products set expires_on=null where id='.$id.' and expires_on<now()'
			);
		}
	}
	Core_cacheClear();
	return array('ok'=>1);
}

// }
// { Products_adminProductGet

/**
	* get all details about a product or products by its ID
	*
	* @return array the product
	*/
function Products_adminProductGet() {
	$ids=array();
	if (isset($_REQUEST['ids'])) {
		$idsToCheck=$_REQUEST['ids'];
		$ids=array();
		foreach ($idsToCheck as $id) {
			$ids[]=(int)$id;
		}
	}
	else {
		$ids=array((int)$_REQUEST['id']);
	}
	$rs=dbAll('select * from products where id in ('.join(', ', $ids).')');
	$arr=array();
	foreach ($rs as $r) {
		$r['online_store_fields']=json_decode($r['online_store_fields']);
		$r['data_fields']=json_decode($r['data_fields']);
		$arr[]=$r;
	}
	return $arr;
}

// }
// { Products_adminProductsGetUpdates

/**
	* get details of all products updated since a date
	*
	* @return array the product
	*/
function Products_adminProductsGetUpdates() {
	$dateFrom=$_REQUEST['from'];
	$rs=dbAll(
		'select * from products where date_edited>"'.addslashes($dateFrom).'"'
	);
	foreach ($rs as $k=>$r) {
		$r['online_store_fields']=json_decode($r['online_store_fields']);
		$r['data_fields']=json_decode($r['data_fields']);
		$rs[$k]=$r;
	}
	return $rs;
}

// }
// { Products_adminProductsDatafieldsGet

/**
	* get a list of all data fields
	*
	* @return array data fields
	*/
function Products_adminProductsDatafieldsGet() {
	$data = array();
	$typeDatas = dbAll('select data_fields from products_types');
	foreach ($typeDatas as $typeData) {
		$fields=json_decode($typeData['data_fields'], true);
		foreach ($fields as $field) {
			$data[$field['n']]=1;
		}
	}
	ksort($data);
	return array_keys($data);
}

// }
// { Products_adminProductsListImages

/**
	* get list of all product images
	*
	* @return array list of images
	*/
function Products_adminProductsListImages() {
	$rs=dbAll('select id,images_directory from products');
	$images=array();
	foreach ($rs as $r) {
		if (!$r['images_directory']
			|| !file_exists(USERBASE.'/f/'.$r['images_directory'])
		) {
			continue;
		}
		$dir=new DirectoryIterator(USERBASE.'/f/'.$r['images_directory']);
		foreach ($dir as $file) {
			if ($file->isDot() || $file->isDir()) {
				continue;
			}
			$images[]=array(
				$r['id'],
				$r['images_directory'].'/'.$file->getFilename()
			);
		}
	}
	return $images;
}

// }
// { Products_adminProductsList

/**
	* get products in array format
	*
	* @return null
	*/
function Products_adminProductsList() {
	$ps=dbAll('select id,name from products order by name');
	$arr=array();
	foreach ($ps as $v) {
		$arr[$v['id']]=__FromJson($v['name']);
	}
	return $arr;
}

// }
// { Products_adminProductsListDT

/**
	* get a list of products in datatables format
	*
	* @return array products list
	*/
function Products_adminProductsListDT() {
	$start=(int)$_REQUEST['iDisplayStart'];
	$length=(int)$_REQUEST['iDisplayLength'];
	$search=$_REQUEST['sSearch'];
	$orderbyNum=(int)$_REQUEST['iSortCol_0'];
	$orderdesc=$_REQUEST['sSortDir_0']=='desc'?'desc':'asc';
	$GLOBALS['product_columns']=array();
	Core_trigger('extra-products-columns');
	global $product_columns;
	switch ($orderbyNum) {
		case 2:
			$orderby='name';
		break;
		case 3:
			$orderby='stock_number';
		break;
		case 6:
			$orderby='id';
		break;
		case 7:
			$orderby='enabled';
		break;
		case 8:
			$orderby='date_created';
		break;
		case 9:
			$orderby='date_edited';
		break;
		default:
			$orderby='name';
	}
	if ($orderbyNum>9 && isset($product_columns[$orderbyNum-10]['field_name'])) {
		$orderby=$product_columns[$orderbyNum-10]['field_name'];
	}
	$filters=array();
	if ($search) {
		$sArr=array();
		$sArr[]='match(data_fields,name) against ("'.addslashes($search).'" in boolean mode)';
		$filters[]='('.join(' and ', $sArr).')';
#			.' or stock_number like "%'.addslashes($search).'%")';
	}
	$filter='';
	if (count($filters)) {
		$filter='where '.join(' and ', $filters);
	}
	$sql='select id, user_id, images_directory, name, stock_number, enabled'
		.', date_created, date_edited, stockcontrol_total';
	foreach ($product_columns as $p) {
		if (isset($p['field_name'])) {
			$sql.=', '.$p['field_name'];
		}
	}
	$sql.=' from products '.$filter
		.' order by '.$orderby.' '.$orderdesc
		.' limit '.$start.','.$length;
	$rs=dbAll($sql, '', 'products');
	$result=array();
	$result['sql']=$sql;
	$result['sEcho']=intval($_GET['sEcho']);
	$result['iTotalRecords']=dbOne(
		'select count(id) as ids from products', 'ids', 'products'
	);
	$result['iTotalDisplayRecords']=dbOne(
		'select count(id) as ids from products '.$filter,
		'ids', 'products'
	);
	$arr=array();
	foreach ($rs as $r) {
		$row=array(0);
		// { has images
		$has_images=0;
		if ($r['images_directory']
			&& @is_dir(USERBASE.'/f/'.$r['images_directory'])
		) {
			$dir=new DirectoryIterator(USERBASE.'/f/'.$r['images_directory']);
			foreach ($dir as $f) {
				if ($f->isDot()) {
					continue;
				}
				if ($f->isFile()) {
					$has_images++;
				}
			}
		}
		$row[]=$has_images;
		// }
		// { name
		$row[]=__FromJson($r['name']);
		// }
		// { stock_number
		$row[]=$r['stock_number'];
		// }
		// { stock_control
		$row[]=$r['stockcontrol_total'];
		// }
		// { owner
		$user=User::getInstance($r['user_id'], false, false);
		$row[]=$r['user_id'].'|'.($user?$user->get('name'):'unknown owner');
		// }
		// { id
		$row[]=$r['id'];
		// }
		// { enabled
		$row[]=$r['enabled'];
		// }
		// { created date
		$row[]=$r['date_created'];
		// }
		// { last edit
		$row[]=$r['date_edited'];
		// }
		foreach ($product_columns as $p) {
			if (isset($p['field_name'])) {
				$row[]=$r[$p['field_name']];
			}
			else {
				$row[]='TODO';
			}
		}
		$arr[]=$row;
	}
	$result['aaData']=$arr;
	return $result;
}

// }
// { Products_adminProductsListCommonDetails

/**
	* get common details about products in array format
	*
	* @return null
	*/
function Products_adminProductsListCommonDetails() {
	$constraint='';
	if (isset($_REQUEST['date_edited']) && $_REQUEST['date_edited']!='0000-00-00') {
		$constraint=' where date_edited>"'.addslashes($_REQUEST['date_edited']).'"';
	}
	$sql='select id, name, num_of_categories, date_edited from products'
		.$constraint;
	$ps=dbAll($sql, false, 'products');
	$arr=array();
	foreach ($ps as $k=>$p) {
		$arr[]=array(
			$p['id'], $p['name'], $p['num_of_categories'], $p['date_edited']
		);
		unset($ps[$k]);
	}
	return $arr;
}

// }
// { Products_adminProductTypeVoucherTemplateSample

/**
	* retrieve an example template for a product of type voucher
	*
	* @return the sample template
	*/
function Products_adminProductTypeVoucherTemplateSample() {
	return array(
		'html'=>file_get_contents(
			dirname(__FILE__).'/templates/product-type-voucher.html'
		)
	);
}

// }
// { Products_adminTypeCopy

/**
	* copy a product type
	*
	* @return array status of the copy
	*/
function Products_adminTypeCopy() {
	if (is_numeric($_REQUEST['id'])) {
		$id=(int)$_REQUEST['id'];
		$r=dbRow('select * from products_types where id='.$id);
	}
	else {
		$n=$_REQUEST['id'];
		if (strpos($n, '..')!==false) {
			Core_quit();
		}
		$r=json_decode(
			file_get_contents(dirname(__FILE__).'/templates/'.$n.'.json'), true
		);
		$r['data_fields']=json_encode($r['data_fields']);
	}
	dbQuery(
		'insert into products_types set'
		.' name="'.addslashes($r['name'].' (copy)').'"'
		.', multiview_template="'.addslashes($r['multiview_template']).'"'
		.', singleview_template="'.addslashes($r['singleview_template']).'"'
		.', data_fields="'.addslashes($r['data_fields']).'"'
		.', is_for_sale='.((int)$r['is_for_sale'])
		.', is_voucher='.((int)$r['is_voucher'])
		.', default_category='.((int)@$r['default_category'])
		.', voucher_template="'.addslashes(@$r['voucher_template']).'"'
		.', multiview_template_header="'
			.addslashes($r['multiview_template_header']).'"'
		.', multiview_template_footer="'
			.addslashes($r['multiview_template_footer']).'"'
		.', meta="'.addslashes($r['meta']).'"'
	);
	Core_cacheClear();
	return array(
		'id'=>dbLastInsertId()
	);
}

// }
// { Products_adminTypeDelete

/**
	* delete a product type
	*
	* @return null
	*/
function Products_adminTypeDelete() {
	$id=(int)$_REQUEST['id'];
	dbQuery("delete from products_types where id=$id");
	Core_cacheClear();
	return true;
}

// }
// { Products_adminTypeEdit

/**
	* edit a product type
	*
	* @return array
	*/
function Products_adminTypeEdit() {
	$d=$_REQUEST['data'];
	$data_fields=json_encode($d['data_fields']);
	$sql='update products_types set name="'.addslashes($d['name']).'"'
		.', allowcomments="'.((int)$d['allowcomments']).'"'
		.', multiview_template="'
		.addslashes(Core_sanitiseHtmlEssential($d['multiview_template']))
		.'",singleview_template="'
		.addslashes(Core_sanitiseHtmlEssential($d['singleview_template']))
		.'",data_fields="'.addslashes($data_fields).'"'
		.', is_for_sale='.(int)$d['is_for_sale']
		.', has_userdefined_price='.(int)@$d['user_defined_price']
		.', is_voucher='.(int)$d['is_voucher']
		.', stock_control='.(int)$d['stock_control']
		.', default_category='.(int)$d['default_category']
		.', voucher_template="'
		.addslashes(Core_sanitiseHtmlEssential($d['voucher_template'])).'",'
		.'prices_based_on_usergroup="'
		.addslashes($d['prices_based_on_usergroup'])
		.'",multiview_template_header="'
		.addslashes(Core_sanitiseHtmlEssential($d['multiview_template_header']))
		.'",template_expired_notification="'
		.addslashes(Core_sanitiseHtmlEssential(@$d['template_expired_notification']))
		.'",multiview_template_footer="'
		.addslashes(Core_sanitiseHtmlEssential($d['multiview_template_footer']))
		.'" where id='.(int)$d['id'];
	dbQuery($sql);
	Core_cacheClear();
	return array('ok'=>1);
}

// }
// { Products_adminTypeUploadMissingImage

/**
	* upload a new image to mark products that have no uploaded image
	*
	* @return null
	*/
function Products_adminTypeUploadMissingImage() {
	$id=(int)$_REQUEST['id'];
	if (!file_exists(USERBASE.'/f/products/types/'.$id)) {
		mkdir(USERBASE.'/f/products/types/'.$id, 0777, true);
	}
	$imgs=new DirectoryIterator(USERBASE.'/f/products/types/'.$id);
	foreach ($imgs as $img) {
		if ($img->isDot()) {
			continue;
		}
		unlink($img->getPathname());
	}
	$from=$_FILES['Filedata']['tmp_name'];
	$to=USERBASE.'/f/products/types/'.$id.'/image-not-found.png';
	move_uploaded_file($from, $to);
	Core_cacheClear();
	echo '/a/f=getImg/w=64/h=64/products/types/'.$id.'/image-not-found.png';
	Core_quit();
}

// }
// { Products_adminTypesGetSampleImport

/**
	* download a CSV version of a product type in importable format
	*
	* @return null
	*/
function Products_adminTypesGetSampleImport() {
	$ptypeid=(int)$_REQUEST['ptypeid'];
	if ($ptypeid) {
		$ptypes=dbAll('select * from products_types where id='.$ptypeid);
	}
	else {
		$ptypes=dbAll('select * from products_types');
	}
	$are_any_for_sale=0;
	// { get list of data field names
	$names=array();
	foreach ($ptypes as $p) {
		if ($p['is_for_sale']) {
			$are_any_for_sale=1;
		}
		$dfs=json_decode($p['data_fields']);
		foreach ($dfs as $df) {
			if (!in_array($df->n, $names)) {
				$names[]=$df->n;
			}
		}
	}
	// }
	header('Content-type: text/csv; Charset=utf-8');
	header(
		'Content-Disposition: attachment; filename="product-types-'.$ptypeid.'.csv"'
	);
	// { header
	$row=array('_stocknumber', '_name', '_ean');
	if ($are_any_for_sale) {
		$row[]='_price';
		$row[]='_sale_price';
		$row[]='_bulk_price';
		$row[]='_bulk_amount';
		$row[]='_stockcontrol_total';
	}
	foreach ($names as $n) {
		$row[]=$n;
	}
	$row[]='_type';
	$row[]='_categories';
	echo Products_arrayToCSV($row);
	// }
	// { sample rows
	foreach ($ptypes as $p) {
		$row=array('stock_number', 'name', 'barcode');
		if ($are_any_for_sale) {
			$row[]='0.00';
			$row[]='0.00';
			$row[]='0.00';
			$row[]='0';
			$row[]='0';
		}
		foreach ($names as $n) {
			$row[]='';
		}
		$row[]=$p['name'];
		$row[]='';
		echo Products_arrayToCSV($row);
	}
	// }
	Core_quit();
}

// }
// { Products_adminUserGroupsGet

/**
	* get an array of user groups used in product types
	*
	* @return array
	*/
function Products_adminUserGroupsGet() {
	$gnames=array();
	$types=dbAll('select data_fields from products_types');
	foreach ($types as $type) {
		$fs=json_decode($type['data_fields']);
		foreach ($fs as $f) {
			if ($f->t!='user') {
				continue;
			}
			$names=explode("\n", $f->e);
			foreach ($names as $name) {
				if ($name=='') {
					continue;
				}
				$name=addslashes($name);
				if (!in_array($name, $gnames)) {
					$gnames[]=$name;
				}
			}
		}
	}
	return dbAll(
		'select id,name from groups where name in ("'.join('", "', $gnames).'")'
		.' order by name'
	);
}

// }
function Products_adminCategoryFullName() {
	function getParentName($id) {
		$cat=ProductCategory::getInstance($id);
		return $cat->vals['parent_id']
			?getParentName($cat->vals['parent_id']).'/'.$cat->vals['name']
			:$cat->vals['name'];
	}
	return getParentName((int)$_REQUEST['id']);
}
function Products_adminProductCategoryContainsUpdate() {
	$id=(int)$_REQUEST['id'];
	if (!$id) {
		$id=dbOne('select id from products order by id limit 1', 'id');
	}
	$rs=dbAll(
		'select * from products_categories_products where product_id='.$id
	);
	foreach ($rs as $r) {
		$cat=ProductCategory::getInstance($r['category_id']);
		if ($cat) {
			$cat->containsDel(
				$r['product_id'], $r['category_id']
			);
			$cat->containsAdd(
				$r['product_id'], $r['category_id']
			);
		}
		else {
			dbQuery(
				'delete from products_categories_products where product_id='.$id
				.' and category_id='.$r['category_id']
			);
		}
	}
	echo $id;
	$id=dbOne(
		'select id from products where id>'.$id.' order by id limit 1', 'id'
	);
	if ($id) {
		echo '<script>setTimeout(function() {document.location="./id='.$id.'";}, 1);</script>';
	}
	else {
		echo '<br/>DONE';
	}
	exit;
}
function products_adminFixOrphanedCategories() {
	$rs=dbAll('select id,name,parent_id from products_categories');
	foreach ($rs as $r) {
		if ($r['parent_id']=='0') {
			continue;
		}
		$pid=dbOne('select id from products_categories where id='.$r['parent_id'], 'id');
		if (!$pid) {
			$sql='update products_categories set parent_id=0 where id='.$r['id'];
			dbQuery($sql);
			echo $sql."<br/>";
		}
		else {
			echo 'product_category '.$r['name'].' is okay.<br/>';
		}
	}
	Core_cacheClear('products_categories');
	exit;
}
