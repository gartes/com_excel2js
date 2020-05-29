<?php
defined('_JEXEC') or die('Restricted access');
$view=JRequest :: getVar('view','export','','string');
$inputCookie  = JFactory::getApplication()->input->cookie;



     $memory_limits[] = JHTML::_('select.option',  '0.2', "20%", 'value', 'text' );
     $memory_limits[] = JHTML::_('select.option',  '0.3', "30%", 'value', 'text' );
     $memory_limits[] = JHTML::_('select.option',  '0.4', "40%", 'value', 'text' );
     $memory_limits[] = JHTML::_('select.option',  '0.5', "50%", 'value', 'text' );
     $memory_limits[] = JHTML::_('select.option',  '0.6', "60%", 'value', 'text' );
     $memory_limits[] = JHTML::_('select.option',  '0.7', "70%", 'value', 'text' );
     $memory_limits[] = JHTML::_('select.option',  '0.8', "80%", 'value', 'text' );
     $memory_limits[] = JHTML::_('select.option',  '0.9', "90%", 'value', 'text' );

     $cat_ordering[] = JHTML::_('select.option',  'category_id', JText::_('BY_ID'), 'value', 'text' );
     $cat_ordering[] = JHTML::_('select.option',  'category_name', JText::_('ALPHABETICALLY'), 'value', 'text' );
     $cat_ordering[] = JHTML::_('select.option',  'ordering', JText::_('BY_ORDERING'), 'value', 'text' );

     $product_statuses[] = JHTML::_('select.option',  '-1', 'Все', 'value', 'text' );
     $product_statuses[] = JHTML::_('select.option',  '0', 'Неопубликованные', 'value', 'text' );
     $product_statuses[] = JHTML::_('select.option',  '1', 'Опубликованные', 'value', 'text' );

     $selected_cat=@unserialize(urldecode(JRequest::getVar('c_category', 'cookie', '0', 'string')));
     $selected_man=@unserialize(urldecode(JRequest::getVar('c_man', 'cookie', '0', 'string')));

     // Получаем выпадающий список
     $list = JHTML::_('select.genericlist',$this->categories,'category','style="float: none;width: 160px;" size="1" ','category_id','category_name',is_array($selected_cat)?$selected_cat[0]:$selected_cat);

if($this->manufacturers){
    $man_list = JHTML::_('select.genericlist',$this->manufacturers,'manufacturer_id[]','data-placeholder="Выберите производителя" class="chosen-select" multiple style="float: none;width: 220px;" size="1" ','manufacturer_id','manufacturer_name',$selected_man?$selected_man:0);
    $doc = JFactory::getDocument();
    $doc->addScript(JURI::base()."components/com_excel2js/js/chosen.jquery.min.js");
    $doc->addStyleSheet(JURI::base()."components/com_excel2js/assets/chosen.css");
    $doc->addScriptDeclaration ( 'jQuery(document).ready(function(){jQuery(".chosen-select").chosen();});' );
}


if($this->config->price_hint):
$nombers=range('A','Z');
$nombers2=range('A','Z');
foreach($nombers2 as $n2){
    foreach($nombers2 as $letter)
    	$nombers[]=$n2.$letter;
}
$total_fields = count($this->fields); 

?>
  <h3><?php echo JText::_('CURRENT_PROFILE') ?>: <span style="font-weight: bold; color: #006633" id="current_profile"><?php echo $this->config->profile_name ?></span></h3>
<form action="index.php?option=com_excel2js&view=export" method="POST">
	<h3><?php echo JText::_('CHANGE_TO') ?>: <?php echo JHTML::_('select.genericlist',$this->profiles,  'profile_id', 'size="1" onchange="this.form.submit()"','id','profile',$this->config->profile_id) ?></h3>
	<input type="hidden" name="task" value="change_profile" />
