<?php
	defined( '_JEXEC' ) or die( 'Restricted access' );
	/*
	
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
	
	
	*/

?>
<form id="upload_form_yurkas" action="index.php" method="POST">
    <!--<div class="cont">
        <label for="">Url загрузки файла CSV:</label>
        <input type="text" name="url" value="https://yurkas.by/export/doors.csv">
    </div>-->
    <div class="cont">
        <button type="submit">Закачать</button>
    </div>
    
    
    
    
    <input type="hidden" name="option" value="com_excel2js">
    <input type="hidden" name="task" value="upload_yurkas">
    <input type="hidden" name="format" value="html">
    <input type="hidden" name="star_line" value="0">
    <input type="hidden" name="part" value="0">
    <input type="hidden" name="all_count_price" value="0">
    <input type="hidden" name="rows_processed" value="0">
</form>