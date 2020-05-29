<?php

defined('_JEXEC') or die('Restricted access');
$view=JRequest :: getVar('view','excel2js','','string');
$mainframe = JFactory::getApplication();
$sitename=$mainframe->getCfg('sitename');

$params = JComponentHelper :: getParams("com_excel2js");
$notify_show=$params->get('notify_show','fold');
$notify_hide=$params->get('notify_hide','explode');

if($notify_show=='none')$notify_show='';
if($notify_hide=='none')$notify_hide='';

function getSize($bytes){
   if($bytes<1024)
   	  return $bytes." B<br>";
   elseif($bytes<1024*1024)
   	  return round($bytes/1024)." KB<br>";
   else
   	  return round($bytes/(1024*1024),2)." MB<br>";
}
?>
<style type="text/css">
.table-striped td{
	border: #CCCCCC 1px solid!important;
	text-align: center;
}
.ui-icon-closethick{
  top: 0!important;
  left: 0!important;
}

</style>
<script type="text/javascript">
     //Переопределяем функцию нажатия на кнопки панели
     if(typeof Joomla != 'undefined'){
         Joomla.submitbutton = function(pressbutton) {
					eval(pressbutton+'()');
		 }
     }
     else{
          window['submitbutton'] = function (pressbutton) {
					eval(pressbutton+'()');
		 }
     }

     function new_backup(){
     	  jQuery("#loader").show();
          jQuery.ajax({
			  url:'index.php?option=com_excel2js&view=backup&rand='+Math.random(),
			  type:'GET',
			  data:{task:'new_backup'},
			  dataType: 'json',
			  error:function(data){jQuery("#loader").hide();notify(0,data.statusText)},
			  success:function(data){
			  	  jQuery("#loader").hide();
				  if(data.status=='ok'){
                      notify(1,"<?php echo  JText::_('BACKUP_SUCCESSFULL') ?><br>Время на создание: "+data.time+" с");
                      jQuery(".table-striped tbody").prepend(data.html);
				      jQuery(".table-striped tbody > tr:first").fadeIn('slow');
                  }
                  else{
                      notify(0,data.html);
                  }


			  }
		  });
    }

    function fix(){
          jQuery.ajax({
				 url:'index.php?option=com_excel2js&view=backup',
				 type:'GET',
				 data:{task:'fix'},
				 dataType: 'html',
				 error:function(data){notify(0,data.statusTextata)},
				 success:function(data){
					 notify(1,data);
		 	 	 }
		  });
    }



	function notify(title,text){
		 if(title)
              jQuery( "#ui-dialog-title-dialog" ).html( "<?php echo  JText::_('THE_OPERATION_WAS_SUCCESSFUL') ?>" );
		 else
			  jQuery( "#ui-dialog-title-dialog" ).html( "<?php echo  JText::_('ERROR_OCCURED') ?>" );
         jQuery( "#dialog" ).html(text);
         jQuery( "#dialog" ).dialog( "open" );
	 }

	function clear(){
		 jQuery( "#dialog-form" ).dialog( "open" );
	}

			jQuery(function(){
				jQuery.fx.speeds._default = 1000;
	            jQuery( "#dialog" ).dialog({
					autoOpen: false,
					show: "<?php echo  $notify_show ?>",
					hide: "<?php echo  $notify_hide ?>"
				});

				//Удаление бэкапа
                jQuery(".ui-icon-circle-close").live("click",function(){
                    var backup_id=jQuery(this).attr("rel");

					jQuery.ajax({
					     url:'index.php?option=com_excel2js&view=backup&rand='+Math.random(),
						 type:'GET',
						 data:{task:'delete_backup',id:backup_id},
						 dataType: 'text',
						 error:function(data){notify(0,data.statusText)},
						 success:function(data){
                              jQuery("#"+backup_id).fadeOut('slow');
                              notify(1,data);
						 }
					});
                });

				jQuery(".ui-icon-arrowreturnthick-1-w").live("click",function(){
                    var backup_id=jQuery(this).attr("rel");
                    jQuery("#loader").show();
					jQuery.ajax({
					     url:'index.php?option=com_excel2js&view=backup&rand='+Math.random(),
						 type:'GET',
						 data:{task:'restore',id:backup_id},
						 dataType: 'text',
						 error:function(data){jQuery("#loader").hide();notify(0,data.statusText)},
						 success:function(data){
						 	  jQuery("#loader").hide();
                              notify(1,data);
						 }
					});

                });

				jQuery( "#dialog-form" ).dialog({
					autoOpen: false,
					show: "<?php echo  $notify_show ?>",
					hide: "<?php echo  $notify_hide ?>",
					modal: false,
					buttons:{
						"Очистить выбранное":function(){
						     if(!confirm("Вы уверены, что хотите произвести очистку на сайте  <?php echo  $_SERVER["SERVER_NAME"] ?>???")){
                    		      jQuery( this ).dialog( "close" );
                                  return false;
                    	     }
                             var $products = jQuery("#products").is(':checked');
                             var $cats = jQuery("#cats").is(':checked');
                             var $images = jQuery("#images").is(':checked');
                             var $manufacturers = jQuery("#manufacturers").is(':checked');
                             var $options = jQuery("#options").is(':checked');
                             var $backups = jQuery("#backups").is(':checked');
							 jQuery( this ).dialog( "close" );
                             jQuery.ajax({
                             	url:'index.php?option=com_excel2js&view=backup&rand='+Math.random(),
                             	type:'GET',
                             	data:{task:'clear',products :$products,cats :$cats,images :$images,manufacturers :$manufacturers,options:$options,backups:$backups},
                             	dataType: 'text',
                             	error:function(data){notify(0,data.statusText);},
								success:function(data){notify(1,data);}
                             });
						},
                    	"Отмена": function() {
							jQuery( this ).dialog( "close" );
						}
                    }
				});



				//hover states on the static widgets
				jQuery('.ui-state-default').hover(
					function() { jQuery(this).addClass('ui-state-hover'); },
					function() { jQuery(this).removeClass('ui-state-hover'); }
				);

                //Переопределяем функцию нажатия на кнопки панели
				Joomla.submitbutton = function(pressbutton) {
						eval(pressbutton+'()');
				}
			});


