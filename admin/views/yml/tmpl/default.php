<?php
	defined( '_JEXEC' ) or die( 'Restricted access' );
	$view = JRequest:: getVar( 'view', 'excel2js', '', 'string' );
	$doc  = JFactory::getDocument();
	$doc->addStyleSheet( JURI::root() . "administrator/components/com_excel2js/assets/sorter.css" );
	$doc->addScript( JURI::base() . "components/com_excel2js/js/yml.js" );
	$doc->addScript( JURI::base() . "components/com_excel2js/js/chosen.jquery.min.js" );
	$doc->addStyleSheet( JURI::base() . "components/com_excel2js/assets/chosen.css" );
	$doc->addScriptDeclaration( 'jQuery(document).ready(function(){jQuery(".chosen-select").chosen();});' );
	
	JToolBarHelper::save( 'save_config_yml', JText::_( 'SAVE' ) );
	JToolBarHelper::save( 'save_profile_yml', JText::_( 'SAVE_AS_PROFILE' ) );
	
	$identity[] = JHTML::_( 'select.option', '0', "ID товара", 'value', 'text' );
	$identity[] = JHTML::_( 'select.option', '1', "Артикул", 'value', 'text' );
	$identity[] = JHTML::_( 'select.option', '2', "Наименование", 'value', 'text' );
	
	$images_mode[] = JHTML::_( 'select.option', '2', "Для всех товаров", 'value', 'text' );
	$images_mode[] = JHTML::_( 'select.option', '1', "Для новых товаров", 'value', 'text' );
	$images_mode[] = JHTML::_( 'select.option', '0', "Не загружать", 'value', 'text' );
	
	$export_resume[] = JHTML::_( 'select.option', '0', "Из указанных ниже категорий" );
	$export_resume[] = JHTML::_( 'select.option', '1', "Все, кроме указанных категорий" );
	
	
	$alias_template[] = JHTML::_( 'select.option', '1', JText::_( 'ALIAS_PRODUCT_NAME' ) );
	$alias_template[] = JHTML::_( 'select.option', '2', JText::_( 'ALIAS_ID_PRODUCT_NAME' ) );
	$alias_template[] = JHTML::_( 'select.option', '3', JText::_( 'ALIAS_PRODUCT_NAME_ID' ) );
	$alias_template[] = JHTML::_( 'select.option', '4', JText::_( 'ALIAS_SKU_PRODUCT_NAME' ) );
	$alias_template[] = JHTML::_( 'select.option', '5', JText::_( 'ALIAS_PRODUCT_NAME_SKU' ) );
	$alias_template[] = JHTML::_( 'select.option', '6', JText::_( 'ALIAS_SKU_ID_PRODUCT_NAME' ) );
	$alias_template[] = JHTML::_( 'select.option', '7', JText::_( 'ALIAS_ID_SKU_PRODUCT_NAME' ) );
	$alias_template[] = JHTML::_( 'select.option', '8', JText::_( 'ALIAS_PRODUCT_NAME_SKU_ID' ) );
	$alias_template[] = JHTML::_( 'select.option', '9', JText::_( 'ALIAS_PRODUCT_NAME_ID_SKU' ) );
	$alias_template[] = JHTML::_( 'select.option', '10', JText::_( 'ALIAS_SKU' ) );
	$alias_template[] = JHTML::_( 'select.option', '11', JText::_( 'ALIAS_ID' ) );
	
	if( substr( JVERSION, 0, 1 ) == 3 )
	{
		JHtml::_( 'bootstrap.tooltip' );
	}
	else
	{
		JHTML::_( 'behavior.tooltip' );
	}
	
	$params = JComponentHelper:: getParams( "com_excel2js" );
	$debug  = $params->get( 'debug', 0 );
	
	
	$inputCookie = JFactory::getApplication()->input->cookie;
	$list        = '<select name="export_categories[]" data-placeholder="Выберите категории" class="chosen-select" multiple style="width: 220px;" size="1" >' . $this->export_categories . '</select>';