</form>
<?php
switch ($this->config->price_template) {
  case 1:
         $categories=array('1.'.JText::_('HOUSEHOLD_APPLIANCES'),'1.1.'.JText::_('REFRIGERATORS'));
  break;
  case 2:
         $categories=array(JText::_('HOUSEHOLD_APPLIANCES'),$this->config->simbol.JText::_('REFRIGERATORS'));
  break;

  case 3:
         $categories=array(JText::_('HOUSEHOLD_APPLIANCES'),JText::_('REFRIGERATORS').$this->config->simbol);
  break;

  case 4:
         $categories=array(JText::_('HOUSEHOLD_APPLIANCES'),JText::_('REFRIGERATORS'));
		 foreach($this->fields as $a ){
            if($a->name=='path'){
               $path=$a->ordering;
            }
    	}
		if(!isset($path))echo '<font size="2" color="#FF0000">'.JText::_('YOU_DID_NOT_SPECIFY_THE_COLUMN_NUMBER_CATEGORY').'</font>';
  break;
  case 5:
         $categories=array('','');
		 echo '<strong><font size="3" color="#FF0000">'.JText::_('WRONG_METHOD').'</font></strong>';
case 6:
         $categories=array('','');
		 if(!isset($this->fields['path']))echo '<font size="2" color="#FF0000">'.JText::_('YOU_DID_NOT_SPECIFY_THE_COLUMN_NUMBER_CATEGORY').'</font>';
         $doc = JFactory::getDocument();
         $doc->addScript(JURI::base()."components/com_excel2js/js/chosen.jquery.min.js");
         $doc->addStyleSheet(JURI::base()."components/com_excel2js/assets/chosen.css");
         $doc->addScriptDeclaration ( 'jQuery(document).ready(function(){jQuery(".chosen-select").chosen();});' );
         $list = JHTML::_('select.genericlist',$this->categories,'category[]','data-placeholder="Выберите категорию" class="chosen-select" multiple style="float: none;width: 220px;" size="1" ','category_id','category_name',$selected_cat);
  break;
  case 7:
        $categories=array('','');
		if(!isset($this->fields['path']))echo '<font size="2" color="#FF0000">'.JText::_('YOU_DID_NOT_SPECIFY_THE_COLUMN_NUMBER_CATEGORY').'</font>';
        $doc = JFactory::getDocument();
        $doc->addScript(JURI::base()."components/com_excel2js/js/chosen.jquery.min.js");
        $doc->addStyleSheet(JURI::base()."components/com_excel2js/assets/chosen.css");
        $doc->addScriptDeclaration ( 'jQuery(document).ready(function(){jQuery(".chosen-select").chosen();});' );
        $list = JHTML::_('select.genericlist',$this->categories,'category[]','data-placeholder="Выберите категорию" class="chosen-select" multiple style="float: none;width: 220px;" size="1" ','category_id','category_name',$selected_cat);
  break;
  case 8:
         $categories=array(JText::_('HOUSEHOLD_APPLIANCES'),JText::_('REFRIGERATORS').$this->config->simbol);
  break;
}
?>

<h3><?php echo JText::_('PRICE_EXAMPLE') ?>:</h3>
<table class="table table-striped" align="center" border="1">


  <tr class="title">
     <?php if($this->config->price_template==8): ?>
     <td rowspan="6" style="width: 67px; padding: 0"><img src="./components/com_excel2js/assets/images/ierarh.jpg" width="67" height="153" style="border: 0" alt=""></td>
	 <?php endif; ?>
     <td class="ui-state-highlight center bold"><?php echo JText::_('LINE_NOMBER') ?></td>
     <?php
     for($i=0;$i<$total_fields;$i++)
	 	echo "<td class=\"ui-state-highlight center bold\">{$nombers[$i]} (".($i+1).")</td>";
     ?>
  </tr>
  <tr class="title">
     <th class="ui-state-highlight"></th>
     <?php foreach($this->fields as $f)echo "<th class=\"title\">".JText::_($f->title)."</th>"; ?>
  </tr>

