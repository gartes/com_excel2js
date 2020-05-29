<?php
defined('_JEXEC') or die('Restricted access');
$view=JRequest :: getVar('view','excel2js','','string');
$doc = JFactory::getDocument();
$doc->addStyleSheet(JURI::root()."administrator/components/com_excel2js/assets/sorter.css");
if(substr(JVERSION,0,1)==3){
    JHtml::_('bootstrap.tooltip');
}
else{
    JHTML::_('behavior.tooltip');
}
$post_max_size=ini_get('post_max_size');
$upload_max_filesize=ini_get('upload_max_filesize');
$max_size=(int)$post_max_size<(int)$upload_max_filesize?$post_max_size:$upload_max_filesize;
$params = JComponentHelper :: getParams("com_excel2js");
$debug=$params->get('debug',0);
$reimport_time=(int)$params->get('reimport_time',10);
$reimport_time++;
$reimport_num=(int)$params->get('reimport_num',10);
$reimport_num--;
$inputCookie  = JFactory::getApplication()->input->cookie;
$show_results = $inputCookie->get('showResults', 1);
function getSize($bytes){
   if($bytes<1024)
   	  return $bytes." B<br>";
   elseif($bytes<1024*1024)
   	  return round($bytes/1024)." KB<br>";
   else
   	  return round($bytes/(1024*1024),2)." MB<br>";
}





$nombers=range('A','Z');
$nombers2=range('A','Z');
foreach($nombers2 as $n2){
    foreach($nombers2 as $letter)
    	$nombers[]=$n2.$letter;
}
$total_fields = count($this->fields);

 ?>
 <style type="text/css">
fieldset.panelform{
  width: 550px!important;
}
#uploaded_files_table{
   margin: 5px auto;
}
#uploaded_files_table td,#uploaded_files_table th{
   text-align: center;
   padding:4px;
}
#uploaded_files_table label{
  width:100%!important;
  max-width: 100%!important;
}
 </style>

 <script type="text/javascript">
    jQuery(document).ready(function(){
       <?php if(count($this->uploaded_files)): ?>
       jQuery("#uploaded_files_table").tablesorter({sortList: [[3,1]],headers: {0:{sorter: false},4:{sorter: false},5:{sorter: false}}});
       <?php endif; ?>
       jQuery(".delete").live('click',function(){
           var $id=jQuery(this).attr('rel');
           var $file=jQuery(this).attr('file');
           jQuery.ajax({
                             url:'index.php?option=com_excel2js&rand='+Math.random(),
           					 type:'GET',
           					 data:{task:'delete',file:$file},
           					 dataType: 'text',
           					 success:function(data){
           					     if(data){
                                    alert(data);
           					     }
                                 else{
                                    jQuery("#row_"+$id).hide();
                                 }
           					 },
                             error:function(data){
                                 alert("Произошла ошибка:\n"+data.responseText);
                             }
                   });
       });
    });

 </script>
<div style="position:relative">
 <h3><?php echo JText::_('CURRENT_PROFILE') ?>: <span style="font-weight: bold; color: #006633" id="current_profile"><?php echo $this->config->profile_name ?></span></h3>
  <div id="version" style="position: absolute; top:5px;right: 5px"><?php echo JText::_('JVERSION') ?>: <?php echo $this->version ?><br><span id="reimport_counter"></span></div>
</div>
  <form action="index.php?option=com_excel2js" method="POST">
	<h3><?php echo JText::_('CHANGE_TO') ?>: <?php echo JHTML::_('select.genericlist',$this->profiles,  'profile_id', 'size="1" onchange="this.form.submit()"','id','profile',$this->config->profile_id) ?></h3>
	<input type="hidden" name="task" value="change_profile" />
