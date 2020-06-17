<?php
defined('_JEXEC') or die('Restricted access');
if(substr(JVERSION,0,1)==3){
    JHtml::_('bootstrap.tooltip');
}
else{
    JHTML::_('behavior.tooltip');
}
$params = JComponentHelper :: getParams("com_excel2js");

JToolBarHelper::save('save_config',JText::_('SAVE') );
JToolBarHelper::save('save_profile',JText::_('SAVE_AS_PROFILE'));

JToolBarHelper::addNew('price',JText::_('SPECIAL_PRICE'));
JToolBarHelper::addNew('empty_field',JText::_('EMPTY_COLUMN'));
JToolBarHelper::divider();
//JToolBarHelper::custom('test_timeout','options','',JText::_('TIMEOUT_TEST'),false);
JToolBarHelper :: preferences('com_excel2js');
$total_fields=count(@$this->active) + count(@$this->inactive);
$nombers=range('A','Z');
$nombers2=range('A','Z');
foreach($nombers2 as $n2){
    foreach($nombers2 as $letter)
    	$nombers[]=$n2.$letter;
}
$trash_link="<a href='javascript:void(0);' title='".JText::_('DELETE')."' class='ui-icon ui-icon-trash'>".JText::_('DELETE')."</a>";
$lang =JFactory::getLanguage();

$price_template[]= JHTML::_( 'select.option', '1', JText::_('NUMERIC_WITH_NAME') );
$price_template[]= JHTML::_( 'select.option', '4', JText::_('NUMERIC_WITHOUT_NAME') );
$price_template[]= JHTML::_( 'select.option', '2', JText::_('SPECIAL_SYMBOL_BEFORE_THE_NAME') );
$price_template[]= JHTML::_( 'select.option', '3', JText::_('SPECIAL_SYMBOL_AFTER_THE_NAME') );
$price_template[]= JHTML::_( 'select.option', '5', JText::_('CATEGORY_SEARCH_BY_KEYWORDS'));
$price_template[]= JHTML::_( 'select.option', '6', JText::_('CATEGORY_ID_FOR_EACH_PRODUCT') );
$price_template[]= JHTML::_( 'select.option', '7', JText::_('CATEGORY_NAME_FOR_EACH_PRODUCT') );
$price_template[]= JHTML::_( 'select.option', '8', JText::_('GROUPS_IN_EXCEL') );

$alias_template[]= JHTML::_( 'select.option', '1', JText::_('ALIAS_PRODUCT_NAME') );
$alias_template[]= JHTML::_( 'select.option', '2', JText::_('ALIAS_ID_PRODUCT_NAME') );
$alias_template[]= JHTML::_( 'select.option', '3', JText::_('ALIAS_PRODUCT_NAME_ID') );
$alias_template[]= JHTML::_( 'select.option', '4', JText::_('ALIAS_SKU_PRODUCT_NAME') );
$alias_template[]= JHTML::_( 'select.option', '5', JText::_('ALIAS_PRODUCT_NAME_SKU'));
$alias_template[]= JHTML::_( 'select.option', '6', JText::_('ALIAS_SKU_ID_PRODUCT_NAME') );
$alias_template[]= JHTML::_( 'select.option', '7', JText::_('ALIAS_ID_SKU_PRODUCT_NAME') );
$alias_template[]= JHTML::_( 'select.option', '8', JText::_('ALIAS_PRODUCT_NAME_SKU_ID') );
$alias_template[]= JHTML::_( 'select.option', '9', JText::_('ALIAS_PRODUCT_NAME_ID_SKU') );
$alias_template[]= JHTML::_( 'select.option', '10', JText::_('ALIAS_SKU') );
$alias_template[]= JHTML::_( 'select.option', '11', JText::_('ALIAS_ID') );

$publish_new[]= JHTML::_( 'select.option', '0', "Не опубликован" );
$publish_new[]= JHTML::_( 'select.option', '1', "Опубликован" );

$publish_old[]= JHTML::_( 'select.option', '-1', "Не изменять" );
$publish_old[]= JHTML::_( 'select.option', '0', "Не опубликован" );
$publish_old[]= JHTML::_( 'select.option', '1', "Опубликован" );

$prices_update[]= JHTML::_( 'select.option', '0', "Обновлять всегда" );
$prices_update[]= JHTML::_( 'select.option', '1', "Обновлять, если цена в прайсе выше" );
$prices_update[]= JHTML::_( 'select.option', '2', "Обновлять, если цена в прайсе ниже" );

$doc = JFactory::getDocument();
$doc->addScript(JURI::base()."components/com_excel2js/js/chosen.jquery.min.js");
$doc->addStyleSheet(JURI::base()."components/com_excel2js/assets/chosen.css");
$doc->addScriptDeclaration ( 'jQuery(document).ready(function(){jQuery(".chosen-select").chosen();});' );
$list = JHTML::_('select.genericlist',$this->categories,'unpublish_categories[]','data-placeholder="Выберите категории" class="chosen-select" multiple style="float: none;width: 220px;" size="1" ','category_id','category_name',@$this->config->unpublish_categories?$this->config->unpublish_categories:0);
$reset_list = JHTML::_('select.genericlist',$this->categories,'reset_categories[]','data-placeholder="Выберите категории" class="chosen-select" multiple style="float: none;width: 220px;" size="1" ','category_id','category_name',@$this->config->reset_categories?$this->config->reset_categories:0);