?>
<style type="text/css">
    fieldset.panelform {
        width: 800px !important;
    }

    #uploaded_files_table label {
        width: 100% !important;
        max-width: 100% !important;
    }

    .wide {
        width: 300px !important;
        max-width: 300px !important;
        text-align: left;
    }

    .small {
        width: 80px !important;
        max-width: 100px !important;
    }

    fieldset.adminform fieldset.radio label, fieldset.panelform fieldset.radio label {

        position: relative;
        top: -10px;
    }

    #export_form {
        padding: 20px;
    }

</style>
<?php $jtext_array = array( 'IMPORT_ERROR', 'IMPORT_CONTINUE', 'IMPORT_OF', 'SERVER_LAST_RESPONSE', 'SECONDS_AGO', 'TIME_LEFT', 'SECONDS', 'RATE', 'ROWS_PER_SECOND', 'MEMORY_USAGE', 'MB', 'FROM', 'START_IMPORT', 'ADD_NEW', 'INPUT_THE_NAME_OF_THE_NEW_PROFILE' );
?>
<script type="text/javascript">
    var $JURI_root = '<?php echo JURI::root() ?>';
	<?php foreach ($jtext_array as $jtext)
	{
		echo 'var $jtext_' . $jtext . " = '" . JText::_( $jtext ) . "';\n";
	}
	?>
</script>


