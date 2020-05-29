<?php

defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');

class Excel2jsViewSupport extends JViewLegacy {
	
	function display($tpl = NULL) {
		$db     = JFactory::getDBO();
		$option = JRequest::getVar('option', '', '', 'string');
		$view   = JRequest::getVar('view', '', '', 'string');
		
		$title = $GLOBALS['component_name'] . '. ' . JText::_('SUPPORT');
		
		$model            = $this->getModel();
		$this->changelist = $model->getChangeList();
		$this->data       = $model->getData();
		$this->my_version = $model->getMyVersion();
		$this->order_id   = $model->order_id;
		$this->email      = $model->email;
		JToolBarHelper:: title($title, 'logo');
		parent:: display($tpl);
	}
	
}

?>