</form>

 <?php
 if($this->config->price_hint):
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
  case 5:$categories=array('','');
  break;
  case 6:$categories=array('','');
		if(!isset($this->fields['path']))echo '<font size="2" color="#FF0000">'.JText::_('YOU_DID_NOT_SPECIFY_THE_COLUMN_NUMBER_CATEGORY').'</font>';
  break;
  case 7:$categories=array('','');
		if(!isset($this->fields['path']))echo '<font size="2" color="#FF0000">'.JText::_('YOU_DID_NOT_SPECIFY_THE_COLUMN_NUMBER_CATEGORY').'</font>';
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
          echo "<td>".($f->type=='independ'?$this->model->getIndependedOptions($f->extra_id,1):JText::_($f->example_array[1]))."</td>";
	  }


	  ?>
  </tr>
</table>
<?php endif; ?>
<script>

	jQuery(function() {
		  var end_of_import = false;
		  var intrval_id;
		  var interval_counter=0; //Количество циклов статистики
		  var buffer_counter=<?php echo $reimport_time ?>; //В это в ремя не страбатывает перезапуск
          var last_cur_row=0;
		  var timeouted=0;
          var reimport_counter=0;
          jQuery("#xls_file").val('');
          function reimport(){
          	 jQuery("#xls_file").attr("disabled","disabled");
             jQuery('#import_form').ajaxSubmit({
                url: 'index.php?option=com_excel2js&task=import&reimport=1&rand='+Math.random(),
			    success: showResponse,
	            timeout: 300000,
				error:function(data){
					timeouted=1;
					if(data.statusText!='Gateway Time-out'){
						timeouted=1;
						reimport_counter++;
						if(reimport_counter<<?php echo $reimport_num ?>)
							reimport();
						else{
	                        clearInterval(intrval_id);
		                    options.url+='&reimport=1';
							jQuery("#results").html('<b><font color="#FF0000"><?php echo JText::_("IMPORT_ERROR") ?></font></b><br />'+data.responseText).show('bounce');
							jQuery("#import_button").show('slide',{},1000).val("<?php echo JText::_('IMPORT_CONTINUE') ?>");
						}

					}
				}

             });

          }

		  var options = {
		    url: 'index.php?option=com_excel2js&task=import&rand='+Math.random(),
		    success: showResponse,
            timeout: 900000,
			error:function(data){
				timeouted=1;
				if(data.statusText!='Gateway Time-out'){
				    console.log(data.statusText);
                    reimport();
				}
			}
		  };

          var upload_options = {
		    url: 'index.php?option=com_excel2js&task=upload&rand='+Math.random(),
		    success: upload_complete,
            timeout: 900000,
            uploadProgress: OnProgress,
			error:upload_error
		  };

          function upload_error(data){
              jQuery("#import_button").show('explode',{},1500);
              jQuery("#results").html("<span style='color: #FF3333'>Ошибка при загрузке файла. Загрузите файл по ФТП в папку <?php echo JURI::root() ?>administrator/components/com_excel2js/xls/</span>").show('slide',{},1000);
              jQuery("#import_started").html("Статус: <span style='color: #FF3333'>Ошибка при загрузке файла</span>");
          }

          jQuery("#results").hide();
          jQuery("#statistics").hide();

		  jQuery('#import_button').click(function(){  //Запуск закачки файлов

               jQuery("#results").hide();
               jQuery("#statistics").hide();
               jQuery("#last_response").hide();

               jQuery("#results").html('');
               jQuery("#import_button").hide('explode',{},1500);
               jQuery("#filename").html('');
               jQuery("#row").html(0);
               jQuery("#total_row").html(0);
               jQuery("#new").html(0);
               jQuery("#up").html(0);
               jQuery("#new_cat").html(0);
               jQuery("#up_cat").html(0);
               jQuery("#duration").html("0 c.");
               jQuery("#category").html("");
               jQuery("#product").html("");


               if(jQuery("#xls_file").val()){
                   jQuery('#upload_form').ajaxSubmit(upload_options);
                   jQuery("#import_started").html("Статус: Загрузка файла");
                   jQuery("#import_started").slideDown();
               }
               else{
                   start_import();
               }
		  });

        jQuery("#abort_button").click(function() {
            jQuery.ajax({
		        type: "HEAD",
		        async: true,
		        url:'index.php?option=com_excel2js&view=excel2js&task=abort&rand='+Math.random(),
		        success: function(){
		        	clearInterval(intrval_id);
                    jQuery("#abort_button").hide('explode',{},1500);
					if(timeouted){
						setTimeout(function(){
	                        jQuery("#results").load("index.php",{option : 'com_excel2js',task : 'response'}).delay(1000);
							jQuery("#results").show('slide',{},1000);
						},5000);

					}
		        }
		    });
        });

        function upload_complete(data){ //Загрузка файлов завершена
             if(data!='Ok'){
                jQuery("#results").html(data).show('slide',{},1000);
                return false;
             }
             jQuery.ajax({
                               url:'index.php?option=com_excel2js&view=excel2js&rand='+Math.random(),
          					 type:'GET',
          					 data:{task:'update_files'},
          					 dataType: 'html',
          					 success:function(data){
                                       jQuery("#uploaded_files_tbody").html(data);
                                       var name;
                                        for (var i = 0; i < jQuery("#xls_file").get(0).files.length; ++i) {
                                            name=jQuery("#xls_file").get(0).files[i].name;
                                            jQuery("input[value='"+name+"']").prop('checked', true);
                                        }
                                        jQuery("#xls_file").val('');
                                        start_import();
                                        jQuery("#uploaded_files_table").trigger("update");


          					 }
             });
        }


        function start_import() {
		  	  reimport_counter=0;
			  end_of_import=false;
              if(jQuery('#zip_file').val()){
                  jQuery("#import_started").html("Статус: Загрузка архива изображений");
                  jQuery("#import_started").slideDown();
              }
			  jQuery("#abort_button").show('slide',{},1500);
			  jQuery('#import_form').ajaxSubmit(options);

			  setTimeout(function(){

                  //jQuery("#statistics").slideDown();
	              setTimeout(function(){
                     getStat();
		          },2000);
			  },1000);

			  return false;
		  }

        function getStat(){
            if(buffer_counter<<?php echo $reimport_time ?>){
                jQuery("#reimport_counter").html("Перезапуск через: "+buffer_counter);
            }
            else{
                 jQuery("#reimport_counter").html('');
            }
            if(end_of_import){
				return false;
			}
            jQuery.ajax({
                 url:'index.php?option=com_excel2js&view=excel2js',
                 type:'GET',
                 data:{task:'get_stat',rand:Math.random()},
                 dataType: 'json',
                 success:function(data){

					  if(data != null){
					  	  if(data.status){
                             jQuery("#import_started").html("Статус: "+data.status);
					  	  }
						  else{
	                          jQuery("#import_started:visible").slideUp();
	                  		  jQuery("#statistics").slideDown();
						  }

                 	  }
                      else{
                        return;
                      }
                      if(parseInt(data.cur_row)!=-1 && parseInt(data.last_response)>5){
                          buffer_counter--;
                          console.log("Перезапуск через "+buffer_counter+" с.");
                      }
                      if(parseInt(data.last_response)<=5){
                          buffer_counter=<?php echo $reimport_time ?>;
                      }
                      var file_num =parseInt(data.file_index)+1;
                      jQuery("#filename").html(data.filename+"("+file_num+"<?php echo JText::_('IMPORT_OF') ?> "+data.total_files+")");
                      jQuery("#row").html(data.cur_row);
                      jQuery("#total_row").html(data.num_row);
                      jQuery("#new").html(data.pn);
                      jQuery("#up").html(data.pu);
                      jQuery("#new_cat").html(data.cn);
                      jQuery("#up_cat").html(data.cu);
                      jQuery("#duration").html(data.time+" c.");
                      jQuery("#category").html(data.cur_cat);
                      jQuery("#product").html(data.cur_prod);
                      jQuery("#last_response").html("<center><?php echo JText::_('SERVER_LAST_RESPONSE') ?> "+data.last_response+" <?php echo JText::_('SECONDS_AGO') ?></center>");


                      jQuery("#file_index").val(data.file_index);


                       var speed = parseInt(data.cur_row / data.time);
                       var speed2 = parseInt(data.cur_row -last_cur_row);
                       last_cur_row=data.cur_row;
					   var progress = Math.round(100*data.cur_row/data.num_row);
					   var left_time = Math.round(100*data.time/progress)-data.time;
                       jQuery( "#progressbar" ).progressbar({value: progress});
                       if(progress==100){
                          jQuery("#progresspercent").html("<b>Импорт завершен</b>");
                       }
                       else
                          jQuery("#progresspercent").html("<b>"+progress+"%</b>");

                       if(data.total_files==1){
                       	   jQuery("#time_left :hidden").show();
						   jQuery("#speed :hidden").show();
	                       jQuery("#time_left").html("<b><?php echo JText::_('TIME_LEFT') ?>: "+left_time+" <?php echo JText::_('SECONDS') ?></b>");
	                       jQuery("#speed").html("<b><?php echo JText::_('RATE') ?>: "+speed+" <?php echo JText::_('ROWS_PER_SECOND') ?></b>");
	                       jQuery("#step").html("<b>Строки: +"+speed2+"</b>");
                       }
                       else{
                           jQuery("#time_left :visible").hide();
						   jQuery("#speed :visible").hide();
						   jQuery("#step :visible").hide();
                       }

                       jQuery("#memory").html("<b><?php echo JText::_('MEMORY_USAGE') ?>: "+data.mem+"<?php echo JText::_('MB') ?> <?php echo JText::_('FROM') ?> "+data.mem_total+"<?php echo JText::_('MB') ?> ("+Math.round(100*data.mem/data.mem_total)+"%)</b>");

					   if(parseInt(data.cur_row) >= parseInt(data.num_row) && timeouted){
                             clearInterval(intrval_id);
                             var $show_results=jQuery("#show_results:checked").val();
                             jQuery("#results").load("index.php",{option : 'com_excel2js',task : 'response',show_results:$show_results}).delay(1000);
							 jQuery("#results").show('slide',{},1000);
							 //jQuery("#import_button").show('slide',{},1000);
					   }


                       if(data.last_response > 7  && buffer_counter <= 0){
                       	    buffer_counter=<?php echo $reimport_time ?>;
                            jQuery("#reimport_counter").html("");
							options.url='index.php?option=com_excel2js&task=import&reimport=1';
                            console.log("Перезапуск №"+parseInt(reimport_counter)+1);
                            jQuery('#import_form').ajaxSubmit(options);

                       }

                       setTimeout(function(){
                           getStat();
        		       },2000);
                  },
                  error:function(data){
                      console.log("Произошла ошибка:\n"+data.responseText);
                      setTimeout(function(){
                         getStat();
    		          },2000);
                  }
           });
        }

		function showResponse(responseText, statusText)  {
		    if(responseText=='timeout'){
                reimport();
                return false;
            }
            jQuery("#results").html(responseText).show('slide',{},1000);
			clearInterval(intrval_id);
			setTimeout(function(){end_of_import = true;},1000);


            getStat();

			jQuery("#statistics").slideDown();
            jQuery("#import_button").show('slide',{},1000).val("<?php echo JText::_('START_IMPORT') ?>");
			jQuery("#abort_button").hide('explode',{},1500);
            reimport_counter=0;
			options.url='index.php?option=com_excel2js&task=import';
			jQuery("#xls_file").attr("disabled",false);
            //Снимаем галку с импортируемого файла
            jQuery("#uploaded_files_tbody input").prop('checked', false);

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
         function OnProgress(event, position, total, percentComplete){
            if(percentComplete==100 && position!=total){
               percentComplete=99;
            }
            jQuery("#import_started").html("Статус: Загрузка файла (" +percentComplete+"%)<br>Загружено: "+getSize(position)+" из "+getSize(total));
            if(!jQuery("#import_started").is(":visible") ){
                jQuery("#import_started").slideDown();
            }
            //console.log(percentComplete);
          }

          function getSize($bytes){
             if($bytes<1024)
             	  return $bytes+" B<br>";
             else if($bytes<1024*1024)
             	  return Math.round($bytes/1024)+" KB<br>";
             else
             	  return parseFloat($bytes/(1024*1024)).toFixed(2)+" MB<br>";
          }
	});
</script>
<style type="text/css">
.wide{
	width: 300px!important;
	max-width: 300px!important;
	text-align: left;
}
.small{width: 80px!important;max-width: 100px!important;}

</style>
<h1 align="center"><?php echo JText::_('IMPORT') ?></h1>
<fieldset class="panelform" style="width:800px;margin: 10px auto;">
<?php echo JHtml::_('sliders.start', 'import-sliders', array('useCookie'=>1)); ?>
<?php echo JHtml::_('sliders.panel', JText::_('ENTER_THE_XLS_FILE_ON_YOUR_COMPUTER'), 'local'); ?>
<form id="upload_form" action="index.php" method="POST" enctype="multipart/form-data">

	<input type="hidden" name="option" value="com_excel2js" />
	<input type="hidden" name="task" value="upload" />




	<center>
    <?php echo JHTML::tooltip('Для увеличения лимита, Вам необходимо увеличить параметры <b>post_max_size</b> и <b>upload_max_filesize</b> в настройках сервера', 'Максимальный размер файла загрузки - '.$max_size,'','<span style="color: #CC6600; font-weight: bold;text-decoration:none; border-bottom:#F00 1px dashed;">Максимальный размер файла загрузки - '.$max_size.'</span>'); ?>
   <?php if(!is_writable(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_excel2js'.DS.'xls')): ?>
        <br><br><?php echo JHTML::tooltip('Для того, чтобы прайс мог быть загружен на сервер, необходимо на папку /administrator/components/com_excel2js/xls/ установить права 775 или 777 (если уже установлено 775 или 755)', 'Папка НЕ доступна для записи','','<span style="color: #CC0000; font-weight: bold;text-decoration:none; border-bottom:#F00 1px dashed;">Внимание! Папка <b>/administrator/components/com_excel2js/xls/</b> НЕ доступна для записи</span>'); ?>
   <?php endif; ?>
   <br>
    <input id="xls_file" name="xls_file[]" type="file" size="30" style="margin: 5px 5px 5px 173px" multiple=""/><?php echo JHTML::tooltip (JText::_('MULTI_UPLOAD_HINT'), JText::_('MULTI_UPLOAD'), 'tooltip.png'); ?>
    </center>
</form>
<?php echo JHtml::_('sliders.panel', "<strong>".JText::_('OR')."</strong> ".JText::_('SELECT_THE_DOWNLOADED'), 'loaded'); ?>
<form id="import_form" action="index.php" method="POST"  enctype="multipart/form-data">

<input type="hidden" name="option" value="com_excel2js" />
<input type="hidden" name="task" value="import" />

                <table style="border-collapse: collapse" id="uploaded_files_table" class="tablesorter">
                <thead>
                  <tr>
                    <th>Импорт</th>
                    <th>Файл</th>
                    <th style="width: 62px">Размер</th>
                    <th>Дата</th>
                    <th></th>
                    <th></th>
                  </tr>
                </thead>
                <tbody id="uploaded_files_tbody">
                <?php foreach($this->uploaded_files as $key=>$f): ?>
                  <tr id="row_<?php echo $key ?>">
                    <td><input name="uploaded_file[]" id="uploaded_file_<?php echo $key ?>" type="checkbox" value="<?php echo $f->file ?>" style="margin-left: 14px"></td>
                    <td><label for="uploaded_file_<?php echo $key ?>"><?php echo $f->file ?></label></td>
                    <td><?php echo getSize($f->size) ?></td>
                    <td><?php echo date("Y-m-d H:i",$f->time) ?></td>
                    <td><a href="index.php?option=com_excel2js&task=download&file=<?php echo $f->file ?>"><img src="<?php echo JURI::base() ?>/components/com_excel2js/assets/images/download.png" width="16" height="16" alt=""></a></td>
                    <td><img style="cursor: pointer" rel="<?php echo $key ?>" file="<?php echo $f->file ?>"  class="delete" src="<?php echo JURI::base() ?>/components/com_excel2js/assets/images/delete.png" width="16" height="16" alt=""></td>
                  </tr>

                <?php endforeach; ?>
                </tbody>
              </table>

       <?php echo JHtml::_('sliders.end'); ?>
       <?php echo JHtml::_('sliders.start', 'images-sliders', array('useCookie'=>1)); ?>
       <?php echo JHtml::_('sliders.panel', "Импорт изображений", 'image'); ?>
       <ul  class="adminformlist" style="text-align: center; margin: 10px auto; width: 200px">
		<li><?php echo JHTML::tooltip('Для увеличения лимита, Вам необходимо увеличить параметры <b>post_max_size</b> и <b>upload_max_filesize</b> в настройках сервера', 'Максимальный размер zip-архива - '.$max_size,'','<span style="color: #CC6600; font-weight: bold;text-decoration:none; border-bottom:#F00 1px dashed;">Максимальный размер zip-архива - '.$max_size.'</span>'); ?><label class="wide" for="zip_file"><?php echo JText::_('ENTER_YOUR_ZIP_FILE_WITH_THE_IMAGES_ON_YOUR_COMPUTER') ?>:</label>&nbsp;<input id="zip_file" name="zip_file" type="file" size="30" />
<br /></li>
	<br  clear="both"/>

   </ul>
   <?php echo JHtml::_('sliders.end'); ?>

   <ul  class="adminformlist" style="text-align: center; margin: 10px auto; width: 400px">
        <li style="width: 400px;margin: 0 auto"><input id="show_results" name="show_results" type="checkbox" value="1" <?php echo $show_results?'checked="checked"':'' ?> >&nbsp;<label for="show_results" style="width:380px!important;max-width:380px!important;float:none!important;display: inline;line-height: 21px;">Вывести таблицу со всеми товарами после окончания</label> </li><br>
        <li style="width: 110px;margin: 0 auto"><input style="float: none" type="button" class="btn btn-success" id="import_button" value="<?php echo JText::_('START_IMPORT') ?>" /></li>
   <li style="width: 110px;margin: 0 auto"><input style="float: none;display:none" type="button" class="btn btn-success" id="abort_button" value="<?php echo JText::_('ABORT_IMPORT') ?>" /></li>
   </ul>


</form>
</fieldset>
    <h3 id="import_started" style="display: none" align="center"><?php echo JText::_('IMPORT_STARTED') ?>...</h3>
    <div id="statistics">
	    <div id="progresspercent"></div>
	    <div id="progressbar"></div>
        <div id="time_left"></div>
        <div id="speed"></div>
        <div id="step"></div>
        <div id="memory"></div>
		<br />
		<span style='font-size: 18px;display: inline-block;text-align: left; margin: 15px 0;width: 500px;'>
		    <?php echo JText::_('THE_IMPORT_FILE') ?>: <strong id="filename"></strong><br />
			<?php echo JText::_('IMPORTED_ROWS') ?>:<strong id="row">0</strong> <?php echo JText::_('FROM') ?> <strong id="total_row">0</strong> <br />
			<?php echo JText::_('IMPORT_TAKES') ?>: <strong id="duration">0</strong><br />
			<?php echo JText::_('NEW_PRODUCTS') ?>: <strong id="new">0</strong><br />
			<?php echo JText::_('UPDATED_PRODUCTS') ?>: <strong id="up">0</strong><br />
			<?php echo JText::_('NEW_CATEGORIES') ?>: <strong id="new_cat">0</strong><br />
			<?php echo JText::_('UPDATED_CATEGORIES') ?>: <strong id="up_cat">0</strong><br />
			<?php echo JText::_('CURRENT_CATEGORY') ?>: <strong id="category"></strong><br />
			<?php echo JText::_('CURRENT_PRODUCT') ?>: <strong id="product"></strong>
		</span>
	</div>
    <div id="results" style="text-align: center"></div>

    <div id="last_response"></div>