$notify_show=$params->get('notify_show','fold');
$notify_hide=$params->get('notify_hide','explode');

if($notify_show=='none')$notify_show='';
if($notify_hide=='none')$notify_hide='';

?>
<script type="text/javascript">

     //Переопределяем функцию нажатия на кнопки панели
     if(typeof Joomla != 'undefined'){
         Joomla.submitbutton = function(pressbutton) {
				if(pressbutton == 'price')
	                extend_field(pressbutton);
				else
					eval(pressbutton+'()');
		 }
     }
     else{
          window['submitbutton'] = function (pressbutton) {
				if(pressbutton == 'price')
	                extend_field(pressbutton);
				else
					eval(pressbutton+'()');
		 }
     }



	 function save_config(options){
            if(!options){
               var options = {
			  	target:"#response",
				data:{task:'save_config'},
			    success: response,
			    timeout: 3000
		    	};
            }

			jQuery("#fields_list").val(jQuery("#active").sortable('toArray'));
			jQuery("#new_profile_name").val();
            jQuery('#adminform').ajaxSubmit(options);

		    return false;
     }

     function save_profile(){
            jQuery.ajax({
                     url:'index.php?option=com_excel2js&view=config',
					 type:'GET',
					 data:{task:'profile_list',rand:Math.random()},
					 dataType: 'html',
					 success:function(data){
                         jQuery("#response").html(data);
            			 jQuery("#response_div").css({height:'180px',width:'340px'}).show('<?php echo $notify_show ?>',{},200);
					 }
           });
     }

        //Отображаем ответ сервера
		function response(text){
			if(text)
				jQuery("#response").html(text);
            //jQuery("#response_div").css({height:'50px',width:'400px'}).show('<?php echo $notify_show ?>',{},500).delay(3000).hide('<?php echo $notify_hide ?>',{},500);
            jQuery("#response_div").css({height:'50px',width:'400px'}).show('<?php echo $notify_show ?>');
			setTimeout(function(){
                 jQuery("#response_div").hide('<?php echo $notify_hide ?>');
			},3000);

		}

		//Добавить поле
		function add_field(id,title,type){

            jQuery("#active").append("<li class='"+type+"' id='"+id+"'>"+title+" <?php echo $trash_link ?></li>");
		}

		//Добавить пустой столбец
		function empty_field(){
			jQuery.ajax({
                     url:'index.php?option=com_excel2js&view=config&task=empty_field&rand='+Math.random(),
					 type:'GET',
					 dataType: 'text',
					 error: function(){response("<?php echo JText::_('DATA_NOT_SAVED') ?>")},
					 success:function(data){
                         add_field(data,"<?php echo JText::_('EMPTY_COLUMN') ?>","empty");
						 response("<?php echo JText::_('EMPTY_COLUMN') ?> <?php echo JText::_('ADDED') ?>");
					 }
            });
		}


		//Добавить доп. поле
		function extend_field(field_type){
			jQuery.ajax({
                     url:'index.php?option=com_excel2js&view=config',
					 type:'GET',
					 data:{task:field_type,rand:Math.random()},
					 dataType: 'html',
					 success:function(data){
                         jQuery("#response").html(data);
            			 jQuery("#response_div").css({height:'180px',width:'320px'}).show('<?php echo $notify_show ?>',{},200);
					 }
           });
		}
      function new_profile(){
          if(jQuery("#profile_id :selected").val()){
          	 if(jQuery("#create_new_profile").is(':visible'))
              jQuery("#create_new_profile").hide('<?php echo $notify_hide ?>');
		  }
		  else{
              jQuery("#create_new_profile").show('<?php echo $notify_show ?>');
		  }
      }

      //Отправляем данные для добавления нового столбца
		function add_field_form(){
			var options = {
				dataType: 'json',
			    success: function(obj){
			        if(obj.title=='error'){
                        jQuery("#response").html("<span style='color: #CC0000'>Возникла ошибка при создании поля</span>").show("<?php echo $notify_show ?>",500);
					    jQuery("#response_div").delay(3000).hide('<?php echo $notify_hide ?>',{},500);
			        }
                    else{
                        add_field(obj.id,obj.title,obj.type);
    					jQuery("#response").html("<?php echo JText::_('COLUMN_CREATED') ?> - "+obj.title).show("<?php echo $notify_show ?>",500);
    					jQuery("#response_div").delay(3000).hide('<?php echo $notify_hide ?>',{},500);
                    }

            	}

		    };
			//jQuery("#response").hide('scale',{},300);
			//jQuery("#response_div").animate({height:'60px',width:'400px'},500);
            jQuery('#ajax_form').ajaxSubmit(options);
			return false;
		}

        //Отправляем данные для о профиле
		function create_profile_form(){
			var options = {
				dataType: 'html',
				data:{task:'create_profile'},
			    success: function(data){
					jQuery("#response").html(data).show("<?php echo $notify_show ?>",500);
					jQuery("#response_div").delay(3000).hide('<?php echo $notify_hide ?>',{},500).delay(3000);
            	}

		    };
            var profile_name=jQuery('#ajax_form :selected').text();
            if(profile_name == "<?php echo JText::_('ADD_NEW') ?>"){
               profile_name = jQuery("#profile").val();
			   jQuery("#new_profile_name").val(profile_name);
			   jQuery("#profile_id2").val(0);
            }
			else{
			   //alert(jQuery('#ajax_form :selected').val());
               jQuery("#profile_id2").val(jQuery('#ajax_form :selected').val());
			   jQuery("#new_profile_name").val();
			}
			if(!profile_name){
				alert("<?php echo JText::_('INPUT_THE_NAME_OF_THE_NEW_PROFILE') ?>");
                return false;
			}

			jQuery("#response").hide('<?php echo $notify_hide ?>',{},300);
			jQuery("#response_div").animate({height:'60px',width:'400px'},500);
            //jQuery('#ajax_form').ajaxSubmit(options);
			save_config(options);
			jQuery("#current_profile").html(profile_name).css({background:'yellow'});

			return false;
		}

    function price_template_change(){
             var val = jQuery("#price_template :selected").val();
			 if(val!=2 && val!=3){
				if(jQuery("#simbol_li").is(':visible'))
	            	jQuery("#simbol_li").hide('<?php echo $notify_hide ?>');
			 }
			 else if(!jQuery("#simbol_li").is(':visible'))
	            jQuery("#simbol_li").show('<?php echo $notify_show ?>');

			 if(val!=5){
                 if(jQuery("#extra_category").is(':visible'))
                	jQuery("#extra_category").hide('<?php echo $notify_hide ?>');
			 }
	         else if(!jQuery("#extra_category").is(':visible'))
	            jQuery("#extra_category").show('<?php echo $notify_show ?>');

             if(val==7){
                 if(!jQuery(".delimiters_li").is(':visible')){
                     jQuery(".delimiters_li").show('drop');
                 }
             }
             else{
                 if(jQuery(".delimiters_li").is(':visible')){
                     jQuery(".delimiters_li").hide('drop');
                 }
             }
    }

    function auto_unpublish_change(){
        var val = jQuery("input[name=unpublish]:checked").val();
        if(val==1){
            jQuery("#unpublish_cats").show('<?php echo $notify_show ?>');
        }
        else{
            jQuery("#unpublish_cats").hide('<?php echo $notify_hide ?>');
        }
    }

    function auto_reset_change(){
        var val = jQuery("input[name=reset_stock]:checked").val();
        if(val==1){
            jQuery("#reset_cats").show('<?php echo $notify_show ?>');
        }
        else{
            jQuery("#reset_cats").hide('<?php echo $notify_hide ?>');
        }
    }

	jQuery(function() {
		jQuery( "#active" ).sortable({
			connectWith: "ul",
			/*revert: true, */
			placeholder: 'ui-state-highlight',
			forcePlaceholderSize: true,
			containment: '#order_table'
		});

		jQuery( "#inactive" ).sortable({
			connectWith: "ul",
			/*revert: true,*/
			placeholder: 'ui-state-highlight',
			forcePlaceholderSize: true,
			containment: '#order_table'


		});

		jQuery( "#inactive, #active" ).disableSelection();

        //Сохраняем настройки


        //удалить поле
        jQuery(".ui-icon-trash").live("click",function(){
             var parent = jQuery(this).parent('li');
             parent.hide('<?php echo $notify_hide ?>',{},1000);
			 jQuery.ajax({
                     url:'index.php?option=com_excel2js&view=config&task=delete_field',
					 type:'GET',
					 data:{id : parent.attr('id'),rand:Math.random()},
					 dataType: 'text',
					 error: function(){response("<?php echo JText::_('ERROR_COLUMN_DELETE') ?>")},
					 success:function(data){
						 response("<?php echo JText::_('COLUMN_DELETED') ?>");
					 }
             });

        });





		//Кнопка закрытия уведомления
		jQuery("#close").click(function(){
             jQuery("#response_div").hide('<?php echo $notify_hide ?>',{},1000);
		});

		jQuery("#price_template").change(function(){
             price_template_change();
		});
        jQuery("input[name=unpublish]").change(function(){
             auto_unpublish_change();
		});
        jQuery("input[name=reset_stock]").change(function(){
             auto_reset_change();
		});
		price_template_change();
        auto_unpublish_change();
        auto_reset_change();
	});

	</script>

