<?php

defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.model');


class Excel2jsModelSupport extends JModelLegacy
{
    public $pagination;

    function __construct()
    {
        parent:: __construct();
        $this->order_id = $this->getOrderId();
        $this->app = JFactory::getApplication();
        $this->input = $this->app->input;
        if ($this->input->cookie->get('response_email')) {
            $this->email = $this->input->cookie->get('response_email', '', 'string');
        }
        if ($this->input->post->get('email')) {
            $this->email = $this->input->post->get('email', '', 'string');
            $this->input->cookie->set('response_email', $this->email, time() + 365 * 3600 * 24);
        }
    }

    function getOrderId()
    {
        return 000000;
    }

    function getChangeList()
    {
        return 'Новости от разработчика недоступны';
    }

    function getData()
    {
        $json = '{"support":"Cracked","version":"3.1.6"}';
        return json_decode($json);
    }

    function send_message()
    {
        JError::raiseWarning('', "Отправка сообщения автору невозможна");
        return false;
    }

    function getMyVersion()
    {
        $xml = JFactory::getXML(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_excel2js' . DS . 'excel2js.xml');
        $version = (string)$xml->version;

        return $version;
    }

    function getLastFile()
    {
        $path = JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'xls' . DS;
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');
        $files = JFolder::files($path);
        $new_array = [];
        foreach ($files as $file) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            if (!in_array($ext, ['xls', 'xlsx', 'csv'])) {
                continue;
            }
            $time = filemtime($path . $file);
            $new_array[$time] = $file;
        }
        krsort($new_array);

        return array_shift($new_array);
    }

    function export_profile()
    {
        require(dirname(__FILE__) . DS . 'config.php');
        $model = new Excel2jsModelConfig();

        return $model->export_profile(true);
    }

    function update()
    {
        JError::raiseWarning('', "Обновление отключено");
        return false;
    }
}