<?php if(!in_array($this->config->price_template,array(5,6,7))): ?>
  <tr>
      <td class="ui-state-highlight center bold"><?php echo $this->config->first++ ?></td>
      <?php
      for($i=0;$i<$total_fields;$i++)
	  	 if($this->config->cat_col == $i+1)
		 	echo "<td>".$categories[0]."</td>";
		 elseif(@$path == $i+1)
		    echo "<td>1</td>";
		 else
		 	echo "<td>&nbsp;</td>";
      ?>
  </tr>
  <tr>
      <td class="ui-state-highlight center bold"><?php echo $this->config->first++ ?></td>
      <?php
      for($i=0;$i<$total_fields;$i++)
	  	 if($this->config->cat_col == $i+1)
		 	echo "<td>".$categories[1]."</td>";
		 elseif(@$path == $i+1)
		    echo "<td>1.1</td>";
		 else
		 	echo "<td>&nbsp;</td>";
      ?>
  </tr>
<?php  endif; ?>
  <tr>
      <td class="ui-state-highlight center bold"><?php echo $this->config->first++ ?></td>
      <?php
	  foreach($this->fields as $key => $f){

          $f->example_array = explode(';',$f->example);

		  if($this->config->price_template==6 AND $f->name=='path')
		  	 $f->example_array=array(1,2);
		  elseif($this->config->price_template==7 AND $f->name=='path')
		     $f->example_array=array(JText::_('HOUSEHOLD_APPLIANCES'),JText::_('REFRIGERATORS'));

          echo "<td>".($f->type=='independ'?$this->model->getIndependedOptions($f->extra_id,0):JText::_($f->example_array[0]))."</td>";
	  }
	  ?>
  </tr>

  <tr>
      <td class="ui-state-highlight center bold"><?php echo $this->config->first++ ?></td>
      <?php

	  foreach($this->fields as $key => $f){
          echo "<td>".($f->type=='independ'?$this->model->getIndependedOptions($f->extra_id,1):JText::_(@$f->example_array[1]))."</td>";
	  }

	  ?>
  </tr>