</script>
<div id="dialog" style="z-index: 10" title=""></div>

<div id="dialog-form" title="<?php echo  JText::_('CLEAR') ?>">
	<p class="validateTips">Выберите параметры очистки</p>
	<form>
	<input id="products" name="products" type="checkbox" <?php echo  @$_COOKIE['b_products']?'checked="true"':'' ?>> - <label style="display:inline" for="products">Товары</label><br>
	<input id="cats" name="cats" type="checkbox" <?php echo  @$_COOKIE['b_cats']?'checked="true"':'' ?>> - <label style="display:inline"  for="cats">Категории и Товары</label><br>
	<input id="images" name="images" type="checkbox" <?php echo  @$_COOKIE['b_images']?'checked="true"':'' ?>> - <label style="display:inline"  for="images">Все файлы изображений</label><br>
	<input id="manufacturers" name="manufacturers" type="checkbox" <?php echo  @$_COOKIE['b_manufacturers']?'checked="true"':'' ?>> - <label style="display:inline"  for="manufacturers">Производители</label><br>
	<input id="options" name="options" type="checkbox" <?php echo  @$_COOKIE['b_options']?'checked="true"':'' ?>> - <label style="display:inline"  for="options">Опции характеристик и атрибутов</label><br>
	<input id="backups" name="backups" type="checkbox" <?php echo  @$_COOKIE['b_backups']?'checked="true"':'' ?>> - <label style="display:inline"  for="backups">Резервные копии</label><br>
	</form>
</div>
<center><div id="loader" style="width: 100%; height: 220px;position:absolute;display: none"><center><img src="<?php echo  JURI::base()."components/com_excel2js/assets/images/loader.gif" ?>"></center></div></center>
<center>

	<form action="" name="adminForm" id="adminForm" method="POST">

	<table class="table table-striped" style=" width: 900px;" cellpadding="0" cellspacing="0" border="1">
		<thead>
		    <tr><th class="title" colspan="6"><?php echo  JText::_('BACKUPS') ?></th></tr>
			<tr style="font-size: 14px; color: #0000FF ">
				<th class="title">ID</th >
				<th class="title"><?php echo  JText::_('FILE_NAME') ?></th >
				<th class="title"><?php echo  JText::_('SIZE') ?></th >
				<th class="title"><?php echo  JText::_('DATE') ?></th >
				<th class="title"><?php echo  JText::_('DELETE') ?></th >
				<th class="title"><?php echo  JText::_('RECOVER_BUTTON') ?></th >

			</tr>
	   </thead>
	   <tbody>
<?php

if($this->list):
	$i=0;
	foreach($this->list as $l):
		$link="components/com_excel2js/backup/".$l->file_name;
		$i++;

		?>
			  <tr id="<?php echo $l->backup_id ?>" class='row<?php echo $i%2 ?>'>

			     <td><?php echo $l->backup_id ?></td>
			     <td><a href="<?php echo $link ?>" target="_blank"><?php echo $l->file_name ?></a></td>
			     <td><?php echo getSize($l->size) ?></td>
			     <td><?php echo $l->date2 ?></td>
                 <td><li style="display: inline-block" class="ui-state-default ui-corner-all"><span title="Удалить" rel="<?php echo $l->backup_id ?>" class="ui-icon ui-icon-circle-close"></span></li></td>
                 <td><li style="display: inline-block" class="ui-state-default ui-corner-all"><span title="<?php echo  JText::_('RECOVER_BUTTON') ?>" rel="<?php echo $l->backup_id ?>" class="ui-icon ui-icon-arrowreturnthick-1-w"></span></li></td>

			  </tr>
		<?php

	endforeach;
endif;

?>
	   </tbody>


	   </table>
	   <input type="hidden" name="boxchecked" value="0" />
       <input type="hidden" name="filter_order" value="<?php echo JRequest :: getVar('filter_order','','','string') ?>" />
	   <input type="hidden" name="filter_order_Dir" value="<?php echo JRequest :: getVar('filter_order_Dir','','','string') ?>" />
       <input type="hidden" name="option" value="com_excel2js" />
       <input type="hidden" name="view" value="<?php echo @$view ?>" />

	   <input type="hidden" name="task" value="" />

	</form>

</center>


