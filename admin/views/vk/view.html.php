<?php

defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');

class Excel2jsViewVK extends JViewLegacy {

	function display($tpl = null) {
		$title = $GLOBALS['component_name'].'. '.JText::_('VKontakte');
		$model =  $this->getModel();
        $this->params = $model->params;
        $this->input = $model->input;
        $this->user_token = $model->user_token;
        $this->config = $model->config;
		//$this->assign('yml_config', $model->getYmlConfig());
		//$this->assign('yml_export_config', $model->getYmlExportConfig());

        //$this->assign('currencies', $this->get('Currencies'));
        //$this->assign('profile_data', $this->get('Profile'));
        //$this->assign('languages', $this->get('Languages'));

        $this->manufacturers = $this->get('Manufacturers');
        $this->labels = $this->get('Labels');
        $this->atributes = $this->get('Atributes');
        $this->extra_fields = $this->get('ExtraFields');
        $this->export_categories =$model->getCategoryList(true,isset($this->config->export_categories)?$this->config->export_categories:0);
        $doc = JFactory::getDocument();

        $doc->addScript(JURI::base()."components/com_excel2js/js/jquery.cookie.js");
        $doc->addScript(JURI::base()."components/com_excel2js/js/jquery.form.js");
        $doc->addScript(JURI::base()."components/com_excel2js/js/core.js");
        $doc->addScript(JURI::base()."components/com_excel2js/js/vk.js");
        $doc->addScript(JURI::base()."components/com_excel2js/js/chosen.jquery.min.js");
        $doc->addStyleSheet(JURI::base()."components/com_excel2js/assets/chosen.css");
		JToolBarHelper :: title($title, 'logo');
		JToolBarHelper :: preferences('com_excel2js');
        if(version_compare(JVERSION,"3",">=")){
            JHtml::_('bootstrap.tooltip');
        }
        else{
            JHTML::_('behavior.tooltip');
        }

		parent :: display($tpl);
	}

}

?>