</table>
<?php endif; ?>
<script>

	jQuery(function() {
		  var end_of_export = false;
		  var intrval_id;
		  var part=0;
		  var timeouted=0;
		  var memory_limit=jQuery("#memory_limit").val();

          jQuery("#results").hide();
          jQuery("#statistics").hide();

		  jQuery('#export_button').click(function() {
		  	  end_of_export = false;
			  jQuery("#export_button").hide('explode',{},1500);
              part = 0;
              jQuery("#part").val(0);
			  jQuery("#links").html('');
			  jQuery("#import_started").slideDown();

              jQuery('#export_form').ajaxSubmit({
				  	url: 'index.php?option=com_excel2js&task=export&view=export&rand='+Math.random(),
				    success: showResponse,
				    method: 'post',
		            timeout: 600000,
					dataType:'json',
					error:if_error
			  });

              jQuery("#row").html(0);
              jQuery("#cat").html(0);
              jQuery("#product").html(0);
              jQuery("#current_cat").html('');
              jQuery("#current_product").html('');
              jQuery("#duration").html("0 c.");
              jQuery("#memory").html("0");
              jQuery("#status").html("");
			  setTimeout(function(){
                  jQuery("#import_started").slideUp();
                  jQuery("#statistics").slideDown();
	              intrval_id = setInterval(function(){
	              	 if(end_of_export){
		               clearInterval(intrval_id);
		        	 }
					 else
                     	getStat();
		          },2000);
			  },2000);
			  return false;
		  });
        function if_error(data,textStatus){
           if(data.statusText=='OK'){
           	  if(data.responseText.indexOf("Out of memory")>-1)out_of_memory();
           	  else{
           	      jQuery("#links").append("<div><font color='#FF0000'><?php echo JText::_('ERROR_OCCURED') ?>:<br>"+textStatus+"<br>"+data.responseText+"</font></div>").show('bounce');

                  jQuery("#export_button").show('explode',{},1500);
                  setTimeout(function(){
                      jQuery("#statistics").slideUp();
                  },2000);
           	  }

           }
		   else if(data.statusText=='Gateway Time-out' ||data.statusText== 'timeout'){
                timeouted=1;
		   }
		   else{
		   		end_of_export = true;
		        clearInterval(intrval_id);
				jQuery("#links").append('<b><font color="#FF0000"><?php echo JText::_("ERROR_OCCURED_EXPORT") ?></font></b><br />'+data.statusText+'<br>'+data.responseText).show('bounce');
				jQuery("#export_button").show('slide',{},1000);
		   }
        }

        function out_of_memory(){
            var mem_limit = jQuery("#memory_limit").val();
			  if(mem_limit > 0.2){
			  	 mem_limit=(parseFloat(mem_limit) - 0.1).toFixed(1);
			  	 alert("<?php echo JText::_('OUT_OF_RAM1') ?> "+(mem_limit*100)+"<?php echo JText::_('OUT_OF_RAM2') ?>");
                 jQuery("#memory_limit").val(mem_limit);
                 jQuery('#export_form').ajaxSubmit({
    				  	url: 'index.php?option=com_excel2js&task=export&view=export&rand='+Math.random(),
    				    success: showResponse,
    				    method: 'post',
    		            timeout: 600000,
    					dataType:'json',
    					error:if_error
    			  });
				 return false;
			  }
			  else{
              	  jQuery("#links").append("<?php echo JText::_('OUT_OF_RAM3') ?>");
	              end_of_export = true;
	              clearInterval(intrval_id);
              }
        }

        function getStat(){

            jQuery.ajax({
                 url:'index.php?option=com_excel2js&view=export&rand='+Math.random(),
                 type:'GET',
                 data:{task:'get_export_stat'},
                 dataType: 'json',
                 success:function(data){
                 	if(data.row){
                      jQuery("#row").html(data.row);
                      jQuery("#cat").html(data.cat);
                      jQuery("#product").html(data.product);
                      jQuery("#current_cat").html(data.current_cat);
                      jQuery("#current_product").html(data.current_product);
                      jQuery("#duration").html(data.time+" c.");
                      jQuery("#memory").html(data.mem);
                      jQuery("#status").html(data.status);
                    }
					   if(timeouted && data.notmodified){
								jQuery.ajax({
								  	url: 'index.php?option=com_excel2js&task=get_export_file&view=export&rand='+Math.random(),
								    success: showResponse,
								    method: 'post',
						            timeout: 120000,
									dataType:'json',
									data:{csv:jQuery("#csv").val(),part:part},
									error:if_error
								});
					   }

                  }
           });
        }

		function showResponse(responseText, statusText)  {
			if(responseText.text=='No')
				return false;
			if(!responseText){
               out_of_memory();
			   return false;
			}
            timeouted=0;
            jQuery("#links").append('<a href="'+responseText.link+'">'+responseText.text+'</a><br />');
			getStat();

			if(responseText.finish!=1){
                part++;
				jQuery("#part").val(part);

				jQuery('#export_form').ajaxSubmit({
				  	url: 'index.php?option=com_excel2js&task=export&view=export&rand='+Math.random(),
				    success: showResponse,
				    method: 'post',
		            timeout: 600000,
					dataType:'json',
					error:if_error
			    });
				return false;
			}
            else if(part>1){
                jQuery("#links").append('<a href="index.php?option=com_excel2js&view=export&task=zip&parts='+part+'"><?php echo JText::_('DOWNLOAD_ALL_PARTS') ?></a><br />');
            }
			end_of_export = true;
            clearInterval(intrval_id);
			jQuery("#statistics").slideDown();
            jQuery("#export_button").show('slide',{},1000);
		}

		jQuery("input[name=make_thumb]:radio").change(function(){
			if(jQuery(this).val()==1)
                 jQuery("#thumb_set").show('fold');
			else
                 jQuery("#thumb_set").hide('fold');
		});


		 if(jQuery("input[name=make_thumb]:checked").val()==1)
               jQuery("#thumb_set").show('fold');
		 else
               jQuery("#thumb_set").hide('fold');

         jQuery(".spoiler").live("click",function(){
			 jQuery("#spoiler_span").slideToggle("slow");
			  jQuery(this).toggleClass("active");
		 });
	});