<div style="position:relative">
    <h3> <?php echo JText::_( 'CURRENT_PROFILE' ) ?>: <span style="font-weight: bold; color: #006633"
        <form style="display: inline" action="index.php?option=com_excel2js&view=yml" method="POST"
              onsubmit="return confirm('<?php echo JText::_( 'ARE_YOU_SURE_YOU_WANT_TO_DELETE_THE_CURRENT_PROFILE' ) ?>');">
            <input type="submit" class="btn btn-success" value="<?php echo JText::_( 'DELETE' ) ?>"/>
            <input type="hidden" name="task" value="delete_profile"/>
        </form>
    </h3>

    <form action="index.php?option=com_excel2js&view=yml" method="POST">
        <h3><?php echo JText::_( 'CHANGE_TO' ) ?>
            : <?php echo JHTML::_( 'select.genericlist', $this->profiles, 'profile_id', 'size="1" onchange="this.form.submit()"', 'id', 'name', $this->profile_data->id ) ?></h3>
        <input type="hidden" name="task" value="change_yml_profile"/>
    </form>


    <h1 align="center"><?php echo JText::_( 'YML' ) ?></h1>
    <fieldset class="panelform" style="width:1000px;margin: 10px auto;">
		<?php echo JHtml::_( 'sliders.start', 'import-sliders', array( 'useCookie' => 1 ) ); ?>
		<?php echo JHtml::_( 'sliders.panel', JText::_( 'IMPORT' ), 'yml_import' ); ?>
        <form id="import_form" action="index.php" method="POST" enctype="multipart/form-data">
            <center>
                <label style="float: none">Адрес xml-файла:</label>
                <input name="yml_import_path" type="text" size="90" style="float:none"
                       placeholder="http://some-domain/ymarket.xml"
                       value="<?php echo @$this->yml_config->yml_import_path ? $this->yml_config->yml_import_path : "" ?>"/>
                <br>
                Ссылка для Cron - <a
                        href="<?php echo JURI::root() ?>administrator/components/com_excel2js/models/cron_yml_import.php"
                        target="_blank"><?php echo JURI::root() ?>
                    administrator/components/com_excel2js/models/cron_yml_import.php</a> <br>
                <div style="margin: 10px auto; width: 400px;padding-left: 176px;">
                    <div class="row">
                        <label>
							<?php echo JHTML::tooltip( JText::_( 'LANGUAGE_HINT' ), JText::_( 'LANGUAGE' ), '', JText::_( 'LANGUAGE' ) ); ?>
                        </label>
						<?php echo JHTML::_( 'select.genericlist', $this->languages, 'languege', 'size="1"', 'language', 'name', @$this->yml_config->languege ) ?>
                    </div>
                    <div class="row">
                        <label>Обновлять товары:</label>
                        <fieldset class="radio btn-group btn-group-yesno">
							<?php echo JHTML::_( 'select.booleanlist', 'is_update', '', @$this->yml_config->is_update ) ?>
                        </fieldset>
                    </div>
                    <div class="row">
                        <label>Создавать товары:</label>
                        <fieldset class="radio btn-group btn-group-yesno">
							<?php echo JHTML::_( 'select.booleanlist', 'is_create', '', @$this->yml_config->is_create ) ?>
                        </fieldset>
                    </div>
                    <div class="row">
                        <label>Количество по умолчанию:</label>

                        <input name="product_in_stock_default" type="text" size="4"
                               value="<?php echo isset( $this->yml_config->product_in_stock_default ) ? $this->yml_config->product_in_stock_default : 10 ?>"/>

                    </div>
                    <div class="row">
                        <label>Коэффициент перерасчета цен:</label>

                        <input name="import_factor" type="text" size="4"
                               value="<?php echo isset( $this->yml_config->import_factor ) ? $this->yml_config->import_factor : 1 ?>"/>

                    </div>
                    <div class="row">
                        <label>Идентификатор товара:</label>
                        <fieldset class="radio btn-group btn-group-yesno">
							<?php echo JHTML::_( 'select.genericlist', $identity, 'identity', 'size="1"', 'value', 'text', @$this->yml_config->identity ); ?>
                        </fieldset>
                    </div>

                    <div class="row">
                        <label><?php echo JHTML::tooltip( JText::_( 'ALIAS_METHOD_HINT' ), JText::_( 'ALIAS_METHOD' ), '', JText::_( 'ALIAS_METHOD' ) ); ?></label>
						<?php echo JHTML::_( 'select.genericlist', $alias_template, 'alias_template', 'size="1"', 'value', 'text', @$this->yml_config->alias_template ? $this->yml_config->alias_template : 2 ) ?>
                    </div>
                    <div class="row">
                        <label>Загрузка изображений:</label>
                        <fieldset class="radio btn-group btn-group-yesno">
							<?php echo JHTML::_( 'select.genericlist', $images_mode, 'images_mode', 'size="1"', 'value', 'text', @$this->yml_config->images_mode ); ?>
                        </fieldset>
                    </div>

                </div>


                <div id="statistics" style="display: none">
                    <div id="progresspercent"></div>
                    <div id="progressbar"></div>
                    <div id="time_left"></div>
                    <div id="speed"></div>
                    <div id="step"></div>
                    <div id="memory"></div>
                    <br/>
                    <span style='font-size: 18px;display: inline-block;text-align: left; margin: 15px 0;width: 500px;'>

			<?php echo JText::_( 'IMPORTED_ROWS' ) ?>:<strong
                                id="row">0</strong> <?php echo JText::_( 'FROM' ) ?> <strong
                                id="total_row">0</strong> <br/>
			<?php echo JText::_( 'IMPORT_TAKES' ) ?>: <strong id="duration">0</strong><br/>
			<?php echo JText::_( 'NEW_PRODUCTS' ) ?>: <strong id="new">0</strong><br/>
			<?php echo JText::_( 'UPDATED_PRODUCTS' ) ?>: <strong id="up">0</strong><br/>
			<?php echo JText::_( 'NEW_CATEGORIES' ) ?>: <strong id="new_cat">0</strong><br/>
			<?php echo JText::_( 'UPDATED_CATEGORIES' ) ?>: <strong id="up_cat">0</strong><br/>
			<?php echo JText::_( 'CURRENT_PRODUCT' ) ?>: <strong id="product"></strong>
		</span>
                </div>
                <input id="import_yml" style="float:none" name="" class="btn btn-success" type="button"
                       value="Импортировать YML">
                <input id="abort_button" style="float: none;display:none" name="" type="button" class="btn btn-success"
                       value="Прервать импорт">
                <div id="last_response"></div>
                <div id="errors"></div>
                <div id="results" style="text-align: center"></div>


            </center>

        </form>


        <!-- Экспорт -->
		
		<?php echo JHtml::_( 'sliders.panel', JText::_( 'EXPORT' ), 'yml_export' ); ?>
        <form id="export_form" action="index.php" method="POST" enctype="multipart/form-data">


            <label>Путь к файлу:</label>

            <input name="yml_export_path" type="text" size="60"
                   value="<?php echo @$this->yml_config->yml_export_path ? $this->yml_config->yml_export_path : ( JPATH_ROOT . DS . "ymarket.xml" ) ?>"/>
            <br>

            <label>
				<?php echo JHTML::tooltip( JText::_( 'LANGUAGE_HINT' ), JText::_( 'LANGUAGE' ), '', JText::_( 'LANGUAGE' ) ); ?>
            </label>
			
			<?php echo JHTML::_( 'select.genericlist', $this->languages, 'languege', 'size="1"', 'language', 'name', @$this->yml_export_config->languege ) ?>
            <br clear="both">
            <label>Основная валюта:</label>
			<?php echo JHTML::_( 'select.genericlist', $this->currencies, 'currency', 'size="1"', 'currency_id', 'currency_name', @$this->yml_export_config->currency ? $this->yml_export_config->currency : 0 ) ?>
            <br clear="both">

            <label>Исключить товары, которых нет на складе?</label>
            <fieldset class="radio btn-group btn-group-yesno">
				<?php echo JHTML::_( 'select.booleanlist', 'not_in_stock', '', @$this->yml_export_config->not_in_stock ) ?>
            </fieldset>
            <br clear="both">
            <label>Коэффициент перерасчета цен:</label>

            <input name="export_factor" type="text" size="4"
                   value="<?php echo isset( $this->yml_export_config->export_factor ) ? $this->yml_export_config->export_factor : 1 ?>"/>
            <br clear="both">
            <label>Экспортировать товары:</label>
			<?php echo JHTML::_( 'select.genericlist', $export_resume, 'export_resume', 'size="1"', 'value', 'text', @$this->yml_export_config->export_resume ); ?>
            <br clear="both">
            <label>Категории:</label>
			<?php echo $list ?>
            <br clear="both">
            <label>Производители:</label>
			<?php echo JHTML::_( 'select.genericlist', $this->manufacturers, 'export_manufacturers[]', ' data-placeholder="Выберите производителя" class="chosen-select" multiple style="width: 220px;"', 'manufacturer_id', 'mf_name', @$this->yml_export_config->export_manufacturers ); ?>
            <br clear="both">
            <center>


                <div id="export_statistics" style="display: none">
                    <div id="export_progresspercent"></div>
                    <div id="export_progressbar"></div>
                    <div id="export_time_left"></div>
                    <div id="export_speed"></div>
                    <br/>
                    <span style='font-size: 18px;display: inline-block;text-align: left; margin: 15px 0;width: 500px;'>

			Экспортировано товаров:<strong id="export_row">0</strong> <?php echo JText::_( 'FROM' ) ?> <strong
                                id="export_total_row">0</strong> <br/>
			Экспорт длится: <strong id="export_duration">0</strong><br/>
		</span>
                </div>
                <div id="response_link"></div>
                <br/><input id="create_yml" style="float:none" name="" type="button" class="btn btn-success"
                            value="Создать YML">
                <br>

                <br>
                Ссылка для Cron - <a
                        href="<?php echo JURI::root() ?>administrator/components/com_excel2js/models/cron_yml_export.php"
                        target="_blank"><?php echo JURI::root() ?>
                    administrator/components/com_excel2js/models/cron_yml_export.php</a> <br>
                Создание "на лету" - <a
                        href="<?php echo JURI::root() ?>administrator/components/com_excel2js/models/yml_export.php"
                        target="_blank"><?php echo JURI::root() ?>
                    administrator/components/com_excel2js/models/yml_export.php</a>
            </center>


        </form>
		<?php echo JHtml::_( 'sliders.end' ); ?>

    </fieldset>

    <div id="response_div" style="position:fixed; z-index: 50; top:200px; left:40%; padding: .7em; display: none"
         class="ui-state-highlight ui-corner-all">
        <span id="close" title="<?php echo JText::_( 'CLOSE' ) ?>" class="ui-icon ui-icon-closethick"
              style="position: absolute; top: 0;right: 0; cursor: pointer"></span>
        <form action="index.php?option=com_excel2js&view=yml" id="ajax_form" method="POST">
            <p style="margin-bottom: 5px; text-align: center; font-size: 14px">
                <span style="float: left; margin-right: .3em;" class="ui-icon ui-icon-info"></span>

                <span id="response"></span>
            </p>
        </form>
    </div>

</div>