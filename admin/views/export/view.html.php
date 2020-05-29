<?php

defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');

class Excel2jsViewExport extends JViewLegacy {

	function display($tpl = null) {
		$db = JFactory::getDBO();

		$title = $GLOBALS['component_name'].'. '.JText::_('EXPORT');
		$model =  $this->getModel();

		$this->assign('model', $model);
		$this->assign('config', $model->config);
		$this->assign('fields', $model->active);
		$this->assign('categories', $model->getCategoryList(0));
		$this->assign('manufacturers', $model->getManufacturers());

		$this->assign('profiles', $model->profile_list());
		JToolBarHelper :: title($title, 'logo');
		JToolBarHelper :: preferences('com_excel2js');


		parent :: display($tpl);
	}

}

?>