</script>
<style type="text/css">
.wide{
	width: 430px!important;
	max-width: 460px!important;
	text-align: left;
}
.small{width: 80px!important;max-width: 100px!important;}
.panelform label{
	max-width: 50%!important;
	width: 160px!important;
	text-align: right;
}

</style>
<h1 align="center"><?php echo JText::_('EXPORT') ?></h1>
<form id="export_form" action="index.php" method="POST" enctype="multipart/form-data">
<fieldset class="panelform" style="width:400px;margin: 10px auto;">
	<input type="hidden" id="part" name="part" value="0" />


        <label><?php echo JText::_('FILE_FORMAT') ?>:</label>
		<select style="float: none" name="csv" id="csv" size="1">
			<option <?php echo !$inputCookie->get('c_csv', 0)?'selected="selected"':'' ?> value="0">Excel</option>
			<option <?php echo $inputCookie->get('c_csv', 0)?'selected="selected"':'' ?> value="1">CSV</option>
		</select>
        <br />
        <label><?php echo JText::_('CATEGORY') ?>:</label>
            <?php echo $list ?>
		<br />
        <label><?php echo JText::_('Производитель') ?>:</label>
            <?php echo $man_list ?>
		<br />
		<label><?php echo JText::_('LIMIT_RAM') ?>:</label>
        <?php echo JHTML::_('select.genericlist',$memory_limits,'memory_limit','style="float: none;" size="1" ','value','text',$inputCookie->get('c_memory_limit', "0.7")) ?>

		<br />
		<label><?php echo JText::_('CATEGORY_ORDER') ?>:</label>

        <?php echo JHTML::_('select.genericlist',$cat_ordering,'order','style="float: none;" size="1" ','value','text',$inputCookie->get('c_order', "category_id")) ?>

		<br />
        <label>Статус товаров:</label>
        <?php echo JHTML::_('select.genericlist',$product_statuses,'product_status','style="float: none;" size="1" ','value','text',$inputCookie->get('c_product_status', "-1")) ?>

		<br />
	   <label>	<?php echo JText::_('ROWS_LIMIT') ?>:</label> <input type="text" name="row_limit" id="row_limit" value="<?php echo $inputCookie->get('c_row_limit', "0") ?>" style="float: none"/> <br />


   <br style="clear:both " />
   <center><input style="float: none" type="button" class="btn btn-success" id="export_button" value="<?php echo JText::_('START_EXPORT') ?>" /></center>

</fieldset>
</form>
    <h3 id="import_started" style="display: none" align="center"><?php echo JText::_('EXPORT_STARTED') ?></h3>
    <div id="statistics">
		<br />
		<span style='font-size: 18px;display: inline-block;text-align: left; margin: 15px 0;'>
			<?php echo JText::_('EXPORTED_ROWS') ?>:<strong id="row">0</strong><br />
			<?php echo JText::_('EXPORT_LASTS') ?>: <strong id="duration">0</strong><br />
			<?php echo JText::_('EXPORTED_CATEGORIES') ?>: <strong id="cat">0</strong><br />
			<?php echo JText::_('EXPORTED_PRODUCTS') ?>: <strong id="product">0</strong><br />
			<?php echo JText::_('CURRENT_CATEGORY') ?>: <strong id="current_cat"></strong><br />
			<?php echo JText::_('CURRENT_PRODUCT') ?>: <strong id="current_product"></strong><br />
			<?php echo JText::_('MEMORY_USAGE') ?>: <strong id="memory"></strong><br />
			<?php echo JText::_('CURRENT_OPERATION') ?>: <strong id="status"></strong><br />

		</span>
	</div>
    <div id="links" style="text-align: center"></div>
