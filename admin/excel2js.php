<?php defined( "_JEXEC" ) or die( "Restricted access" );
if(!defined("DS")){
    define("DS",DIRECTORY_SEPARATOR);
}

if (!JFactory::getUser()->authorise("core.manage", "com_excel2js"))
{
	return JError::raiseWarning(404, JText::_("JERROR_ALERTNOAUTHOR"));
}

$controller	= JControllerLegacy::getInstance("Excel2js");
$controller->execute(JFactory::getApplication()->input->get("task"));
$controller->redirect();