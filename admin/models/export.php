<?php

defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.model');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.archive');
ini_set('log_errors', 'On');
ini_set('error_log', JPATH_ROOT . DS . 'components' . DS . 'com_excel2js' . DS . 'export_errors.txt');

Use Joomla\Archive\Archive;
Use Joomla\Utilities\ArrayHelper;

require_once(dirname(__FILE__) . DS . "updateTable.php");

class Excel2jsModelExport extends JModelLegacy
{
    public $category;

    function __construct()
    {
        parent:: __construct();

        $params = JComponentHelper:: getParams("com_excel2js");
        $this->app = JFactory::getApplication();
        $this->input = $this->app->input;
        $this->browser_timeout = $params->get('timeout', 60) ? $params->get('timeout', 60) : 60;

        $this->export_query_size = (int)$params->get('export_query_size', 1000);
        if (!$this->export_query_size) $this->export_query_size = 1000;

        $this->config_table = new updateTable("#__excel2js", "id", 1);
        $this->config = $this->getConfig();
        $this->active = $this->getActive();
        $this->part = $this->input->get('part', 0, 'int');
        $this->csv = $this->input->get('csv', 0, 'int');
        $this->csv_field_delimiter = $params->get('csv_field_delimiter', ';');
        $this->csv_row_delimiter = $params->get('csv_row_delimiter', '');
        $this->csv_convert = $params->get('csv_convert', 1);
        $this->row_limit = $this->input->get('row_limit', 0, 'int') - 1;
        $this->product_status = $this->input->get('product_status', -1, 'int');

        $this->manufacturers = (array)$_POST['manufacturer_id'];
        $this->manufacturers = ArrayHelper::toInteger($this->manufacturers);
        $this->manufacturers = implode(",", $this->manufacturers);
        $this->get_version();

        if (is_array(@$_REQUEST['category'])) {
            if (count($_REQUEST['category']) == 1) {
                $this->category = (int)$_REQUEST['category'][0];
            } elseif (count($_REQUEST['category']) > 1) {
                $this->category = (array)$_REQUEST['category'];
                $this->category = ArrayHelper::toInteger($this->category);
            } else {
                $this->category = 0;
            }
        } else {
            $this->category = (int)@$_REQUEST['category'];
        }


        $this->order = $this->input->get('order', 'category_id', 'string');


        $this->letters = range('A', 'Z');
        $leters2 = range('A', 'H');
        foreach ($leters2 as $l2) {
            foreach ($this->letters as $letter)
                $this->letters[] = $l2 . $letter;
        }
    }

