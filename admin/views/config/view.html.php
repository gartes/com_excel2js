<?php

defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');

class Excel2jsViewConfig extends JViewLegacy {

	function display($tpl = null) {
		@$db = JFactory::getDBO();
		$option=JRequest::getVar('option', '', '', 'string');
		$view=JRequest::getVar('view', '', '', 'string');

		$title = $GLOBALS['component_name'].'. '.JText::_('CONFIGURATIONS');
		@$model =  $this->getModel();
		@$this->assign('active', $this->get('Active'));
		@$this->assign('inactive', $this->get('Inactive'));
		@$this->assign('config', $this->get('Config'));
		@$this->assign('currencies', $this->get('Currencies'));
		@$this->assign('languages', $this->get('Languages'));
	   //	@$this->assign('default_lang', $model->default_lang);
		@$this->assign('groups', $this->get('Groups'));
		@$this->assign('categories', $this->get('CategoryList'));
		@$this->assign('profiles', $model->profile_list(true));
		@$this->assign('units', $model->getUnits());

		JToolBarHelper :: title($title, 'logo');



		parent :: display($tpl);
	}

}

?>