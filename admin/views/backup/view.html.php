<?php

defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.view');

class Excel2jsViewBackup extends JViewLegacy {

	function display($tpl = null) {
		$db = JFactory::getDBO();
		$option=JRequest::getVar('option', '', '', 'string');
		$view=JRequest::getVar('view', '', '', 'string');

		$title = $GLOBALS['component_name'].'. '.JText::_('RECOVER');

		$model =  $this->getModel();

			$this->assign('list', $this->get('Backups'));


			JToolBarHelper :: title($title, 'logo');
			JToolBarHelper :: save('new_backup', JText::_('CREATE_A_BACKUP_COPY'));
			JToolBarHelper :: trash('clear', JText::_('CLEAR'),false);
			//JToolBarHelper :: save('fix', JText::_('FIX_OF_TABLE'));

		parent :: display($tpl);
	}

}

?>