    function getConfig()
    {
        $this->_db->setQuery("SELECT id FROM #__excel2js WHERE default_profile = 1");
        $id = $this->_db->loadResult();
        if (!$id) {
            $this->_db->setQuery("UPDATE #__excel2js SET default_profile = 1 LIMIT 1");
            $this->_db->execute();
            $this->config_table->load(1, 'default_profile');
        } else
            $this->config_table->load($id);

        $this->active_fields = $this->config_table->active;
        $config = unserialize($this->config_table->config);
        $config->profile_name = $this->config_table->profile;
        $config->profile_id = $this->config_table->id;
        if (!$config->language) {
            $languages = $this->getLanguages();
            if (!in_array('ru-Ru', $languages)) {
                $config->language = current($languages)->language;
            }
        }
        if (!@$config->currency_rate) {
            $config->currency_rate = 1;
        }
        $this->_db->setQuery("SELECT f.name,f.extra_id, `name_{$config->language}` as attr_name
							  FROM #__excel2js_fields as f
		                      LEFT JOIN #__jshopping_attr as a ON a.attr_id=f.extra_id
							  WHERE f.id IN ($this->active_fields) AND f.type = 'independ' ORDER BY f.id");
        $this->independ = $this->_db->loadObjectList('extra_id');

        $this->_db->setQuery("SELECT f.name,f.extra_id, `name_{$config->language}` as attr_name
							  FROM #__excel2js_fields as f
		                      LEFT JOIN #__jshopping_attr as a ON a.attr_id=f.extra_id
							  WHERE f.id IN ($this->active_fields) AND f.type = 'depend' ORDER BY f.id");
        $this->depend = $this->_db->loadObjectList('extra_id');

        $this->_db->setQuery("SELECT f.name
							  FROM #__excel2js_fields as f
							  WHERE f.id IN ($this->active_fields) AND f.type = 'depend2' ORDER BY f.id");
        $depend2 = $this->_db->loadColumn();

        $this->_db->setQuery("SELECT f.name,CONCAT('attr_',extra_id) as attr_field
							  FROM #__excel2js_fields as f
							  WHERE f.id IN ($this->active_fields) AND f.type = 'depend' ORDER BY f.id");
        $depend_attr = $this->_db->loadObjectList();

        $this->depend2_new = [];
        $depend_as = [];
        foreach ($depend2 as $d2f) {
            $this->depend2_new[$d2f] = str_replace('depend_', '', $d2f);
            $depend_as[] = str_replace('depend_', '', $d2f) . ' as ' . $d2f;
        }

        foreach ($depend_attr as $key => $v) {
            $this->depend2_new[] = $v->attr_field;
            $depend_as[] = $v->attr_field . ' as ' . $v->name;
        }

        $this->uniq = "CONCAT(" . implode(",'_',", $this->depend2_new) . ")";


        $this->depend2_select = implode(", ", $depend_as) . ", $this->uniq as uniq";

        $this->_db->setQuery("SELECT f.name,f.extra_id, `name_{$config->language}` as attr_name, a.type
							  FROM #__excel2js_fields as f
		                      LEFT JOIN #__jshopping_products_extra_fields as a ON a.id=f.extra_id
							  WHERE f.id IN ($this->active_fields) AND f.type = 'extra' ORDER BY f.id");
        $this->extra = $this->_db->loadObjectList('extra_id');

        if (count($this->independ))
            foreach ($this->independ as &$i) {
                $this->_db->setQuery("SELECT value_id,`name_{$config->language}` as value
                                      FROM #__jshopping_attr_values
                                      WHERE attr_id = '$i->extra_id'");
                $i->values = array_combine($this->_db->loadColumn(0), $this->_db->loadColumn(1));
            }

        if (count($this->depend))
            foreach ($this->depend as &$d) {
                $this->_db->setQuery("SELECT value_id,`name_{$config->language}` as value
                                      FROM #__jshopping_attr_values
                                      WHERE attr_id = '$d->extra_id'");
                $d->values = array_combine($this->_db->loadColumn(0), $this->_db->loadColumn(1));
            }

        if (count($this->extra))
            foreach ($this->extra as &$e) {
                if ($e->type) continue;
                $this->_db->setQuery("SELECT id,`name_{$config->language}` as value
                                      FROM #__jshopping_products_extra_field_values
                                      WHERE field_id = '$e->extra_id'");
                $e->values = array_combine($this->_db->loadColumn(0), $this->_db->loadColumn(1));
            }

        return $config;
    }

    function getLanguages()
    {
        $this->_db->setQuery("SELECT language,name FROM #__jshopping_languages ORDER BY ordering, name");

        return $this->_db->loadObjectList('language');
    }

    function getActive()
    {
        $this->active_fields = $this->active_fields ? $this->active_fields : 1;
        $query = "SELECT *
				FROM #__excel2js_fields
				WHERE id IN({$this->active_fields})
				ORDER BY FIELD(id,{$this->active_fields})";
        $this->_db->setQuery($query);
        $list = $this->_db->loadObjectList('name');
        $i = 0;
        foreach ($list as $key => $l) {
            $i++;
            $list[$key]->ordering = $i;
        }

        return $list;
    }

    function get_version()
    {
        $xml = simplexml_load_file(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_jshopping' . DS . 'jshopping.xml');
        $version = (string)$xml->version;
        if (substr($version, 0, 1) == 3) {
            $this->old_version = true;
        } else {
            $this->old_version = false;
        }
    }

    function change_profile()
    {
        $profile_id = $this->input->get('profile_id', '', 'int');
        $this->_db->setQuery("UPDATE #__excel2js SET default_profile = 0");
        $this->_db->execute();
        $this->config_table->reset();
        $this->config_table->id = $profile_id;
        $this->config_table->default_profile = 1;
        $this->config_table->update();
    }

    function getManufacturers()
    {
        try {
            $this->_db->setQuery("SELECT manufacturer_id, `name_{$this->config->language}` as manufacturer_name FROM #__jshopping_manufacturers ORDER BY `name_{$this->config->language}`");

            return $this->_db->loadObjectList();
        } catch (Exception $e) {
            return false;
        }
    }

    function getCategoryList($parent_id = 0, $prefix = '|_ ')
    {
        if ($parent_id == 0) $this->list[] = JHTML::_('select.option', '0', JText::_('ALL'), 'category_id', 'category_name');
        try {
            $this->_db->setQuery("SELECT  category_id, `name_{$this->config->language}` as category_name,
              (SELECT COUNT(product_id) FROM #__jshopping_products_to_categories as pc WHERE pc.category_id = c.category_id) as products
  								  FROM #__jshopping_categories as c
  								  WHERE category_parent_id ='$parent_id'

  								  ORDER BY category_id");
            $categories = $this->_db->loadObjectList('category_id');
        } catch (Exception $e) {
            return false;
        }


        if (!$categories)
            return false;

        foreach ($categories as $id => $cat) {
            $this->list[] = JHTML::_('select.option', $id, (!$parent_id ? '' : $prefix) . $cat->category_name . " ($cat->products)", 'category_id', 'category_name');
            $this->getCategoryList($id, '&nbsp;.&nbsp;' . $prefix);
        }

        return $this->list;
    }

    function getCategoryChildrenById($parent_id = 0)
    {

        $this->_db->setQuery("SELECT category_id
								  FROM #__jshopping_categories
								  WHERE category_parent_id ='$parent_id'
								  ORDER BY category_id");
        $categories = $this->_db->loadColumn();

        if (!$categories)
            return false;

        foreach ($categories as $cat) {
            $this->new_tree[] = $cat;
            $this->getCategoryChildrenById($cat);
        }

        return true;
    }

    function profile_list()
    {
        $list = $this->_getList("SELECT id, profile FROM #__excel2js ORDER BY id");

        return $list;
    }

    function get_export_file()
    {
        $this->part++;
        if (!$this->csv) {
            $mtime = filemtime(JPATH_BASE . "/components/com_excel2js/export/export" . (date("Y_m_d")) . "_part{$this->part}.xls");
            if (time() - $mtime < 60) {
                $resp = new stdClass();
                $resp->text = "{$this->part}." . JText::_('DOWNLOAD_EXPORTED_DATA') . " - " . JText::_('PART') . " {$this->part}";
                $resp->link = JURI::base() . "/components/com_excel2js/export/export" . (date("Y_m_d")) . "_part{$this->part}.xls";
                $resp->finish = file_get_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . "finish.txt");
            } else {
                $resp = new stdClass();
                $resp->text = 'No';
                $resp->finish = 0;
            }
            echo json_encode($resp);
            exit();
        }
    }

    function export()
    {
        $this->log = new stdClass();
        $this->log->cat = 0;
        $this->log->row = 0;
        $parent_categories_ids = [];
        $this->check();
        file_put_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . "finish.txt", 0);
        $this->setCookies();
        $this->timeout = ini_get('max_execution_time') - 10;

        $this->mem_limit = substr(ini_get('memory_limit'), 0, -1) * 1024 * 1024 * $this->input->get('memory_limit', 0.7, 'float');
        $this->mem_limit = $this->mem_limit > 100 * 1024 * 1024 ? 100 * 1024 * 1024 : $this->mem_limit;
        if (ini_get('memory_limit') == -1) $this->mem_limit = 120 * 1024 * 1024;
        $this->start_time = $this->last_upd = time();

        if (!$this->csv) {
            require_once(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'libraries' . DS . 'PHPExcel.php');
            require_once(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'libraries' . DS . 'PHPExcel' . DS . 'IOFactory.php');
            $cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_in_memory_serialized;
            $cacheSettings = ['memoryCacheSize' => '8MB'];
            PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

            $this->objPHPExcel = new PHPExcel();
            $this->objPHPExcel->setActiveSheetIndex(0);
            $this->getActiveSheet = $this->objPHPExcel->getActiveSheet();
            $this->getActiveSheet->setShowSummaryBelow(false);
        } else {
            file_put_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . 'export' . (date("Y_m_d")) . '.csv', '');
            $this->csv_file = fopen(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . 'export' . (date("Y_m_d")) . '.csv', 'a');
        }

        $this->row = 1;

        if (in_array($this->config->price_template, [1, 2, 3, 4, 8])) {
            if (!$this->part) {
                $this->_db->setQuery("SELECT *, `name_{$this->config->language}` as category_name
							  FROM #__jshopping_categories
							  WHERE category_parent_id=0
							  ORDER BY $this->order");


                $tree = $this->_db->loadObjectList();
                $tree = $this->shift($tree);


                foreach ($tree as $key => $obj) {
                    $obj->level = 0;
                    switch ($this->config->price_template) {
                        case 1:
                            $obj->path = $key . '.';
                            break;
                        case 2:
                            $obj->path = '';
                            break;
                        case 3:
                            $obj->path = '';
                            break;
                        case 4:
                            $obj->path = $key;
                            break;
                    }
                    $this->getChildren($obj);
                }
                $this->print_headers();

                @$this->log->cat = 0;
                $this->log->product = 0;
                $this->log->start_time = time();
                $this->log->status = JText::_('COLLECTING_DATA');
                $this->log->currant_index = 0;
            } else {
                $this->new_tree = unserialize(file_get_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . 'category_bak.txt'));
                $this->log = unserialize(file_get_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . 'log_bak.txt'));
                $this->log->status = JText::_('COLLECTING_DATA');
                $this->print_new_headers();

            }

            if ($this->category) {
                $parent_categories_ids = [];
                $this->_db->setQuery("SELECT category_parent_id FROM #__jshopping_categories WHERE category_id = $this->category");
                $category_parent_id = $this->_db->loadResult();
                while ($category_parent_id > 0) {
                    $parent_categories_ids[] = $category_parent_id;
                    $this->_db->setQuery("SELECT category_parent_id FROM #__jshopping_categories WHERE category_id = $category_parent_id");
                    $category_parent_id = $this->_db->loadResult();
                }
            }
            $num_categories = count($this->new_tree);
            for ($this->log->currant_index; $this->log->currant_index < $num_categories; $this->log->currant_index++) {
                $category = $this->new_tree[$this->log->currant_index];
                if ($this->category) {
                    if (in_array($category->category_id, $parent_categories_ids)) {
                        $this->exportCategory($category);
                        continue;
                    } elseif ($category->category_id == $this->category) {
                        $this->log->currant_path = @$category->path;
                    } elseif (isset($this->log->currant_path)) {
                        $path_len = strlen($this->log->currant_path);
                        if (substr($category->path, 0, $path_len) != $this->log->currant_path)
                            continue;
                    } else {
                        continue;
                    }
                }
                $this->exportCategory($category);
                $start = @$this->log->currant_product_index[$this->log->currant_index] ? $this->log->currant_product_index[$this->log->currant_index] : 0;
                $products = $this->getProductsByCategory($category->category_id, $start);
                $total_products = $this->products_total[$category->category_id];

                if ($total_products > $start + 1000) {
                    $this->exportProducts($products);
                    $start += 1000;
                    while ($start < $total_products) {
                        $products = $this->getProductsByCategory($category->category_id, $start);
                        $this->exportProducts($products);
                        $start += 1000;
                    }
                } else {
                    if (!$products) continue;
                    else $this->exportProducts($products);
                }

            }
        } elseif (in_array($this->config->price_template, [6, 7])) {
            if (!$this->part) {
                $this->print_headers();
                @$this->log->cat = 0;
                $this->log->product = 0;

                $this->log->start_time = time();
                $start = 0;

            } else {
                $this->row--;
                $this->log = unserialize(file_get_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . 'log_bak.txt'));
                $start = @$this->log->currant_product_index;

            }
            $this->log->status = JText::_('COLLECTING_DATA');

            for (; ;) {

                $products = $this->getProducts($start);

                if (!count($products)) {
                    break;
                }
                $this->exportProducts($products);
                $start += $this->export_query_size;

                $this->updateStat();
            }
        }

        if (!$this->csv) {
            $this->log->status = JText::_('CREATING_EXCEL_FILE');
            $this->last_upd -= 2;
            $this->updateStat();
            $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel5');
            $objWriter->setPreCalculateFormulas(false);
            $this->part++;
            $objWriter->save(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . 'export' . (date("Y_m_d")) . '_part' . $this->part . '.xls');
            $resp = new stdClass();
            $resp->text = "{$this->part}." . JText::_('DOWNLOAD_EXPORTED_DATA') . " ($this->row " . JText::_('ROWS') . ") - " . JText::_('PART') . " {$this->part}";
            $resp->link = JURI::base() . "/components/com_excel2js/export/export" . (date("Y_m_d")) . "_part{$this->part}.xls";
            $resp->finish = 1;
            $this->log->status = JText::_('EXPORT_FINISHED');
            $this->last_upd -= 2;
            $this->updateStat();
            file_put_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . "finish.txt", 1);
            echo json_encode($resp);

            /*}
            else{
                $resp->text="Скачать Экспортированные данные ($this->row строк)";
                $resp->link=JURI::base()."/components/com_excel2js/export/export".(date("Y_m_d")).".xls";
                $resp->finish=1;
                echo json_encode($resp);

                $objWriter->save(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_excel2js'.DS.'export'.DS.'export'.(date("Y_m_d")).'.xls');
            }*/
        } else {
            fclose($this->csv_file);
            $resp = new stdClass();
            $resp->text = "" . JText::_('DOWNLOAD_EXPORTED_DATA') . " ($this->row строк)";
            $resp->link = JURI::base() . "/components/com_excel2js/export/export" . (date("Y_m_d")) . ".csv";
            $resp->finish = 1;
            echo json_encode($resp);
        }


        $this->last_upd -= 2;
        $this->updateStat();
        jexit();
    }

    function check()
    {
        return true;
    }

    function setCookies()
    {
        $inputCookie = JFactory::getApplication()->input->cookie;
        $inputCookie->set('c_csv', @$_POST['csv'], time() + (365 * 24 * 3600));
        $inputCookie->set('c_memory_limit', @$_POST['memory_limit'], time() + (365 * 24 * 3600));
        $inputCookie->set('c_order', @$_POST['order'], time() + (365 * 24 * 3600));
        $inputCookie->set('c_product_status', @$_POST['product_status'], time() + (365 * 24 * 3600));
        $inputCookie->set('c_row_limit', @$_POST['row_limit'], time() + (365 * 24 * 3600));

        $inputCookie->set('c_category', serialize(@$_POST['category']), time() + (365 * 24 * 3600));
        $inputCookie->set('c_man', serialize(@$_POST['manufacturer_id']), time() + (365 * 24 * 3600));
    }

    function shift($array)
    {
        $i = 1;
        $array2 = [];
        foreach ($array as $val) {
            $array2[$i] = $val;
            $i++;
        }

        return $array2;
    }

    function getChildren($obj)
    {
        if (!@$obj->category_id) return false;
        $this->_db->setQuery("SELECT *, `name_{$this->config->language}` as category_name
							  FROM #__jshopping_categories
							  WHERE category_parent_id=$obj->category_id
							  ORDER BY $this->order");


        $result = $this->_db->loadObjectList();
        $this->new_tree[] = $obj;
        if (count($result)) {
            $result = $this->shift($result);
            foreach ($result as $key => $child) {
                switch ($this->config->price_template) {
                    case 1:
                        $child->path = $obj->path . $key . '.';
                        break;
                    case 2:
                        $child->path = $obj->path . $this->config->simbol;
                        break;
                    case 3:
                        $child->path = $obj->path . $this->config->simbol;
                        break;
                    case 4:
                        $child->path = $obj->path . '.' . $key;
                        break;
                }
                $child->level = $obj->level + 1;
                $this->getChildren($child);
            }
        }

        return $obj;

    }

    function print_headers()
    {
        if (!$this->csv) {
            foreach ($this->active as $a) {
                $this->getActiveSheet->setCellValueByColumnAndRow($a->ordering - 1, $this->row, JText::_($a->title));
            }
        } else {
            $headers = [];
            foreach ($this->active as $a) {
                $headers[$a->ordering] = JText::_($a->title);
            }
            $this->print_csv($headers);
        }
    }

    function print_csv(& $row)
    {
        if (empty($row)) {
            return false;
        }
        $row = (array)$row;
        for ($i = 1; $i <= count($this->active); $i++) {
            if (!isset($row[$i])) {
                $row[$i] = '';
            }
            $row[$i] = str_replace($this->csv_field_delimiter, '%3B', $row[$i]);
            $row[$i] = str_replace("\n", '', $row[$i]);
            $row[$i] = str_replace("\r", '', $row[$i]);
        }
        ksort($row);
        if ($this->csv_convert)
            @fwrite($this->csv_file, iconv("UTF-8", "WINDOWS-1251", $this->csv_row_delimiter . implode($this->csv_field_delimiter, $row)) . $this->csv_row_delimiter . "\n");
        else
            fwrite($this->csv_file, $this->csv_row_delimiter . implode($this->csv_field_delimiter, $row) . $this->csv_row_delimiter . "\n");
        unset($row);
    }

    function print_new_headers()
    {
        $this->print_headers();

        $curant_category_id = $this->new_tree[$this->log->currant_index]->category_id;
        $parent_categories_ids = [];
        $this->_db->setQuery("SELECT category_parent_id FROM #__jshopping_categories WHERE category_id = $curant_category_id");
        $category_parent_id = $this->_db->loadResult();
        while ($category_parent_id > 0) {
            $parent_categories_ids[] = $category_parent_id;
            $this->_db->setQuery("SELECT category_parent_id FROM #__jshopping_categories WHERE category_id = $category_parent_id");
            $category_parent_id = $this->_db->loadResult();
        }

        foreach ($this->new_tree as $category) {
            if (in_array($category->category_id, $parent_categories_ids)) {
                $this->exportCategory($category);
            }
        }

    }

    function exportCategory($category)
    {
        $fields_cat = ['category_image' => 'image_name', 'category_publish' => 'product_publish', 'category_template' => 'product_template', 'category_add_date' => 'product_date_added'];

        $this->log->current_cat = $category->category_name;
        $this->log->cat++;
        $this->log->row++;

        $cells = array_fill(1, count($this->active), '');
        foreach ($fields_cat as $cat_field => $original_field) {
            if (isset($this->active[$original_field])) {
                $cells[$this->active[$original_field]->ordering] = $category->$cat_field;
            }
        }


        switch ($this->config->price_template) {
            case 1:
                $cells[$this->config->cat_col] = $category->path . $category->category_name;
                break;
            case 2:
                $cells[$this->config->cat_col] = $category->path . $category->category_name;
                break;
            case 3:
                $cells[$this->config->cat_col] = $category->category_name . $category->path;
                break;
            case 4:

                if (isset($this->active['path'])) {
                    $cells[$this->config->cat_col] = $category->category_name;
                    $cells[$this->active['path']->ordering] = $category->path;
                } else {
                    $cells[$this->config->cat_col] = $category->path . $category->category_name;
                }
                break;
            case 5:
                echo JText::_('WRONG_METHOD');
                exit();
                break;
            case 8:
                $cells[$this->config->cat_col] = $category->category_name;
                break;
        }
        $this->row++;

        if (!$this->csv) {
            foreach ($cells as $col => $value) {
                if (empty($col)) continue;
                $cell_name = $this->letters[$col - 1] . $this->row;

                if ($this->config->price_template == 8) {
                    $this->getActiveSheet->getRowDimension($this->row)->setOutlineLevel($category->level);
                    @$this->stat->currant_level = $category->level;
                }

                @$this->getActiveSheet->getCellByColumnAndRow($col - 1, $this->row)->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
                if ($this->config->cat_col == $col)
                    $this->getActiveSheet->getStyle($cell_name)->getFont()->setBold(true);
            }
        } else
            $this->print_csv($cells);

    }

    function getProductsByCategory($category_id, $start = 0)
    {
        $where = is_array($this->category) ? " WHERE pc.category_id IN (" . implode(",", $this->category) . ")" : " WHERE pc.category_id = $category_id ";

        $where2 = $this->product_status > -1 ? " HAVING p.product_publish = $this->product_status" : "";
        $man_filter = $this->manufacturers ? " AND p.product_manufacturer_id IN($this->manufacturers)" : "";


        $this->_db->setQuery("SELECT SQL_CALC_FOUND_ROWS p.*,
                                    p.`name_{$this->config->language}` as name,
                                    p.`alias_{$this->config->language}` as alias,
                                    p.`short_description_{$this->config->language}` as short_description,
                                    p.`description_{$this->config->language}` as description,
                                    p.`meta_description_{$this->config->language}` as meta_description,
                                    p.`meta_keyword_{$this->config->language}` as meta_keyword,
                                    p.`meta_title_{$this->config->language}` as meta_title,
                                    pm.`name_{$this->config->language}` as mf_name,

                                    currency_code as currency,pc.category_id,
                                    tax_value as product_tax_id,
                                    dt.`name_{$this->config->language}` as delivery_times,
                                    l." . ($this->old_version ? "`name`" : "`name_{$this->config->language}`") . " as label_id,
                                    u.`name_{$this->config->language}` as units,
                                    pc.product_ordering
		   						 FROM #__jshopping_products as p

		                         LEFT JOIN #__jshopping_products_to_categories as pc ON p.product_id = pc.product_id
		                         LEFT JOIN #__jshopping_manufacturers as pm ON p.product_manufacturer_id = pm.manufacturer_id
		                         LEFT JOIN #__jshopping_currencies as c ON c.currency_id = p.currency_id
		                         LEFT JOIN #__jshopping_taxes as t ON t.tax_id = p.product_tax_id
		                         LEFT JOIN #__jshopping_delivery_times as dt ON dt.id = p.delivery_times_id
		                         LEFT JOIN #__jshopping_product_labels as l ON l.id = p.label_id
		                         LEFT JOIN #__jshopping_unit as u ON u.id = p.basic_price_unit_id

                                 $where
                                 $man_filter
								 GROUP BY p.product_id
                                 $where2
								 ORDER BY pc.product_ordering", $start, 1000);


        $result = $this->_db->loadObjectList();
        $this->_db->setQuery("SELECT FOUND_ROWS()");
        $this->products_total[$category_id] = $this->_db->loadResult();


        return $result;
    }

    function exportProducts($products)
    {
        foreach ($products as $key => $p) {

            @$this->log->row++;
            $this->log->product++;
            if (in_array($this->config->price_template, [6, 7])) {
                @$this->log->currant_product_index++;
            } else {
                @$this->log->currant_product_index[$this->log->currant_index]++;
            }
            $this->log->current_product = $p->name;

            @$this->row++;

            if ($this->config->price_template == 8) {
                $this->getActiveSheet->getRowDimension($this->row)->setOutlineLevel($this->stat->currant_level + 1);
            }


            if ($this->csv)
                $csv_product = [];

            if ((isset($this->active['image_name']) OR isset($this->active['image_title'])) AND @$p->product_id) {

                $this->_db->setQuery("SELECT image_name, `name` as image_title
                                             FROM #__jshopping_products_images
											 WHERE product_id = $p->product_id
											 ORDER BY ordering");
                $p->image_name = @implode("|", $this->_db->loadColumn(0));
                $p->image_title = @implode("|", $this->_db->loadColumn(1));
            }
            $depend_counter = 0;
            foreach ($this->active as $a) {
                if (!$p->product_id) continue;
                $extra_price_ordering = false;
                switch ($a->name) {
                    case 'short_description':
                    case 'description':
                        if ($this->csv) {
                            $p->{$a->name} = str_replace("\n", '', $p->{$a->name});
                            $p->{$a->name} = str_replace("\r", '', $p->{$a->name});
                        }
                        break;
                    case 'path':
                        if ($this->config->price_template == 6)
                            $p->{$a->name} = $p->path;
                        elseif ($this->config->price_template == 7) {
                            $p->{$a->name} = $this->createCategoryPath($p->path);
                        }
                        break;
                    case 'product_price':
                        $p->{$a->name} = round($p->product_price / $this->config->currency_rate, 2);
                        break;
                }


                switch ($a->type) {
                    case 'price':
                        $spec_price_data = json_decode($a->extra_id);

                        $this->_db->setQuery("SELECT discount FROM #__jshopping_products_prices
                                                  WHERE  	product_id = $p->product_id
                                                  AND product_quantity_start = $spec_price_data->product_quantity_start
                                                  AND product_quantity_finish = $spec_price_data->product_quantity_finish");
                        $discount = round($this->_db->loadResult(), 2);
                        if ($discount) {
                            $p->{$a->name} = $discount . "%";
                        }

                        break;

                    case 'extra':
                        $extra_field = $this->extra[$a->extra_id];
                        $extra_field_name = 'extra_field_' . $a->extra_id;
                        if (!@$p->$extra_field_name) {
                            break;
                        }
                        if ($extra_field->type) {
                            $p->{$a->name} = $p->$extra_field_name;
                        } else {
                            $extra_values = explode(",", $p->$extra_field_name);
                            foreach ($extra_values as &$v) {
                                $v = @$extra_field->values[$v];
                            }
                            $p->{$a->name} = implode("|", $extra_values);
                        }

                        break;

                    case 'independ':
                        $independ_field = $this->independ[$a->extra_id];
                        $this->_db->setQuery("
                              SELECT *
                              FROM #__jshopping_products_attr2
                              WHERE product_id = '$p->product_id'
                              AND attr_id = '$a->extra_id'");
                        $independ_field_data = $this->_db->loadObjectList();
                        if (!count($independ_field_data)) {
                            break;
                        }
                        $independ_field_array = [];
                        foreach ($independ_field_data as $ifd) {
                            if (isset($independ_field->values[$ifd->attr_value_id])) {
                                $independ_field_array[] = $independ_field->values[$ifd->attr_value_id] . ($ifd->addprice > 0 ? ";" . $ifd->price_mod . round($ifd->addprice, 2) : '');
                            }

                        }
                        $p->{$a->name} = implode("|", $independ_field_array);
                        break;

                    case 'depend':
                        if ($depend_counter) {
                            break;
                        }
                        $depend_counter++;
                        if (count($this->depend2_new)) {
                            $this->_db->setQuery("
                                SELECT $this->depend2_select
                                FROM #__jshopping_products_attr
                                WHERE product_id = '$p->product_id'
                                GROUP BY uniq
                                ");

                            $rows = $this->_db->loadObjectList('uniq');

                            if (!$rows) {
                                break;
                            }
                            foreach ($rows as &$r) {
                                if (@$r->depend_ext_attribute_product_id) {
                                    $r->depend_ext_attribute_product_id = $this->get_depended_images($r->depend_ext_attribute_product_id);
                                }

                                foreach ($this->depend as $d) {
                                    $this->_db->setQuery("SELECT DISTINCT `attr_{$d->extra_id}`
                                      FROM #__jshopping_products_attr
                                      WHERE $this->uniq = '$r->uniq' AND product_id = '$p->product_id'
                                      ORDER BY `attr_{$d->extra_id}`
                                      ");

                                    $attrs = $this->_db->loadColumn();


                                    if (!$attrs) continue;

                                    foreach ($attrs as &$ai) {
                                        if (isset($d->values[$ai])) {
                                            $ai = $d->values[$ai];
                                        }

                                    }
                                    $r->{$d->name} = implode("|", $attrs);

                                }

                            }

                            $current_row = array_shift($rows);

                            foreach ($current_row as $current_field => $current_field_value) {
                                if ($current_field == 'depend_price') {
                                    $current_field_value = round($current_field_value / $this->config->currency_rate, 2);
                                }
                                if ($current_field == 'uniq') {
                                    continue;
                                }
                                if (!isset($this->active[$current_field])) {
                                    continue;
                                }
                                if (!in_array($current_field, ['depend_price', 'depend_count', 'depend_ean', 'depend_weight', 'depend_old_price', 'depend_buy_price', 'depend_ext_attribute_product_id'])) {
                                    $p->{$current_field} = $current_field_value;
                                } else {
                                    $p->{$current_field} = $current_field_value;
                                    @$this->getActiveSheet->getCellByColumnAndRow($this->active[$current_field]->ordering - 1, $this->row)->setValueExplicit($current_field_value, PHPExcel_Cell_DataType::TYPE_STRING);
                                }


                            }
                            unset($current_row);
                        } else {
                            foreach ($this->depend as $d) {
                                $this->_db->setQuery("SELECT DISTINCT `attr_{$d->extra_id}`
                                      FROM #__jshopping_products_attr
                                      WHERE product_id = '$p->product_id'
                                      ORDER BY `attr_{$d->extra_id}`
                                      ");
                                $attrs = $this->_db->loadColumn();
                                if (!$attrs) continue;
                                foreach ($attrs as &$ai) {
                                    $ai = $d->values[$ai];
                                }
                                $p->{$d->name} = implode("|", $attrs);
                            }
                        }

                        break;

                    case 'free':
                        $this->_db->setQuery("
                            SELECT `name_{$this->config->language}`
                            FROM #__jshopping_products_free_attr as pa
                            LEFT JOIN #__jshopping_free_attr as a ON a.id = attr_id
                            WHERE pa.product_id = '$p->product_id'");
                        $free_attrs = $this->_db->loadColumn();
                        if (count($free_attrs)) {
                            $p->{$a->name} = implode("|", $free_attrs);
                        }
                        break;
                }

                switch ($a->name) {
                    case 'related_products':
                        $this->_db->setQuery("
                                SELECT product_related_id
                                FROM #__jshopping_products_relations
                                WHERE product_id = $p->product_id");

                        $p->related_products = implode('|', $this->_db->loadColumn());
                        break;

                    case 'related_products_sku':
                        $this->_db->setQuery("
                                    SELECT product_ean
            					   	FROM #__jshopping_products_relations as r
            						LEFT JOIN #__jshopping_products as p ON p.product_id = r.product_related_id
            						WHERE r.product_id = $p->product_id");
                        $p->{$a->name} = implode('|', $this->_db->loadColumn());
                        break;

                    case 'product_quantity':
                        if (@$p->unlimited) {
                            $p->product_quantity = -1;
                        }
                        break;
                }


                if (!$this->csv) {
                    if (in_array($a->name, ['product_ean', 'path', 'alias', 'depend_ean'])) {
                        @$this->getActiveSheet->getCellByColumnAndRow($a->ordering - 1, $this->row)->setValueExplicit($p->{$a->name}, PHPExcel_Cell_DataType::TYPE_STRING);

                    } else
                        @$this->getActiveSheet->setCellValueByColumnAndRow($a->ordering - 1, $this->row, $p->{$a->name}, PHPExcel_Cell_DataType::TYPE_STRING);
                } else {
                    @$csv_product[$a->ordering] = $p->{$a->name};
                }
            }

            if ($this->csv) {
                $this->print_csv($csv_product);

                if (!empty($rows)) {
                    foreach ($rows as $current_row) {
                        $csv_product = [];
                        $this->row++;
                        $identity = ['product_id', 'product_ean', 'name'];

                        foreach ($identity as $identity_value) {
                            if (!isset($this->active[$identity_value])) {
                                continue;
                            }
                            $csv_product[($this->active[$identity_value]->ordering) - 1] = $p->$identity_value;
                        }

                        foreach ($current_row as $current_field => $current_field_value) {
                            if ($current_field == 'uniq') {
                                continue;
                            }
                            if ($current_field == 'depend_price') {
                                $current_field_value = round($current_field_value / $this->config->currency_rate, 2);
                            }
                            $csv_product[($this->active[$current_field]->ordering) - 1] = $current_field_value;
                        }

                        $this->print_csv($csv_product);
                    }
                    unset($rows);
                }
            }
            if (!empty($rows) AND !$this->csv) {

                foreach ($rows as $current_row) {
                    $this->row++;
                    $identity = ['product_id', 'product_ean', 'name'];
                    if ($this->config->price_template == 8) {
                        $this->getActiveSheet->getRowDimension($this->row)->setOutlineLevel($this->stat->currant_level + 1);
                    }
                    foreach ($identity as $identity_value) {
                        if (!isset($this->active[$identity_value])) {
                            continue;
                        }
                        $this->getActiveSheet->getCellByColumnAndRow(($this->active[$identity_value]->ordering) - 1, $this->row)->setValueExplicit($p->$identity_value, PHPExcel_Cell_DataType::TYPE_STRING);

                    }

                    foreach ($current_row as $current_field => $current_field_value) {
                        if ($current_field == 'uniq') {
                            continue;
                        }
                        if ($current_field == 'depend_price') {
                            $current_field_value = round($current_field_value / $this->config->currency_rate, 2);
                        }
                        if ($current_field == 'depend_ean') {
                            $this->getActiveSheet->getCellByColumnAndRow(($this->active[$current_field]->ordering) - 1, $this->row)->setValueExplicit($current_field_value, PHPExcel_Cell_DataType::TYPE_STRING);
                        } else {
                            $this->getActiveSheet->setCellValueByColumnAndRow(($this->active[$current_field]->ordering) - 1, $this->row, $current_field_value, PHPExcel_Cell_DataType::TYPE_STRING);
                        }

                    }

                }
                unset($rows);
            }

            $this->updateStat();
            unset($extra_value);
            unset($extra_cart_value);
            unset($extra_price_ordering);
            unset($price);
            unset($p);
            unset($products[$key]);

            if (memory_get_usage(true) > $this->mem_limit OR ($this->row_limit > 0 AND $this->row > $this->row_limit)) {

                unset($products);
                $this->log->status = JText::_('CREATING_EXCEL_FILE');
                $this->last_upd -= 2;
                $this->updateStat();
                $this->part++;
                $objWriter = PHPExcel_IOFactory::createWriter($this->objPHPExcel, 'Excel5');
                $objWriter->setPreCalculateFormulas(false);
                $objWriter->save(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . 'export' . (date("Y_m_d")) . '_part' . $this->part . '.xls');
                $resp = new stdClass();
                $resp->text = "{$this->part}." . JText::_('DOWNLOAD_EXPORTED_DATA') . " ($this->row " . JText::_('ROWS') . ") - " . JText::_('PART') . " {$this->part}";
                $resp->link = JURI::base() . "/components/com_excel2js/export/export" . (date("Y_m_d")) . "_part{$this->part}.xls";
                $resp->finish = 0;
                echo json_encode($resp);
                file_put_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . 'category_bak.txt', serialize(@$this->new_tree));
                file_put_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . 'log_bak.txt', serialize($this->log));
                jexit();
            }

        }
    }

    function createCategoryPath($category_ids)
    {

        $path_array = [];
        $category_ids = explode(",", $category_ids);
        $category_ids = array_unique($category_ids);
        foreach ($category_ids as $category_id) {
            if (isset($this->cat_path_cache[$category_id])) {
                $path_array[] = $this->cat_path_cache[$category_id];
                continue;
            }
            $path = [];
            for (; ;) {
                if (!$category_id) break;
                $this->_db->setQuery("SELECT `name_{$this->config->language}` as name,category_parent_id
    								  FROM #__jshopping_categories
    								  WHERE category_id =  $category_id");

                $data = $this->_db->loadObject();
                $path[] = $data->name;
                $category_id = $data->category_parent_id;

            }

            krsort($path);
            $path_array[] = implode($this->config->level_delimiter, $path);
            @$this->cat_path_cache[$category_id] = implode($this->config->level_delimiter, $path);
            unset($path);
        }

        return implode($this->config->category_delimiter, $path_array);
    }

    function get_depended_images($attribute_product_id)
    {
        $attribute_product_id = (int)$attribute_product_id;
        if (!$attribute_product_id) {
            return '';
        }
        $this->_db->setQuery("SELECT image_name FROM #__jshopping_products_images WHERE product_id = '$attribute_product_id' ORDER BY `ordering`");

        return implode("|", $this->_db->loadColumn());
    }

    function updateStat()
    {
        if (time() - $this->last_upd > 0) {
            $this->last_upd = time();
            $data = new stdClass();
            $data->row = @$this->log->row;
            $data->cat = $this->log->cat;
            $data->product = $this->log->product;
            $data->current_cat = str_replace(',', '.', @$this->log->current_cat);
            $data->current_product = str_replace(',', '.', @$this->log->current_product);
            $data->time = time() - $this->log->start_time;
            $data->mem = $this->get_mem();
            $data->status = @$this->log->status;

            file_put_contents(JPATH_ROOT . DS . 'administrator' . DS . 'components' . DS . 'com_excel2js' . DS . 'export' . DS . 'export_log.txt', json_encode($data));
        }
    }

    function get_mem()
    {
        if (function_exists("memory_get_usage")) {
            $mem_usage = memory_get_usage(true);

            return round($mem_usage / 1048576, 2) . " Mb";
        } else return false;
    }

    function getProducts($start = 0)
    {
        $having = $this->product_status > -1 ? " HAVING p.product_publish = $this->product_status" : "";

        $where = [];
        if ($this->category) {
            if (is_array($this->category)) {
                $where[] = "pc.category_id IN (" . implode(",", $this->category) . ")";
            } else {
                $where[] = "pc.category_id IN (" . $this->_db->Quote($this->category) . ")";
            }
        }

        if ($this->manufacturers) {
            $where[] = "p.product_manufacturer_id IN($this->manufacturers)";
        }

        if (count($where)) {
            $filters = " WHERE " . implode(" AND ", $where);
        } else {
            $filters = "";
        }

        /*$this->_db->setQuery("SELECT COUNT(product_id) FROM #__jshopping_products");
        $total_products = $this->_db->loadResult();*/


        $this->_db->setQuery("SELECT SQL_CALC_FOUND_ROWS p.*,
                                    p.`name_{$this->config->language}` as name,
                                    p.`alias_{$this->config->language}` as alias,
                                    p.`short_description_{$this->config->language}` as short_description,
                                    p.`description_{$this->config->language}` as description,
                                    p.`meta_description_{$this->config->language}` as meta_description,
                                    p.`meta_keyword_{$this->config->language}` as meta_keyword,
                                    p.`meta_title_{$this->config->language}` as meta_title,
                                    pm.`name_{$this->config->language}` as mf_name,

                                    currency_code as currency,
                                    tax_value as product_tax_id,
                                    dt.`name_{$this->config->language}` as delivery_times,
                                    l." . ($this->old_version ? "`name`" : "`name_{$this->config->language}`") . " as label_id,
                                    u.`name_{$this->config->language}` as units,
                                    pc.product_ordering,
                                    GROUP_CONCAT(pc.category_id SEPARATOR ',') as path
		   						 FROM #__jshopping_products as p

		                         LEFT JOIN #__jshopping_products_to_categories as pc ON p.product_id = pc.product_id
		                         LEFT JOIN #__jshopping_manufacturers as pm ON p.product_manufacturer_id = pm.manufacturer_id
		                         LEFT JOIN #__jshopping_currencies as c ON c.currency_id = p.currency_id
		                         LEFT JOIN #__jshopping_taxes as t ON t.tax_id = p.product_tax_id
		                         LEFT JOIN #__jshopping_delivery_times as dt ON dt.id = p.delivery_times_id
		                         LEFT JOIN #__jshopping_product_labels as l ON l.id = p.label_id
		                         LEFT JOIN #__jshopping_unit as u ON u.id = p.basic_price_unit_id
                                 $filters
								 GROUP BY p.product_id
                                 $having
								 ORDER BY pc.product_ordering", $start, $this->export_query_size);


        $result = $this->_db->loadObjectList();

        return @$result;
    }

    function zip()
    {
        $data_file_array = [];
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.archive');
        $parts = $this->input->get('parts', '', 'int') + 1;
        $mark = date("Y_m_d");
        $archive = JPATH_COMPONENT . DS . 'export' . DS . 'export' . $mark . '.zip';
        $Archive_object = new Archive();
        $zip = $Archive_object->getAdapter('zip');

        for ($i = 1; $i <= $parts; $i++) {
            $f['name'] = 'export' . $mark . '_part' . $i . '.xls';
            $f['data'] = file_get_contents(JPATH_COMPONENT . DS . 'export' . DS . $f['name']);
            $data_file_array[] = $f;
        }
        $zip->create($archive, $data_file_array);

        header("Location: " . JURI::base() . "components/com_excel2js/export/" . 'export' . $mark . '.zip');

        exit();

    }

    function getIndependedOptions($attr_id, $variant)
    {
        $this->_db->setQuery("SELECT `name_{$this->config->language}` FROM #__jshopping_attr_values WHERE attr_id = '$attr_id'");
        $options = $this->_db->loadColumn();
        if (count($options) > 2) {
            if (!$variant) {
                return $options[0] . ";+20|" . $options[1] . ";-10|" . $options[2];
            } else {
                return $options[0] . ";=200|" . $options[1] . ";=170|" . $options[2] . ";=150";
            }
        } elseif (count($options) == 2) {
            if (!$variant) {
                return $options[0] . ";+20|" . $options[1];
            } else {
                return $options[0] . ";=200|" . $options[1] . ";=170";
            }
        } else {
            if (!$variant) {
                return "Красный;+20|Синий";
            } else {
                return "Зеленый;=200|Белый;=170";
            }
        }
    }


}