<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.controller');
// отображение ошибок
ini_set("default_charset","utf-8");

$params = JComponentHelper :: getParams("com_excel2js");
$debug=$params->get('debug',0);

if($debug){
   	ini_set("display_errors","1");
   	ini_set("display_startup_errors","1");
   	ini_set('error_reporting', E_ALL);
}
else{
   ini_set("display_errors","0");
   ini_set("display_startup_errors","0");
}



class Excel2jsController extends JControllerLegacy {

    function __construct() {
        parent :: __construct();
        $GLOBALS['component_name'] =  JText::_('COMPONENT_NAME');
        $this->task = JRequest :: getVar('task', '', '', 'string');
        $this->view = JRequest :: getVar('view', 'excel2js', '', 'string');

        if (!JFactory::getUser()->authorise("core." . $this->view, "com_excel2js")) {
			$this->setRedirect("index.php");
            $app= JFactory::getApplication();
			$app->enqueueMessage(JText::_("JERROR_ALERTNOAUTHOR"), 'warning');
			return false;
		}

        if ($this->task AND !in_array($this->task, $this->getTasks()))
            $this->commonTask();
    }



    function commonTask() {
        $model = $this->getModel($this->view);
        $msg = $model->{$this->task}();
        $this->setRedirect("index.php?option=com_excel2js&view={$this->view}", $msg);
    }

    function get_stat(){
    	if(!file_exists(JPATH_COMPONENT_ADMINISTRATOR.DS.'log.txt'))exit();
    	$mtime=filemtime(JPATH_COMPONENT_ADMINISTRATOR.DS.'log.txt');
		if(time() - $mtime > 30)exit();
		$log=json_decode(file_get_contents(JPATH_COMPONENT_ADMINISTRATOR.DS.'log.txt'));
		$log->last_response=time() - $mtime;
        if($log->cur_row==-1){
            $log->last_response=1;
        }
		echo json_encode($log);
		jexit();
	}

	function abort(){
    	file_put_contents(JPATH_COMPONENT_ADMINISTRATOR.DS.'abort.txt',1);
		jexit();
	}
    function abort_yml(){
    	file_put_contents(JPATH_COMPONENT_ADMINISTRATOR.DS.'yml-abort.txt',1);
		jexit();
	}
    function download(){
    	$model = $this->getModel();
        $msg = $model->download();
		jexit();
	}

    function delete(){
    	$model = $this->getModel();
        $msg = $model->delete();
		jexit();
	}

    function upload(){
       $model = $this->getModel();
       $msg = $model->upload();
       jexit();
    }

    function update_files(){
       $model = $this->getModel();
       $msg = $model->update_files();
       jexit();
    }