<style type="text/css">
.controls > .radio:first-child, .controls > .checkbox:first-child {
    padding-top: 0!important;
    padding-left: 13px;
}
input.ordering_input{
  width:auto!important;
}
#order_table{
  height: <?php echo $total_fields*35+90 ?>px
}
#legend-fields li{
  padding: 2px
}
input[name="start"],input[name="end"]{
  width: 41px!important;
}
#unpublish_categories_chosen{
  float: left;
}

</style>

<div id="response_div" style="position:fixed; z-index: 50; top:200px; left:40%; padding: .7em; display: none" class="ui-state-highlight ui-corner-all">
				<span id="close" title="<?php echo JText::_('CLOSE') ?>" class="ui-icon ui-icon-closethick" style="position: absolute; top: 0;right: 0; cursor: pointer"></span>
				<form action="index.php?option=com_excel2js&view=config" id="ajax_form" method="POST">
					<p style="margin-bottom: 5px; text-align: center; font-size: 14px">
						<span style="float: left; margin-right: .3em;" class="ui-icon ui-icon-info"></span>

						<span id="response"></span>
					</p>
				</form>
</div>
<h3><?php echo JText::_('CURRENT_PROFILE') ?>: <span style="font-weight: bold; color: #006633" id="current_profile"><?php echo $this->config->profile_name ?></span>
 &nbsp;|&nbsp;
 <form style="display: inline" action="index.php?option=com_excel2js&view=config" method="POST" onsubmit="return confirm('<?php echo JText::_('ARE_YOU_SURE_YOU_WANT_TO_DELETE_THE_CURRENT_PROFILE') ?>');">
 <input type="submit" value="<?php echo JText::_('DELETE') ?>" />
 <input type="hidden" name="task" value="delete_profile" />
  </form>
  &nbsp;|&nbsp;
  <form style="display: inline" action="index.php?option=com_excel2js&view=config" method="POST">
 <input type="submit" value="<?php echo JText::_('EXPORT_PROFILE') ?>" />
 <input type="hidden" name="task" value="export_profile" />
  </form>
  &nbsp;|&nbsp;

 <form style="display: inline" action="index.php?option=com_excel2js&view=config" enctype="multipart/form-data" method="POST">
	 <input name="profile_file" value="" type="file" />
	 <input type="submit" value="<?php echo JText::_('IMPORT_PROFILE') ?>" />
	 <input type="hidden" name="task" value="import_profile" />
 </form>



  </h3>
