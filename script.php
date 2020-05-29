<?php
if(!defined("DS")){
    define("DS",DIRECTORY_SEPARATOR);
}
jimport( 'joomla.application.component.model');
defined('_JEXEC') or die('Restricted access');


	class com_excel2jsInstallerScript
	{


	    public function install($parent){
            $this->_db = JFactory::getDBO();

            $this->_db->setQuery("SELECT language FROM #__jshopping_languages");

    		$languages=$this->_db->loadColumn();

            $lang=in_array("ru-RU",$languages)?"ru-RU":$languages[0];

            $this->_db->setQuery('INSERT IGNORE INTO `#__excel2js` (`id`, `profile`, `active`, `config`, `default_profile`) VALUES
(1, \'По умолчанию\', \'3,4,6,9,10\', \'O:8:"stdClass":34:{s:14:"price_template";s:1:"1";s:6:"simbol";s:1:" ";s:14:"extra_category";s:25:"Прочие товары";s:8:"language";s:5:"'.$lang.'";s:5:"units";s:1:"3";s:8:"currency";s:1:"1";s:6:"access";s:1:"1";s:14:"alias_template";s:1:"2";s:5:"first";s:1:"2";s:4:"last";s:6:"все";s:7:"cat_col";s:1:"1";s:11:"auto_backup";s:1:"1";s:11:"backup_type";s:1:"0";s:6:"create";s:1:"1";s:23:"create_without_category";s:1:"1";s:18:"create_without_sku";s:1:"1";s:15:"multicategories";s:1:"1";s:15:"change_category";s:1:"1";s:18:"update_without_sku";s:1:"1";s:9:"published";s:1:"1";s:15:"unpublish_image";s:1:"0";s:9:"unpublish";s:1:"0";s:20:"unpublish_categories";a:1:{i:0;s:1:"0";}s:11:"reset_stock";s:1:"0";s:16:"reset_categories";a:1:{i:0;s:1:"0";}s:16:"quantity_default";s:2:"-1";s:14:"delete_related";s:1:"0";s:16:"spec_price_clear";s:1:"1";s:18:"extra_fields_clear";s:1:"1";s:20:"images_import_method";s:1:"0";s:17:"old_images_delete";s:1:"0";s:10:"price_hint";s:1:"1";s:16:"new_profile_name";s:0:"";s:16:"profile_id_value";s:1:"1";}\', 1);');
            $this->_db->Query();

            $this->_db->setQuery("SELECT id FROM #__excel2js_yml WHERE id=1");
            if(!$this->_db->loadResult()){
                $this->_db->setQuery("INSERT INTO #__excel2js_yml SET id = 1, yml_export_path =".$this->_db->Quote(JPATH_ROOT.DS."ymarket.xml").", params=".$this->_db->Quote('{"languege":"'.$lang.'","is_update":1,"is_create":1,"identity":"product_id"}').",export_params=".$this->_db->Quote('{"languege":"'.$lang.'"}').", `name`='По-умолчанию', `default` = 1");
                $this->_db->Query();
            }

            $this->_db->setQuery("SELECT id FROM #__excel2js_vk_config WHERE id=1");
            if(!$this->_db->loadResult()){
                $this->_db->setQuery("INSERT INTO `#__excel2js_vk_config` (`id`, `name`, `params`, `is_default`) VALUES
(1, 'По-умолчанию', '', 1)");
                $this->_db->Query();
            }
	    }

	    public	function update($parent){
	    	$this->_db = JFactory::getDBO();
            $sqls=file_get_contents(dirname(__FILE__).DS."admin".DS."install.sql");

			$sqls= $this->_db->splitSql($sqls);
            foreach($sqls as $sql){
            	if(empty($sql))continue;
                if(!trim($sql))continue;
                $this->_db->setQuery($sql);
				$this->_db->query();
            }
            $this->_db->setQuery("SELECT id FROM #__excel2js_yml WHERE id=1");
            if(!$this->_db->loadResult()){
                $this->_db->setQuery("SELECT language FROM #__jshopping_languages");

        		$languages=$this->_db->loadColumn();

                $lang=in_array("ru-RU",$languages)?"ru-RU":$languages[0];

                $this->_db->setQuery("INSERT INTO #__excel2js_yml SET id = 1, yml_export_path =".$this->_db->Quote(JPATH_ROOT.DS."ymarket.xml").", params=".$this->_db->Quote('{"languege":"'.$lang.'","is_update":1,"is_create":1,"identity":"product_id"}').",export_params=".$this->_db->Quote('{"languege":"'.$lang.'"}').", `name`='По-умолчанию', `default` = 1");
                $this->_db->Query();
            }
            
            $this->_db->setQuery("SELECT id FROM #__excel2js_vk_config WHERE id=1");
            if(!$this->_db->loadResult()){
                $this->_db->setQuery("INSERT INTO `#__excel2js_vk_config` (`id`, `name`, `params`, `is_default`) VALUES
(1, 'По-умолчанию', '', 1)");
                $this->_db->Query();
            }
	        echo "Компонент Обновлен до версии ".$parent->get('manifest')->version;
	    }

}
?>