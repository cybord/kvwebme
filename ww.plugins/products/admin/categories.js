$(function(){
	function show_attributes(ret, cid){
		function save(){
			var id=window.selected_cat, name=$('#pc_edit_name').val(), enabled=$('#pc_edit_enabled').val();
			ret.attrs.enabled=enabled;
			ret.attrs.name=name;
			$.post(
				'/a/p=products/f=adminCategoryEdit',
				{
					'id':id,
					'name':name,
					'enabled':enabled
				},
				function() {
					$('#cat_'+window.selected_cat+'>a').text(name);
				}
			);
		}
		window.selected_cat=ret.attrs.id;
		var $wrapper=$('#products-categories-attrs').empty();
		var tableCache='<table id="attrs_table" style="width:100%">'
			+'<tr><th>Name</th><td><input id="pc_edit_name" /></td></tr>'
			+'<tr><th>Enabled</th><td><select id="pc_edit_enabled"><option value="1">Yes</option><option value="0">No</option></td></tr>';
		// { icon
		tableCache+='<tr id="icon"><th>Icon</th>'
			+'<td><div id="icon-image"/><input type="file" id="uploader"/></td>'
			+'</tr>';
		// }
		// { products
		tableCache+='<tr id="products"><th>Products</th><td><input id="selectFromParentCats" type="checkbox"/>only show products that the parent category has<div id="products-wrapper">';
		tableCache+='please wait...</div></td></tr>';
		function showProducts(productsPool) {
			var products=['<select name="pc_edit_products[]" id="pc_edit_products" multiple="multiple" style="width:100%">'];
			var pNames=window.product_names;
			for (var i=0;i<pNames.length;++i) {
				var product=pNames[i];
				if (productsPool) {
					var found=false;
					for (var j=0;j<productsPool.length;++j) {
						if (productsPool[j]==product[1]) {
							found=true;
							break;
						}
					}
					if (!found) {
						continue;
					}
				}
				products.push('<option value="'+product[1]+'">'+product[0]+'</option>');
			}
			products.push('</select>');
			products.join('');
			$('#products-wrapper').empty().append(products.join(''));
			$('#pc_edit_products').chosen().change(function() {
				var $opts=$('#pc_edit_products option:selected');
				var $this=$('#pc_edit_products');
				var vals=[];
				$opts.each(function() {
					vals.push(this.value);
				});
				if (window.pcEditProductsTimeout) {
					clearTimeout(window.pcEditProductsTimeout);
				}
				window.pcEditProductsTimeout=setTimeout(function() {
					$.post(
						'/a/p=products/f=adminCategoryProductsEdit/id='+window.selected_cat,
						{ "s[]":vals}
					);
					clearTimeout(window.pcEditProductsTimeout);
					delete window.pcEditProductsTimeout;
				}, 1000);
			});
			$('#pc_edit_products').val(ret.products).trigger('liszt:updated');
		}
		if (selectFromParentCats) {
			var id=$('#cat_'+window.selected_cat).parents('li').attr('id');
			if (id===undefined) {
				setTimeout(showProducts, 1);
			}
			else {
				id=id.replace('cat_', '');
				$.post(
					'/a/p=products/f=adminCategoryGet/id='+id,
					function(ret) {
						showProducts(ret.products);
					}
				);
			}
		}
		else {
			setTimeout(showProducts, 1);
		}
		// }
		tableCache+='</table>';
		$wrapper.append(tableCache);
		Core_uploader('#uploader', {
			'serverScript': '/a/p=products/f=adminCategorySetIcon/cat_id='
				+window.selected_cat,
			'successHandler':function(file, data, response){
				$('#icon-image').html(
					'<img src="/f/products/categories/'+window.selected_cat+'/icon.png?'
					+Math.random()+'"/>'
				);
			}
		});
		$('#cat_'+ret.attrs.id+'>a').text(ret.attrs.name);
		$('#pc_edit_name')
			.change(save)
			.val(ret.attrs.name);
		$('#cat_'+ret.attrs.id+' a').removeClass('disabled');
		// { Remove the links so that they don't get added twice
		$('#create_link,#frontend_link').remove();
		// }
		if (ret.page==null) {
			$(
				'<tr id="create_link"><th>Link</th>'+
				'<td><a href="javascript:;" id="page_create_link"'+
				'onClick='+
				'"createPopup(\''+ret.attrs.name+'\', '+ret.attrs.id+', 2);"'
				+'>Create a page for this category</a></td></tr>'
			).insertAfter($('#icon'));
		}
		if (ret.page!=null) {
			$(
				'<tr id="frontend_link"><th>Link</th>'+
				'<td><a href="'+ret.page+'" target=_blank>'+
				'View this category on the frontend</a></td></tr>'
			).insertAfter('#products');
		}
		$('#pc_edit_enabled')
			.change(save)
			.val(ret.attrs.enabled);
		$('#icon-image').html(ret.hasIcon
			?'<img src="/f/products/categories/'+ret.attrs.id+'/icon.png?'
				+Math.random()+'"/>'
			:''
		);
		$('#selectFromParentCats')
			.change(function() {
				selectFromParentCats=$(this).is(':checked');
				show_attributes(ret);
			})
			.attr('checked', selectFromParentCats);
	}
	var selectFromParentCats=false;
	// { draw categories tree
	$.jstree._themes='/j/jstree/themes/';
	$('#categories-wrapper')
		.jstree({
			'plugins': ["themes", "html_data", "ui", "dnd", "contextmenu"],
			'selected':'cat_'+window.selected_cat,
			'contextmenu': {
				'items': {
					'rename':false,
					'ccp':false,
					'create' : {
						'label'	: "create sub-category", 
						'visible'	: function (NODE, TREE_OBJ) { 
							if(NODE.length != 1) return 0; 
							return TREE_OBJ.check("creatable", NODE); 
						}, 
						'action':function(node, tree){
							var id=node[0].id.replace(/.*_/,'');
							var name=prompt('what do you want to name this sub-category?');
							if (!name) {
								return;
							}
							$.post('/a/p=products/f=adminCategoryNew', {
								"parent_id":id,
								"name":name
							},function(){
								document.location=document.location;
							});
						},
						'separator_after' : true
					},
					'remove' : {
						'label'	: "delete category", 
						'visible'	: function (NODE, TREE_OBJ) { 
							if(NODE.length != 1) return 0; 
							return TREE_OBJ.check("deletable", NODE); 
						}, 
						'action':function(node, tree){
							var id=node[0].id.replace(/.*_/,'');
							if (id==1) {
								$('<em>Cannot delete default category.</em>').dialog({
									'modal':true
								});
								return;
							}
							if (!confirm("Are you sure you want to delete this category?")) {
								return;
							}
							$.post(
								'/a/p=products/f=adminCategoryDelete/id='+id,
								function(){
									document.location="/ww.admin/plugin.php?_plugin=products&"
										+"_page=categories";
								}
							);
						},
						'separator_after' : true
					},
					'copy' : false
				}
			},
			'types':{
				'default':{
					icon:{
						image: false
					}
				}
			},
			'callback':{
				"onmove":function(node){
					var p=$.jstree._focused().parent(node);
					$.post(
						'/a/p=products/f=adminCategoryMove/id='+node.id.replace(/.*_/,'')
						+'&parent_id='+(p==-1?0:p[0].id.replace(/.*_/,'')),
						show_attributes
					);
					$.post('/a/p=products/f=adminCategoryMove/id='
						+node.id.replace(/.*_/,'')+'&parent_id='
						+(p==-1?0:p[0].id.replace(/.*_/,'')), show_attributes
					);
				}
			},
			'dnd': {
				'drag_target': false,
				'drop_target': false
			}
		})
		.bind('move_node.jstree',function(e, ref){
			var data=ref.args[0];
			var node=data.o[0];
			setTimeout(function(){
				var p=node.parentNode.parentNode;
				var nodes=$(p).find('>ul>li');
				if(p.tagName=='DIV')p=-1;
				var new_order=[];
				for (var i=0;i<nodes.length;++i) {
					new_order.push(nodes[i].id.replace(/.*_/, ''));
				}
				$.post('/a/p=products/f=adminCategoryMove/id='
					+node.id.replace(/.*_/,'')+'/parent_id='
					+(p==-1?0:p.id.replace(/.*_/,''))+'/order='+new_order);
			},1);
		});
	var div=$('<div style="clear:both;padding-top:20px;" />');
	$('<button>add main category</button>')
		.click(function(){
			var name=prompt('what do you want to name this category?');
			if(!name)return;
			$.post('/a/p=products/f=adminCategoryNew', {
				"parent_id":0,
				"name":name
			},function(){
				document.location=document.location;
			});
		})
		.appendTo(div);
	div.insertAfter('#categories-wrapper');
	$('#categories-wrapper li>a').live('click', function(){
		$.post('/a/p=products/f=adminCategoryGet/id='
			+$(this).closest('li')[0].id.replace(/.*_/,''), show_attributes
		);
	});
	// }
	$.post('/a/p=products/f=adminCategoryGet/id='+window.selected_cat,
		show_attributes
	);
});