<form action="index.php?option=com_excel2js&view=config" method="POST">
	<h3><?php echo JText::_('CHANGE_TO') ?>: <?php echo JHTML::_('select.genericlist',$this->profiles,  'profile_id_value', 'size="1" onchange="this.form.submit()"','id','profile',$this->config->profile_id) ?></h3>

	<input type="hidden" name="task" value="change_profile" />
</form>
<div style="width: 1250px; float: left; background-color: #F4F4F4">
    <div style="width: 700px; float: left">
    	<fieldset>
    	    <legend><?php echo JText::_('SETTING_THE_PRICE_COLUMN') ?></legend>
    		<table id="order_table">
    			<tr>
    				<th><?php echo JText::_('COLUMN_NOMBER') ?></th>
    				<th><?php echo JText::_('ACTIVE_COLUMNS') ?></th>
    				<th><?php echo JText::_('INACTIVE_COLUMNS') ?></th>
    			</tr>
    			<tr>
    				<td>
    					<ul style="height: <?php echo $total_fields*32.5+18 ?>px" id="nombers">
    					    <?php
    		                    for($i=0;$i<$total_fields;$i++)
    								echo "<li class=\"ui-state-highlight\">{$nombers[$i]} (".($i+1).")</li>";
    						?>
    					</ul>
    				</td>
    				<td>
    		            <ul style="height: <?php echo $total_fields*32.5+18 ?>px" id="active">
    					    <?php
    		                   if($this->active)
    						   	  foreach($this->active as $f){
    						   	  	 $trash = in_array($f->type,array('empty','price'))?$trash_link :'';
                                     echo "<li class=\"{$f->type}\" id=\"{$f->id}\">".JText::_($f->title)." $trash</li>";
    						   	  }

    						?>
    					</ul>

    				</td>
    				<td>
    					<ul style="height: <?php echo $total_fields*32.5+18 ?>px" id="inactive">
    						<?php
    		                   if($this->inactive)
    						   	  foreach($this->inactive as $f){
    						   	  	 $trash = in_array($f->type,array('empty','price'))?$trash_link :'';
                                     echo "<li class=\"{$f->type}\" id=\"{$f->id}\">".JText::_($f->title)." $trash</li>";
    						   	  }
    						?>

    		           </ul>
    				</td>
    			</tr>
    			<tr>
                     <td><h3><?php echo JText::_('LEGEND') ?></h3></td>
    				 <td colspan="2">
                        <ul id="legend-fields">
                            <li class="default"><?php echo JText::_('STANDART_COLUMN') ?></li>
                            <li class="price"><?php echo JText::_('SPECIAL_PRICE') ?></li>
                            <li class="empty"><?php echo JText::_('DO_NOT_IMPORT_COLUMN') ?></li> <br>
    						<li class="independ">Независимый атрибут</li>
                            <li class="depend">Зависимые атрибуты</li>
                            <li class="extra">Характеристики</li>
                            <li class="free">Свободные атрибуты</li>
    					</ul>
    				 </td>
    			</tr>
    		</table>
    	</fieldset>
    </div>

    <div style="width: 520px; float: left; margin-left:15px ">
    	<fieldset class="panelform">
    	    <legend><?php echo JText::_('IMPORT_EXPORT_SETTINGS') ?></legend>
             <form id="adminform" name="adminform" action="index.php" method="post">
    			<input type="hidden" name="option" value="com_excel2js" />

    			<input type="hidden" name="view" value="config" />
             <ul  class="adminformlist">
                <fieldset class="config_groups">
                    <legend>Основные настройки</legend>
                    <li>
                        <label>
                        <?php if(substr(JVERSION,0,1)==3): ?>
                        <span title="<?php echo JText::_('CATEGORY_MARKUP_METHOD_HINT') ?>" data-placement="bottom" class="hasTooltip"><?php echo JText::_('CATEGORY_MARKUP_METHOD') ?></span>
                        <?php else: ?>
                            <?php echo JHTML::tooltip(JText::_('CATEGORY_MARKUP_METHOD_HINT'), JText::_('CATEGORY_MARKUP_METHOD'),'',JText::_('CATEGORY_MARKUP_METHOD')); ?>
                        <?php endif; ?>
                        </label>
                        <?php echo  JHTML::_('select.genericlist',$price_template,  'price_template', 'size="1"','value','text', @$this->config->price_template) ?>

                	</li>
                    <li class="delimiters_li">
                        <label><?php echo  JHTML::tooltip(JText::_('LEVEL_DELIMITER_HINT'), JText::_('LEVEL_DELIMITER'),'',JText::_('LEVEL_DELIMITER')); ?></label>
                        <input class="ordering_input" type="text" name="level_delimiter"  size="32" maxlength="250" value="<?php echo  @$this->config->level_delimiter?$this->config->level_delimiter:'\\'?>" />
                	</li>
                    <li class="delimiters_li">
                        <label><?php echo  JHTML::tooltip(JText::_('CATEGORY_DELIMITER_HINT'), JText::_('CATEGORY_DELIMITER'),'',JText::_('CATEGORY_DELIMITER')); ?></label>
                        <input class="ordering_input" type="text" name="category_delimiter"  size="32" maxlength="250" value="<?php echo  @$this->config->category_delimiter?$this->config->category_delimiter:'|'?>" />
                	</li>
        			<li id="simbol_li">
                        <label><?php echo  JHTML::tooltip(JText::_('SPECIAL_SYMBOL_HINT'), JText::_('SPECIAL_SYMBOL'),'',JText::_('SPECIAL_SYMBOL')); ?></label>
                        <input class="ordering_input" type="text" name="simbol"  size="32" maxlength="250" value="<?php echo  @$this->config->simbol?$this->config->simbol:'#'?>" />
                	</li>
        			<li id="extra_category">
                        <label><?php echo  JHTML::tooltip(JText::_('CATEGORY_FOR_OTHER_PRODUCTS_HINT'), JText::_('CATEGORY_FOR_OTHER_PRODUCTS'),'',JText::_('CATEGORY_FOR_OTHER_PRODUCTS')); ?></label>
                        <input class="ordering_input" type="text" name="extra_category"  size="32" maxlength="250" value="<?php echo  @$this->config->extra_category?$this->config->extra_category:JText::_('OTHER_PRODUCTS')?>" />
                	</li>

        			<li>
                        <label><?php echo JHTML::tooltip(JText::_('LANGUAGE_HINT'), JText::_('LANGUAGE'),'',JText::_('LANGUAGE')); ?></label>
                        <?php echo JHTML::_('select.genericlist',$this->languages,  'language', 'size="1"','language','name', @$this->config->language) ?>

                	</li>
                    <li>
                        <label><?php echo JHTML::tooltip(JText::_('DEFAULT_UNITS_HINT'), JText::_('DEFAULT_UNITS'),'',JText::_('DEFAULT_UNITS')); ?></label>
                        <?php echo JHTML::_('select.genericlist',$this->units,  'units', 'size="1"','id','name', @$this->config->units) ?>

                	</li>
                    <li>
                        <label><?php echo JHTML::tooltip(JText::_('CURRENCY_HINT'), JText::_('CURRENCY'),'',JText::_('CURRENCY')); ?></label>
                        <?php echo JHTML::_('select.genericlist',$this->currencies,  'currency', 'size="1"','currency_id','currency_name', @$this->config->currency?$this->config->currency:150) ?>

                	</li>
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('CURRENCY_RATE_HINT'), JText::_('CURRENCY_RATE'),'',JText::_('CURRENCY_RATE')); ?></label>
                        <input class="ordering_input" type="text" name="currency_rate"  size="32" maxlength="250" value="<?php echo  @$this->config->currency_rate?$this->config->currency_rate:1?>" />
                	</li>
                    <li>
                        <label><?php echo JHTML::tooltip(JText::_('ACCESS_HINT'), JText::_('ACCESS'),'',JText::_('ACCESS')); ?></label>
                        <?php echo JHTML::_('select.genericlist',$this->groups,  'access', 'size="1"','id','title', isset($this->config->access)?$this->config->access:1) ?>

                	</li>
        			<li>
                        <label><?php echo JHTML::tooltip(JText::_('ALIAS_METHOD_HINT'), JText::_('ALIAS_METHOD'),'',JText::_('ALIAS_METHOD')); ?></label>
                        <?php echo JHTML::_('select.genericlist',$alias_template,  'alias_template', 'size="1"','value','text', isset($this->config->alias_template)?$this->config->alias_template:2) ?>

                	</li>
        			<li>
                        <label><?php echo JHTML::tooltip(JText::_('FIRST_HINT'), JText::_('FIRST_ROW_NOMBER'),'',JText::_('FIRST_ROW_NOMBER')); ?></label>
                        <input class="ordering_input" type="text" name="first"  size="32" maxlength="250" value="<?php echo @$this->config->first?$this->config->first:2?>" />
                	</li>

                    <li>
                        <label><?php echo JHTML::tooltip(JText::_('LAST_HINT'), JText::_('LAST_ROW_NOMBER'),'',JText::_('LAST_ROW_NOMBER')); ?></label>
                        <input class="ordering_input" type="text" name="last"  size="32" maxlength="250" value="<?php echo @$this->config->last?$this->config->last:'все'?>" />
                	</li>


        			<li>
                        <label><?php echo JHTML::tooltip(JText::_('COLUMN_NUMBER_HINT'), JText::_('CATEGORY_NAME_COLUMN'),'',JText::_('CATEGORY_NAME_COLUMN')); ?></label>
                        <input class="ordering_input" type="text" name="cat_col"  size="32" maxlength="250" value="<?php echo @isset($this->config->cat_col)?$this->config->cat_col:1?>" />
                	</li>
                </fieldset>

               <fieldset class="config_groups">
               <legend>Бэкап</legend>
                    <li>
                        <label><?php echo JHTML::tooltip(JText::_('AUTO_BACKUP_HINT'), JText::_('AUTOMATIC_BACKUP_BEFORE_IMPORT'),'',JText::_('AUTOMATIC_BACKUP')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'auto_backup', '', @$this->config->auto_backup) ?>
        				</fieldset>
                	</li>

        			<li>
                        <label><?php echo JHTML::tooltip(JText::_('AUTO_BACKUP_TYPE_HINT'), JText::_('AUTOMATIC_BACKUP_TYPE'),'',JText::_('AUTOMATIC_BACKUP_TYPE')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'backup_type', '', @$this->config->backup_type,"gzip","sql") ?>
        				</fieldset>
                	</li>
                </fieldset>
                <fieldset class="config_groups">
                    <legend>Новые товары</legend>
                    <li>
                        <label><?php echo JHTML::tooltip(JText::_('ONLY_UPDATE_PRODUCTS_HINT'), JText::_('ADD_NEW_PRODUCTS'),'',JText::_('ADD_NEW_PRODUCTS')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'create', '', @$this->config->create) ?>
        				</fieldset>
                	</li>
                    <li>
                        <label><?php echo JHTML::tooltip(JText::_('CREATE_WITHOUT_CATEGORY_HINT'), JText::_('CREATE_WITHOUT_CATEGORY'),'',JText::_('CREATE_WITHOUT_CATEGORY')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'create_without_category', '', @$this->config->create_without_category) ?>
        				</fieldset>
                	</li>
                    <li>
                        <label><?php echo JHTML::tooltip(JText::_('CREATE_WITHOUT_SKU_HINT'), JText::_('CREATE_WITHOUT_SKU'),'',JText::_('CREATE_WITHOUT_SKU')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'create_without_sku', '', @$this->config->create_without_sku) ?>
        				</fieldset>
                	</li>
                </fieldset>
                <fieldset class="config_groups">
                    <legend>Обновление товаров</legend>
                    <li>
                        <label><?php echo JHTML::tooltip(JText::_('MULTY_CATEGORY_HINT'), JText::_('MULTI_CATEGORY_SUPPORT_TITLE'),'',JText::_('MULTI_CATEGORY_SUPPORT')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'multicategories', '', @$this->config->multicategories) ?>
        				</fieldset>
                	</li>

        			<li>
                        <label><?php echo JHTML::tooltip(JText::_('CHANGE_CATEGORY_HINT'), JText::_('HANDLE_BELONGING_TO_THE_CATEGORIES'),'',JText::_('HANDLE_BELONGING_TO_THE_CATEGORIES')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'change_category', '', @$this->config->change_category) ?>
        				</fieldset>
                	</li>
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('UPDATE_WITHOUT_SKU_HINT'), JText::_('UPDATE_WITHOUT_SKU'),'',JText::_('UPDATE_WITHOUT_SKU')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'update_without_sku', '', @$this->config->update_without_sku) ?>
        				</fieldset>
                	</li>
                    <li>
                        <label><?php echo  JHTML::tooltip("При оключении данной опции такие поля как Title страницы&#44; Полное описание&#44; Мета-описание&#44; Ключевые слова не будут обновляться у существующих до момента импорта товаров. Эти данные будут заполняться только у товаров&#44; которые создаются в процессе импорта", "Обновлять SEO-параметры?",'',"Обновлять SEO-параметры?"); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'update_seo', '', isset($this->config->update_seo)?$this->config->update_seo:1) ?>
        				</fieldset>
                	</li>
                    <li>
                        <label><?php echo  JHTML::tooltip("Вы можете выбрать определенное условие, при  соблюдении которого цена будет обновляться у сеществующих товаров.", "Обновление цен",'',"Обновление цен"); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                            <?php echo JHTML::_('select.genericlist',$prices_update,  'prices_update', 'size="1"','value','text', isset($this->config->prices_update)?$this->config->prices_update:0) ?>
        				</fieldset>
                	</li>
                </fieldset>
                <fieldset class="config_groups">
                    <legend>Статус публикации</legend>
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('PUBLICATION_STATUS_HINT'), JText::_('PUBLICATION_STATUS'),'',JText::_('PUBLICATION_STATUS')); ?></label>
                        <?php echo JHTML::_('select.genericlist',$publish_new,  'published', 'size="1"','value','text', isset($this->config->published)?$this->config->published:1) ?>

                	</li>
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('PUBLICATION_STATUS_OLD_HINT'), JText::_('PUBLICATION_STATUS_OLD'),'',JText::_('PUBLICATION_STATUS_OLD')); ?></label>
                        <?php echo JHTML::_('select.genericlist',$publish_old,  'published_old', 'size="1"','value','text', isset($this->config->published_old)?$this->config->published_old:-1) ?>

                	</li>


                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('UNPUBLISH_PRODUCTS_WITHOUT_IMAGE_HINT'), JText::_('UNPUBLISH_PRODUCTS_WITHOUT_IMAGE'),'',JText::_('UNPUBLISH_PRODUCTS_WITHOUT_IMAGE')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'unpublish_image', '', @$this->config->unpublish_image) ?>
        				</fieldset>
                	</li>
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('AUTO_UNPUBLISH_HINT'), JText::_('UNPUBLISH_PRODUCTS'),'',JText::_('UNPUBLISH_PRODUCTS')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'unpublish', '', @$this->config->unpublish) ?>
        				</fieldset>
                	</li>
                    <li id="unpublish_cats">
                        <label><?php echo  JHTML::tooltip(JText::_('AUTO_UNPUBLISH_CAT_HINT'), JText::_('UNPUBLISH_CAT_PRODUCTS'),'',JText::_('UNPUBLISH_CAT_PRODUCTS')); ?></label>

                        	<?php echo $list ?>

                	</li>

                </fieldset>
                <fieldset class="config_groups">
                    <legend>Количество на складе</legend>
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('RESET_QUANTITY_IN_STOCK_HINT'), JText::_('RESET_QUANTITY_IN_STOCK'),'',JText::_('RESET_QUANTITY_IN_STOCK')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'reset_stock', '', @$this->config->reset_stock) ?>
        				</fieldset>
                	</li>
                    <li id="reset_cats">
                        <label><?php echo  JHTML::tooltip(JText::_('RESET_QUANTITY_IN_STOCK_CAT_HINT'), JText::_('RESET_QUANTITY_IN_STOCK_CAT'),'',JText::_('RESET_QUANTITY_IN_STOCK_CAT')); ?></label>

                        	<?php echo $reset_list ?>

                	</li>
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('QUANTITY_DEFAULT_HINT'), JText::_('QUANTITY_DEFAULT'),'',JText::_('QUANTITY_DEFAULT')); ?></label>
                        <input class="ordering_input" type="text" name="quantity_default"  size="32" maxlength="250" value="<?php echo  isset($this->config->quantity_default)?$this->config->quantity_default:-1?>" />
                	</li>
                    <li>
                        <label><?php echo  JHTML::tooltip("Если при импорте зависимого атрибута, в прайсе не указано его количество, то оно будет взято из этого поля", "Количество для зависимых атрибутов",'',"Количество для зависимых атрибутов"); ?></label>
                        <input class="ordering_input" type="text" name="quantity_depended"  size="32" maxlength="250" value="<?php echo  isset($this->config->quantity_depended)?$this->config->quantity_depended:10?>" />
                	</li>
                </fieldset>
                <fieldset class="config_groups">
                    <legend>Очистка доп. полей</legend>



        			<li>
                        <label><?php echo  JHTML::tooltip(JText::_('SPEC_PRICE_CLEAR_HINT'), JText::_('SPEC_PRICE_CLEAR'),'',JText::_('SPEC_PRICE_CLEAR')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'spec_price_clear', '', @$this->config->spec_price_clear) ?>
        				</fieldset>
                	</li>
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('EXTRA_FIELDS_CLEAR_HINT'), JText::_('EXTRA_FIELDS_CLEAR'),'',JText::_('EXTRA_FIELDS_CLEAR')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'extra_fields_clear', '', @$this->config->extra_fields_clear) ?>
        				</fieldset>
                	</li>
                </fieldset>
                <fieldset class="config_groups">
                    <legend>Изображения</legend>
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('IMAGES_IMPORT_METHOD_HINT'), JText::_('IMAGES_IMPORT_METHOD'),'',JText::_('IMAGES_IMPORT_METHOD')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno full_width">
                        	<?php echo JHTML::_('select.booleanlist',  'images_import_method', '', @$this->config->images_import_method,JText::_('IMAGES_IN_PRICELIST'),JText::_('FILE_NAME_IN_PRICELIST')) ?>
        				</fieldset>
                	</li>
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('IMAGES_LOAD_HINT'), JText::_('IMAGES_LOAD'),'',JText::_('IMAGES_LOAD')); ?></label>
                        <fieldset class="radio  btn-group btn-group-yesno full_width">
                        	<?php echo JHTML::_('select.booleanlist',  'images_load', '', @$this->config->images_load,JText::_('IMAGES_LOAD_ALL'),'Для новых и товаров без изображения') ?>
        				</fieldset>
                	</li>
                    
                    
                    <!-- загрузка изображений через крон  -->
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('LOAD_IMG_CRON_HINT'), JText::_('LOAD_IMG_CRON'),'',JText::_('LOAD_IMG_CRON')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
			                <?php echo JHTML::_('select.booleanlist',  'load_img_cron', '', @$this->config->load_img_cron) ?>
                        </fieldset>
                    </li>
                    
                    
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('PNG_TO_JPG_CONVERT_HINT'), JText::_('PNG_TO_JPG_CONVERT'),'',JText::_('PNG_TO_JPG_CONVERT')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
			                <?php echo JHTML::_('select.booleanlist',  'png_to_jpg_convert', '', @$this->config->png_to_jpg_convert) ?>
                        </fieldset>
                    </li>
                    <li>
                        <label><?php echo JHTML::tooltip( JText::_( 'PNG_TO_JPG_CONVERT_QUALITY_HINT' ), JText::_( 'PNG_TO_JPG_QUALITY_CONVERT' ), '', JText::_( 'PNG_TO_JPG_QUALITY_CONVERT' ) ); ?></label>
                        <input class="ordering_input"
                               
                               type="text" name="png_to_jpg_convert_quality" size="17" maxlength="250"
                               value="<?php echo isset( $this->config->png_to_jpg_convert_quality ) ? $this->config->png_to_jpg_convert_quality : 50 ?>"/>
                    </li>
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    
                    <li>
                        <label><?php echo  JHTML::tooltip(JText::_('OLD_IMAGES_DELETE_HINT'), JText::_('OLD_IMAGES_DELETE'),'',JText::_('OLD_IMAGES_DELETE')); ?></label>
                        <fieldset class="radio btn-group btn-group-yesno">
                        	<?php echo JHTML::_('select.booleanlist',  'old_images_delete', '', @$this->config->old_images_delete) ?>
        				</fieldset>
                	</li>

                </fieldset>


                 <fieldset class="config_groups">
                     <legend>Метод импорта</legend>
                     <li>
                         <label>
                             <?=JHTML::tooltip(JText::_('LINE_IMPORT_ON_HINT'), JText::_('LINE_IMPORT_ON'),'',JText::_('LINE_IMPORT_ON')); ?>
                         </label>
                         <fieldset class="radio btn-group btn-group-yesno">
                             <?php echo JHTML::_('select.booleanlist',  'line_import_on', '', @$this->config->price_hint) ?>
                         </fieldset>
                     </li>

                     <li>
                         <label>
                             <?= JHTML::tooltip(JText::_('LINE_IMPORT_ON_NUMBER_OF_LINES_HINT'), JText::_('LINE_IMPORT_ON_NUMBER_OF_LINES'), '', JText::_('LINE_IMPORT_ON_NUMBER_OF_LINES')); ?>
                         </label>
                         <input class="ordering_input"
                                type="text" name="line_import_on_number_of_lines" size="17" maxlength="250"
                                value="<?php echo isset($this->config->line_import_on_number_of_lines) ? $this->config->line_import_on_number_of_lines : 50 ?>"/>
                     </li>
                 </fieldset>


                 <fieldset class="config_groups">
                     <legend>Другие настройки</legend>
                     <li>
                         <label><?php echo JHTML::tooltip(JText::_('PRICE_LIST_HINT_HINT'), JText::_('PRICE_LIST_HINT'), '', JText::_('PRICE_LIST_HINT')); ?></label>
                         <fieldset class="radio btn-group btn-group-yesno">
                             <?php echo JHTML::_('select.booleanlist', 'price_hint', '', @$this->config->price_hint) ?>
                         </fieldset>
                     </li>
                 </fieldset>

             </ul>
    		 <input type="hidden" name="fields_list" id="fields_list" value="1,2,3" />
    		 <input type="hidden" name="new_profile_name" id="new_profile_name" value="" />
    		 <input type="hidden" name="profile_id_value" id="profile_id2" value="<?php echo  $this->config->profile_id ?>" />
    	   </form>

    	</fieldset>
    </div>
</div>

