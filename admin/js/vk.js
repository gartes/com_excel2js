jQuery(document).ready(function(){
  jQuery(".chosen-select").chosen();

  setAccordion('#accordion','h2','vk_accordion_main',true,false);

  jQuery("#auth_button").click(function(){
     auth(this,0);
  });
  jQuery("#re_auth_button").click(function(){
     auth(this,1);
  });

  jQuery("#cat_get_button").click(function(){
       jQuery("#spinner").show();
       jQuery.ajax({
             url:'index.php?option=com_excel2js&task=get_vk_categories',
    		 type:'GET',
    		 dataType: 'json',
    		 success:function(data,textStatus, xhr){
    		     jQuery("#spinner").hide();
                 if(data.status=='ok'){
                     jQuery("#categories_form").html(data.html);
                     jQuery("#cat_save_button").show();

                 }
                 else{
                     show_msg(data.msg);
                     jQuery(this).show();

                 }

    		 },
             error:function(data){
                  show_msg("Произошла ошибка. "+data.statusText+"<br>"+data.responseText);
                  jQuery(this).show();
                  jQuery("#spinner").hide();
             }
       });
  });

  jQuery("#cat_save_button").click(function(){
       jQuery("#categories_form").ajaxSubmit({
            url: 'index.php?option=com_excel2js&task=vk_categories_save&rand='+Math.random(),
            dataType:'json',
            success: function(data){
                show_msg(data.msg,true);
                jQuery("#spinner").hide();
            },
            timeout: 6000,
            error:function(data){
            	show_msg(data.statusText+"<br>"+data.responseText);
                jQuery("#spinner").hide();
            }
       });
  });

  jQuery("#filter_save_button").click(function(){
      var options = {
  		data:{task:'save_config_vk',option:'com_excel2js'},
  		success: function(data){show_msg(data,true);jQuery("#spinner").hide();},
  		error: function(data){show_msg("Произошла ошибка. "+data);jQuery("#spinner").hide();},
  		timeout: 3000
  	};
      jQuery('#filter_form').ajaxSubmit(options);
  });

  jQuery("#vk_export_button").click(function(){
  	window.abort_export=false;
    jQuery("#vk_export_button").slideUp();
    jQuery("#vk_stop_button").slideDown();
    jQuery("#cur_product").html('0');
    jQuery("#total_products").html('?');
    jQuery("#exported_products").html('');
    export_products(0);
    jQuery("#export_results").slideDown();
  });

  jQuery("#vk_stop_button").click(function(){
	window.abort_export = true;
  });

  jQuery("#response").on('click',"#send_captcha",function(){
  	  if(!jQuery("#captcha_key").val()){
  	  	jQuery("#captcha_key").css("border-color","red");
		return false;
  	  }
	   var options = {
  		data:{task:'send_captcha',option:'com_excel2js'},
  		success: function(data){
  			jQuery("#exported_products").append(data.products);
			jQuery("#spinner").hide();
			export_products(window.start); 
		},
  		error: function(data){show_msg("Произошла ошибка. "+data);jQuery("#spinner").hide();},
  		timeout: 3000
  	  };
      jQuery('#captcha_form').ajaxSubmit(options);
	  jQuery("#response_div").slideUp();
  });

  function show_msg(text,$auto_hide=false){
    if(text)
	    jQuery("#response").html(text);

    jQuery("#response_div").css({width:'500px',overflow:'auto',maxHeight:'400px'}).show();
    if($auto_hide){
        setTimeout(function(){
            jQuery("#response_div").hide();
    	},3000);
    }
  }

  function show_captcha(data){
	  var text = '<h3>Введите Captcha:</h3>';
	  text += '<form id="captcha_form">';

	  text += '<input type="text" name = "captcha_key" id="captcha_key" /><br><br>';
	  text += '<img src = "'+data.captcha_img+'" /><br><br>';
	  text += '<input type="button" class="btn btn-success" value = "Отправить" id="send_captcha" /><br>';
	  text += '<input type="hidden" name = "captcha_sid" value="'+data.captcha_sid+'" />';
	  text += '<input type="hidden" name = "main_photo_id" value="'+data.main_photo_id+'" />';
	  text += '<input type="hidden" name = "extra_photo_ids" value="'+data.extra_photo_ids+'" />';
	  text += '<input type="hidden" name = "product_id" value="'+data.product_id+'" />';
	  text += '</form>';

	  jQuery("#response").html(text);
	  jQuery("#response_div").css({width:'500px',overflow:'auto',maxHeight:'400px'}).show();
  }

  function export_products(start){
  	 window.start = start;
     jQuery("#spinner").show();
     jQuery.ajax({
             url:'index.php?option=com_excel2js&task=vk_export&start='+start+'&rand='+Math.random(),
    		 type:'GET',
    		 dataType: 'json',
    		 success:function(data,textStatus, xhr){
    		     jQuery("#spinner").hide();
                 if(data.status=='ok'){
                     jQuery("#cur_product").html(data.cur_product);
                     jQuery("#total_products").html(data.total_products);
                     jQuery("#exported_products").append(data.products);
                     if(parseInt(data.cur_product) < parseInt(data.total_products) && !window.abort_export){
                        export_products(parseInt(data.cur_product));
                     }
                     else{
                        jQuery("#vk_export_button").slideDown();
						jQuery("#vk_stop_button").slideUp();
                        show_msg("Экспорт завершен",true);
                     }
                 }
				 else if(data.status=='captcha'){
				 	jQuery("#spinner").hide();
					show_captcha(data);
				 }
                 else{
                     jQuery("#vk_export_button").slideDown();
					 jQuery("#vk_stop_button").slideUp();
                     show_msg(data.msg);

                 }

    		 },
             error:function(data){
                  jQuery("#spinner").hide();
                  jQuery("#vk_export_button").slideDown();
                  show_msg("Произошла ошибка. "+data.statusText+"<br>"+data.responseText);

             }
       });
  }
  function vk_get_products(offset){
       jQuery("#spinner").show();
       jQuery.ajax({
             url:'index.php?option=com_excel2js&task=vk_get_products&list_offset='+offset+'&rand='+Math.random(),
    		 type:'GET',
    		 dataType: 'json',
    		 success:function(data,textStatus, xhr){
    		     jQuery("#spinner").hide();
                 if(data.status=='ok'){
                     jQuery("#vk_products tbody").append(data.products);
                     if(parseInt(data.next_step) > 0){
                        jQuery("#vk_get_more_products_button").html("Получить еще товары ("+data.next_step+" шт.)").show().attr("offset",data.loaded_products);
                        jQuery("#list_footter").html("Получено товаров: "+data.loaded_products+" из "+data.total_products);
                     }
                 }
                 else{
                     show_msg(data.msg);
                 }

    		 },
             error:function(data){
                  jQuery("#spinner").hide();
                  show_msg("Произошла ошибка. "+data.statusText+"<br>"+data.responseText);
             }
       });
  }
  jQuery("#vk_get_products_button").click(function(){
       jQuery("#vk_products").show();
       jQuery("#vk_delete_products_button").show();
       jQuery("#vk_products tbody").html();
       vk_get_products(0);
  });
  jQuery("#vk_get_more_products_button").click(function(){
       var offset = jQuery("#vk_get_more_products_button").attr('offset');
       jQuery("#vk_get_more_products_button").hide();
       vk_get_products(offset);
  });

  jQuery("#vk_delete_products_button").click(function(){
       var products = [];
       jQuery('#vk_products tbody input:checked').each(function() {
            products.push(jQuery(this).val());
       });
       if(products.length==0){
           show_msg("Вы не выбрали ни одного товара",true);
           return false;
       }
       jQuery("#spinner").show();
       jQuery.ajax({
             url:'index.php?option=com_excel2js&task=vk_delete_products&rand='+Math.random(),
    		 type:'POST',
             data:{products_list:JSON.stringify(products)},
    		 dataType: 'json',
    		 success:function(data,textStatus, xhr){
    		     jQuery("#spinner").hide();
                 if(data.status=='ok'){
                     if(typeof data.deleted != 'undefined'){
                        data.deleted.forEach(function (product_id){
                           jQuery("#row_"+product_id).remove();
                        });
                        var msg = "Удалено товаров: "+data.counter;
                        if(typeof data.errors != 'undefined'){
                            msg+="<br><br>Ошибки:<br>"+data.errors;
                        }
                        show_msg(msg);
                     }
                 }
                 else{
                     show_msg(data.msg);
                 }

    		 },
             error:function(data){
                  jQuery("#spinner").hide();
                  show_msg("Произошла ошибка. "+data.statusText+"<br>"+data.responseText);

             }
       });
  });

  jQuery("#checkAll").click(function(){
        jQuery('#vk_products tbody input:checkbox').not(this).prop('checked', this.checked);
  });

  jQuery("#auth_vk_button").click(function(){
       jQuery(this).hide();
       jQuery("#auth_link").before("<h2 class='green'>Вы авторизованы</h2>");
  });

  var spoilers_list = [
        {parentId:"input[name=extra_in_desc]",spoilerId:".extra_in_desc"},
        {parentId:"input[name=attr_in_desc]",spoilerId:".attr_in_desc"}
    ];
    spoilers_list.forEach(function (s) {
        jQuery(s.parentId).change(function () {
            auto_spoiler(s.parentId,s.spoilerId);
        });
        auto_spoiler(s.parentId,s.spoilerId);
    });

  function auto_spoiler(parentId,spoilerId) {
        var val = jQuery(parentId+":checked").val();
        if (val == 1) {
            jQuery(spoilerId).slideDown();
        }
        else {
            jQuery(spoilerId).slideUp();
        }
  }

  function auth(e,new_auth){
       jQuery(e).hide();
       jQuery("#auth_container .good_msg").hide();
       jQuery.ajax({
             url:'index.php?option=com_excel2js&task=auth&new='+new_auth,
    		 type:'GET',
    		 dataType: 'json',
    		 success:function(data,textStatus, xhr){
                 if(data.status=='ok'){
                     jQuery("#auth_link").attr('href',data.url);
                     jQuery("#auth_vk_button").show();
                 }
                 else{
                     show_msg(data.msg);
                     jQuery(e).show();
                 }

    		 },
             error:function(data){
                  show_msg("Произошла ошибка. "+data.statusText+"<br>"+data.responseText);
                  jQuery(e).show();
             }
       });
  }});