    function get_export_stat(){
    	if(!file_exists(JPATH_COMPONENT_ADMINISTRATOR.DS.'export'.DS.'export_log.txt'))exit();
    	$mtime=filemtime(JPATH_COMPONENT_ADMINISTRATOR.DS.'export'.DS.'export_log.txt');
		if(time() - $mtime > 10){
		  $data->notmodified=1;
		  $data->row=0;
		  echo json_encode($data);
          exit();
		}
		echo @file_get_contents(JPATH_COMPONENT_ADMINISTRATOR.DS.'export'.DS.'export_log.txt');
		jexit();
	}
    function display($cachable = false, $urlparams = false) {
		JSubMenuHelper::addEntry(JText::_('IMPORT'), 'index.php?option=com_excel2js',$this->view=='excel2js');
		JSubMenuHelper::addEntry(JText::_('EXPORT'), 'index.php?option=com_excel2js&view=export',$this->view=='export');
		JSubMenuHelper::addEntry(JText::_('CONFIGURATIONS'), 'index.php?option=com_excel2js&view=config',$this->view=='config');
		JSubMenuHelper::addEntry(JText::_('RECOVER'), 'index.php?option=com_excel2js&view=backup',$this->view=='backup');
		JSubMenuHelper::addEntry(JText::_('YML'), 'index.php?option=com_excel2js&view=yml',$this->view=='yml');
		JSubMenuHelper::addEntry(JText::_('VK'), 'index.php?option=com_excel2js&view=vk',$this->view=='vk');
		JSubMenuHelper::addEntry(JText::_('SUPPORT'), 'index.php?option=com_excel2js&view=support',$this->view=='support');
        $doc = JFactory::getDocument();
		$doc->addStyleSheet(JURI::root()."administrator/components/com_excel2js/assets/style.css");
		$doc->addStyleSheet(JURI::root()."administrator/components/com_excel2js/assets/jquery-ui-1.8.17.custom.css");


		if(version_compare(JVERSION,3,">=")){
            JHtml::_('jquery.framework');
            //JHtml::_('bootstrap.framework');
            $doc->addScript('components/com_excel2js/js/jquery-ui.min.js');
        }
        else{
            $doc->addScript('components/com_excel2js/js/jquery-1.7.1.min.js');
    		$doc->addScript('components/com_excel2js/js/jquery-ui.min.js');
    		/*$doc->addScript('https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js');
    		$doc->addScript('https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/jquery-ui.min.js');
            $doc->addStyleSheet("https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.2/themes/smoothness/jquery-ui.css");*/
            $doc->addScriptDeclaration ( 'jQuery.noConflict();' );
        }
        $doc->addScript('components/com_excel2js/js/jquery.form.js');
        $doc->addScript('components/com_excel2js/js/jquery.tablesorter.min.js');
        parent :: display($cachable = false, $urlparams = false);
    }

    function save_config_yml_import(){
        $model = $this->getModel('yml');
        $model->save_config_yml_import();
        jexit();
    }
    function save_config_yml_export(){
        $model = $this->getModel('yml');
        $model->save_config_yml_export();
        jexit();
    }



    function get_yml_stat(){
    	if(!file_exists(JPATH_COMPONENT_ADMINISTRATOR.DS.'yml-log.txt'))exit();
    	$mtime=filemtime(JPATH_COMPONENT_ADMINISTRATOR.DS.'yml-log.txt');
		if(time() - $mtime > 30)exit();
		$log=json_decode(file_get_contents(JPATH_COMPONENT_ADMINISTRATOR.DS.'yml-log.txt'));
		$log->last_response=time() - $mtime;
		echo json_encode($log);
		jexit();
	}

    function get_yml_export_stat(){
    	if(!file_exists(JPATH_COMPONENT_ADMINISTRATOR.DS.'yml-export-log.txt'))exit();
    	$mtime=filemtime(JPATH_COMPONENT_ADMINISTRATOR.DS.'yml-export-log.txt');
		if(time() - $mtime > 5)exit();
		$log=json_decode(file_get_contents(JPATH_COMPONENT_ADMINISTRATOR.DS.'yml-export-log.txt'));
		$log->last_response=time() - $mtime;
		echo json_encode($log);
		jexit();
	}

    function auth(){
        $model = $this->getModel('vk');
        echo $model->auth();
        jexit();
    }

    function get_vk_categories(){
        $model = $this->getModel('vk');
        echo $model->get_vk_categories();
        jexit();
    }
    function vk_categories_save(){
        $model = $this->getModel('vk');
        echo $model->vk_categories_save();
        jexit();
    }

    function save_config_vk(){
        $model = $this->getModel('vk');
        echo $model->save_config_vk();
        jexit();
    }

    function vk_export(){
        $model = $this->getModel('vk');
        echo $model->vk_export();
        jexit();
    }

    function vk_get_products(){
        $model = $this->getModel('vk');
        echo $model->vk_get_products();
        jexit();
    }

    function vk_delete_products(){
        $model = $this->getModel('vk');
        echo $model->vk_delete_products();
        jexit();
    }

	function send_captcha(){
        $model = $this->getModel('vk');
        echo $model->vk_export(true);
        jexit();
    }
}
?>