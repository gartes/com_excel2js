<?php
defined('_JEXEC') or die('Restricted access');

$export_resume[]=JHTML::_( 'select.option', '0', "Из указанных ниже категорий" );
$export_resume[]=JHTML::_( 'select.option', '1', "Все, кроме указанных категорий" );

$export_extra_resume[]=JHTML::_( 'select.option', '0', "Только выбранные" );
$export_extra_resume[]=JHTML::_( 'select.option', '1', "Все, кроме выбранных" );

$list = '<select name="export_categories[]" data-placeholder="Выберите категории" class="chosen-select" multiple style="width: 220px;" size="1" >'.$this->export_categories.'</select>';


?>
<div style="position: relative">
    <fieldset id="accordion" class="panelform">
        <h2>1.Авторизация</h2>
        <div class="accordion_container" id="auth_container">
              <?php if(!$this->params->get('group_id')): ?>
                   <p class="error_msg">Внимание! В настройках не указан ID группы ВКонтакте!</p>
              <?php elseif($this->user_token): ?>
                   <p class="good_msg">Вы уже авторизованы</p>
                   <button id="re_auth_button" class="btn btn-success">Повторть авторизацию</button>
                   <a href="" id="auth_link" target="_blank"><button id="auth_vk_button" style="display:none" class="btn btn-success">Перейти на vk.com</button></a>
              <?php else: ?>

             <button id="auth_button" class="btn btn-success">Авторизация</button>
             <a href="" id="auth_link" target="_blank"><button id="auth_vk_button" style="display:none" class="btn btn-success">Перейти на vk.com</button></a>


              <?php endif; ?>
        </div>
        <h2>2.Соответствия категорий</h2>
        <div class="accordion_container" id="category_container">
             <button id="cat_get_button" class="btn btn-success">Получить список категорий</button>
             <button id="cat_save_button" class="btn btn-success" style="display:none">Сохранить соответствия</button>
             <br style="clear: both">
             <form id="categories_form" method="post"></form>
        </div>
        <h2>3.Настройки</h2>
        <div class="accordion_container" id="filter_container">
             <button id="filter_save_button" class="btn btn-success">Сохранить настройки</button>
             <br style="clear: both">
             <form id="filter_form" method="post">
                 <label>Экспортировать товары:</label>
                 <?php echo  JHTML::_('select.genericlist',$export_resume,'export_resume','size="1"','value','text',isset($this->config->export_resume)?$this->config->export_resume:1); ?>
                 <br clear="both">
                 <label>Категории:</label>
                 <?php echo $list ?>
                 <br clear="both">
                 <label>Производители:</label>
                 <?php echo JHTML::_('select.genericlist',$this->manufacturers,'export_manufacturers[]',' data-placeholder="Выберите производителя" class="chosen-select" multiple style="width: 220px;"','manufacturer_id','mf_name',@$this->config->export_manufacturers);  ?>
                 <br clear="both">
                 <label>Метки:</label>
                 <?php echo JHTML::_('select.genericlist',$this->labels,'export_labels[]',' data-placeholder="Выберите метки" class="chosen-select" multiple style="width: 220px;"','id','label_name',@$this->config->export_labels);  ?>
                 <br clear="both">
                 <label>Описание товара:</label>
                 <fieldset class="radio btn-group btn-group-yesno">
                    <?php echo JHTML::_('select.booleanlist',  'full_description', '', isset($this->config->full_description)?$this->config->full_description:1,"Полное","Краткое") ?>
                </fieldset>
                 <br clear="both">
                 <label>Обновлять товары на VK Market?</label>
                 <fieldset class="radio btn-group btn-group-yesno">
                    <?php echo JHTML::_('select.booleanlist',  'export_old', '', isset($this->config->export_old)?$this->config->export_old:0) ?>
                </fieldset>

			   <br clear="both">
                 <label>Вставить ссылку на товар в описании?</label>
                 <fieldset class="radio btn-group btn-group-yesno">
                    <?php echo JHTML::_('select.booleanlist',  'link_in_desc', '', isset($this->config->link_in_desc)?$this->config->link_in_desc:0) ?>
                </fieldset>

                 <br clear="both">
                 <label>Экспорт характеристик в описании</label>
                 <fieldset class="radio btn-group btn-group-yesno">
                    <?php echo JHTML::_('select.booleanlist',  'extra_in_desc', '', isset($this->config->extra_in_desc)?$this->config->extra_in_desc:0) ?>
                </fieldset>
                <br clear="both">

                <div class="extra_in_desc">
                    <label>Режим:</label>
                     <?php echo  JHTML::_('select.genericlist',$export_extra_resume,'export_extra_resume','size="1"','value','text',isset($this->config->export_extra_resume)?$this->config->export_extra_resume:1); ?>
                     <br clear="both">
                     <label>Характеристики:</label>
                     <?php echo JHTML::_('select.genericlist',$this->extra_fields,'export_extra_fields[]',' data-placeholder="Выберите характеристики" class="chosen-select" multiple style="width: 220px;"','id','extra_name',@$this->config->export_extra_fields);  ?>
                    <br clear="both">
                </div>

                 <label>Экспорт независимых атрибутов в описании</label>
                 <fieldset class="radio btn-group btn-group-yesno">
                    <?php echo JHTML::_('select.booleanlist',  'attr_in_desc', '', isset($this->config->attr_in_desc)?$this->config->attr_in_desc:0) ?>
                </fieldset>
                <br clear="both">

                <div class="attr_in_desc">
                    <label>Режим:</label>
                     <?php echo  JHTML::_('select.genericlist',$export_extra_resume,'export_attr_resume','size="1"','value','text',isset($this->config->export_attr_resume)?$this->config->export_attr_resume:1); ?>
                     <br clear="both">
                     <label>Атрибуты:</label>
                     <?php echo JHTML::_('select.genericlist',$this->atributes,'export_atributes[]',' data-placeholder="Выберите атрибуты" class="chosen-select" multiple style="width: 220px;"','attr_id','attr_name',@$this->config->export_atributes);  ?>
                     <br clear="both">
                </div>

                 <label>Экспорт фотографий:</label>
                 <fieldset class="radio btn-group btn-group-yesno">
                    <?php echo JHTML::_('select.booleanlist',  'export_all_photoes', '', isset($this->config->export_all_photoes)?$this->config->export_all_photoes:0,"Все фото","Главное") ?>
                </fieldset>
                 <br clear="both">

             </form>
        </div>
        <h2>4.Экспорт товаров</h2>
        <div class="accordion_container" id="vk_export_container">
             <button id="vk_export_button" class="btn btn-success">Начать экспорт</button>
             <button id="vk_stop_button" style="display: none" class="btn btn-danger">Прервать экспорт</button>
             <br style="clear: both">
             <div id="export_results" style="display: none">
                  <h3 style="text-align:center">Экспортировано товаров: <span id="cur_product">0</span> из <span id="total_products">?</span> </h3>
                  <ol id="exported_products"></ol>
             </div>
        </div>
        <h2>5.Список товаров на VK Market</h2>
        <div class="accordion_container" id="vk_manage_container">
             <button id="vk_get_products_button" class="btn btn-success">Получить список товаров</button>
             <button id="vk_delete_products_button" style="display: none" class="btn btn-danger">Удалить выбранные товары</button>
             <br style="clear: both">
             <table id="vk_products" style="display: none" class="table-striped">
                 <thead>
                   <tr>
                        <th><input type='checkbox' id='checkAll'></th>
                        <th>#</th>
                        <th>Изображение</th>
                        <th>ID VK</th>
                        <th>Наименование</th>
                        <th>Описание</th>
                        <th>Цена</th>
                        <th>Категория</th>
                    </tr>
                 </thead>
                 <tbody></tbody>
                 <tfoot>
                   <tr><td colspan="8" id="list_footter"></td></tr>
                   <tr><td colspan="8"><button id="vk_get_more_products_button" style="display:none" class="btn btn-success"></button></td></tr>
                 </tfoot>
             </table>
        </div>
    </fieldset>

    <div id="response_div" style="position:fixed; z-index: 50; top:200px; left:40%; padding: .7em; display: none" class="ui-state-highlight ui-corner-all">
	    <span id="close" title="<?php echo  JText::_('CLOSE') ?>" class="ui-icon ui-icon-closethick" style="position: absolute; top: 0;right: 0; cursor: pointer"></span>
	    <span id="response"></span>
    </div>
    <div id="spinner"></div>
</div>
