<?php

defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.model');
require_once(dirname(__FILE__) . DS . "updateTable.php");


class Excel2jsModelConfig extends JModelLegacy
{
    public $pagination;

    function __construct()
    {
        parent:: __construct();
        $this->params = JComponentHelper:: getParams("com_excel2js");
        $this->app = JFactory::getApplication();
        $this->input = $this->app->input;
        $this->table = new updateTable("#__excel2js", "id");

        $this->config = $this->getConfig();
        $this->sync();
    }

    function getConfig()
    {

        $this->_db->setQuery("SELECT id FROM #__excel2js WHERE default_profile = 1");
        $id = $this->_db->loadResult();
        if (!$id) {
            $this->_db->setQuery("UPDATE #__excel2js SET default_profile = 1 LIMIT 1");
            $this->_db->execute();
            $this->table->load(1, 'default_profile');
        } else
            $this->table->load($id);
        $this->active_fields = $this->table->active;
        $this->profile = $this->table->id;
        $config = unserialize($this->table->config);
        $config->profile_name = $this->table->profile;
        $config->profile_id = $this->table->id;

        if (!$config->language) {
            $languages = $this->getLanguages();
            if (!in_array('ru-Ru', $languages)) {
                $config->language = current($languages)->language;
            }
        }

        return $config;
    }

    function getLanguages()
    {
        $this->_db->setQuery("SELECT language,name FROM #__jshopping_languages ORDER BY ordering, name");

        return $this->_db->loadObjectList('language');
    }

    function sync()
    {
        $this->_db->setQuery("SELECT  attr_id,independent, `name_{$this->config->language}` as name
                          	  FROM #__jshopping_attr as a
                              LEFT JOIN #__excel2js_fields as f ON f.extra_id = a.attr_id AND f.type IN ('depend','independ')
                              WHERE  f.id IS NULL
                              ORDER BY attr_ordering");
        $fields = $this->_db->loadObjectList();
        if (count($fields)) {
            foreach ($fields as $field) {
                $obj = new stdClass();
                $obj->id = $this->getNewId();
                $obj->extra_id = $field->attr_id;
                $obj->title = $field->name;
                $obj->type = $field->independent ? 'independ' : 'depend';
                $obj->name = "{$obj->type}_{$obj->id}";
                if ($field->independent) {
                    $obj->example = ";";
                } else {
                    $obj->example = "XXL;XL";
                }
                $this->_db->insertObject("#__excel2js_fields", $obj);
            }
        }
        $this->_db->setQuery("SELECT f.id
                              FROM #__excel2js_fields AS f
                              LEFT JOIN #__jshopping_attr AS a ON f.extra_id = a.attr_id
                              WHERE a.attr_id IS NULL AND f.type IN ('depend','independ')");
        $fields = $this->_db->loadColumn();
        if (count($fields)) {
            foreach ($fields as $id) {
                $this->_db->setQuery("DELETE FROM #__excel2js_fields WHERE id = '$id'");
                $this->_db->execute();
            }
        }

        $this->_db->setQuery("SELECT  a.id,`name_{$this->config->language}` as name
                          	  FROM #__jshopping_products_extra_fields as a
                              LEFT JOIN #__excel2js_fields as f ON f.extra_id = a.id AND f.type='extra'
                              WHERE  f.id IS NULL
                              ORDER BY a.id");
        $fields = $this->_db->loadObjectList();

        if (count($fields)) {
            foreach ($fields as $field) {
                $obj = new stdClass();
                $obj->id = $this->getNewId();
                $obj->extra_id = $field->id;
                $obj->title = $field->name;
                $obj->type = 'extra';
                $obj->name = "{$obj->type}_{$obj->id}";
                $obj->example = "Матовый экран;Матовый экран|Глянцевый экран";
                $this->_db->insertObject("#__excel2js_fields", $obj);
            }
        }
        $this->_db->setQuery("SELECT f.id
                              FROM #__excel2js_fields AS f
                              LEFT JOIN #__jshopping_products_extra_fields AS a ON f.extra_id = a.id
                              WHERE a.id IS NULL AND f.type ='extra' ");
        $fields = $this->_db->loadColumn();
        if (count($fields)) {
            foreach ($fields as $id) {
                $this->_db->setQuery("DELETE FROM #__excel2js_fields WHERE id = '$id'");
                $this->_db->execute();
            }
        }

        $xml = simplexml_load_file(JPATH_BASE . DS . 'components' . DS . 'com_jshopping' . DS . 'jshopping.xml');
        $version = (string)$xml->version;
        if (version_compare($version, '4.16.1', '>=')) {
            $this->_db->setQuery("SELECT id FROM #__excel2js_fields WHERE name='depend_manufacturer_code'");
            if (!$this->_db->loadResult()) {
                $obj = new stdClass();
                $obj->id = $this->getNewId();
                $obj->extra_id = 0;
                $obj->title = 'Артикул (Зависимый атр.)';
                $obj->type = 'depend2';
                $obj->name = "depend_manufacturer_code";
                $obj->example = "art2354;GB54889";
                $this->_db->insertObject("#__excel2js_fields", $obj);
            }
        } else {
            $this->_db->setQuery("SELECT id FROM #__excel2js_fields WHERE name='depend_manufacturer_code'");
            $id = $this->_db->loadResult();
            if ($id) {
                $this->_db->setQuery("DELETE FROM #__excel2js_fields WHERE id = '$id'");
                $this->_db->execute();
            }
        }

        $tables = $this->_db->getTableColumns("#__jshopping_products_attr");
        if (isset($tables['availability_status'])) {
            $this->_db->setQuery("SELECT id FROM #__excel2js_fields WHERE  name='depend_availability_status'");
            if (!$this->_db->loadResult()) {
                $this->_db->setQuery("INSERT INTO #__excel2js_fields SET name='depend_availability_status', title ='Статус доступности (Зависимый атр.)', type='depend2',example = '0;1'");
                $this->_db->execute();
            }

            $this->_db->setQuery("SELECT id FROM #__excel2js_fields WHERE  name='availability_status'");
            if (!$this->_db->loadResult()) {
                $this->_db->setQuery("INSERT INTO #__excel2js_fields SET name='availability_status', title ='Статус доступности', type='default',example = '0;1'");
                $this->_db->execute();
            }
        }

    }

    function getNewId()
    {
        $this->_db->setQuery("SELECT MAX(id) FROM #__excel2js_fields");
        $new_id = $this->_db->loadResult();
        if ($new_id < 1000) {
            $new_id = 1000;
        } else {
            $new_id++;
        }

        return $new_id;
    }

    function getCurrencies()
    {
        $this->_db->setQuery("SELECT currency_id , currency_name FROM #__jshopping_currencies ORDER BY currency_id ");

        return $this->_db->loadObjectList('currency_id');
    }

    function getGroups()
    {
        $this->_db->setQuery("SELECT id , CONCAT(title,' (ID:',id,')') AS title FROM #__viewlevels ORDER BY id");
        $groups = $this->_db->loadObjectList();

        array_unshift($groups, JHTML::_('select.option', '', JText::_('CHOOSE'), 'id', 'title'));

        return $groups;

    }

    function getCategoryList($parent_id = 0, $prefix = '|_ ')
    {
        if ($parent_id == 0) $this->list[] = JHTML::_('select.option', '0', JText::_('ALL'), 'category_id', 'category_name');
        $this->_db->setQuery("SELECT  category_id, `name_{$this->config->language}` as category_name,
            (SELECT COUNT(product_id) FROM #__jshopping_products_to_categories as pc WHERE pc.category_id = c.category_id) as products
								  FROM #__jshopping_categories as c
								  WHERE category_parent_id ='$parent_id'
								  ORDER BY category_id");
        $categories = $this->_db->loadObjectList('category_id');

        if (!$categories)
            return false;

        foreach ($categories as $id => $cat) {
            $this->list[] = JHTML::_('select.option', $id, (!$parent_id ? '' : $prefix) . $cat->category_name . " ($cat->products)", 'category_id', 'category_name');
            $this->getCategoryList($id, '&nbsp;.&nbsp;' . $prefix);
        }

        return $this->list;
    }

    function delete_profile()
    {
        $this->_db->setQuery("SELECT COUNT(id) FROM #__excel2js");
        if ($this->_db->loadResult() < 2) {
            return JText::_('YOU_CAN_NOT_DELETE_THE_LAST_PROFILE');
        }
        if ($this->table->delete($this->profile))
            return JText::_('PROFILE_DELETED');
        else
            return JText::_('AN_ERROR_OCCURRED_WHILE_DELETING_A_PROFILE');
    }

    function getUnits()
    {
        $this->_db->setQuery("SELECT  id, `name_{$this->config->language}` as name
		                      FROM #__jshopping_unit ORDER BY id DESC");

        return $this->_db->loadObjectList();
    }

    function getActive()
    {
        $this->active_fields = $this->active_fields ? $this->active_fields : 1;
        $query = "SELECT *
				FROM #__excel2js_fields
				WHERE id IN({$this->active_fields})
				ORDER BY FIELD(id,{$this->active_fields})";

        return $this->_getList($query);
    }

    function getInactive()
    {
        $query = "SELECT *
				FROM #__excel2js_fields
				WHERE id NOT IN({$this->active_fields})
				ORDER BY id";

        return $this->_getList($query);
    }

    function delete_field()
    {
        $id = $this->input->get('id', '', 'int');
        $this->_db->setQuery("DELETE FROM #__excel2js_fields WHERE id = $id");
        $this->_db->execute();
        exit();
    }

    function extra()
    {
        $id = $this->input->get('id', '', 'int');
        if (!$id) {
            $list = $this->_getList("SELECT virtuemart_custom_id, custom_title FROM #__virtuemart_customs WHERE custom_title NOT IN('COM_VIRTUEMART_RELATED_PRODUCTS','COM_VIRTUEMART_RELATED_CATEGORIES')  AND field_type != 'P'");
            echo "<h3>" . JText::_('SELECT_THE_TYPE_OF_CUSTOM_FIELD') . ":</h3>";
            echo JHTML::_('select.genericlist', $list, 'id', 'size="1" style="width:280px"', 'virtuemart_custom_id', 'custom_title');
            echo '<input type="hidden" name="task" value="extra" />';
            echo '<br /><input type="button" onclick="add_field_form()" value="' . JText::_('CREATE') . '" />';

            exit();
        }

        $this->_db->setQuery("SELECT custom_title,is_cart_attribute FROM #__virtuemart_customs WHERE virtuemart_custom_id = $id");
        $extra = $this->_db->loadObject();

        $obj = new stdClass();
        $obj->id = $this->getNewId();
        $obj->extra_id = $id;
        $obj->title = $extra->custom_title . "($obj->id)";
        $obj->name = "extra_{$obj->id}";
        $obj->example = JText::_('CUSTOM_FIELD_VALUE') . " ($obj->id);" . JText::_('CUSTOM_FIELD_VALUE') . " ($obj->id)";
        $obj->type = $extra->is_cart_attribute ? 'extra-cart' : 'extra';
        $this->_db->insertObject("#__excel2js_fields", $obj);
        echo json_encode($obj);
        exit();
    }

    function profile_list($data_only = false)
    {
        $list = $this->_getList("SELECT id, profile FROM #__excel2js ORDER BY id");
        if ($data_only) return $list;
        array_unshift($list, JHTML::_('select.option', '', JText::_('ADD_NEW'), 'id', 'profile'));

        echo "<h3>" . JText::_('SELECT_AN_EXISTING_PROFILE_OR_CREATE_A_NEW_ONE') . ":</h3>";
        echo JHTML::_('select.genericlist', $list, 'profile_id', 'size="1" id="profile_id" style="width:280px" onchange="new_profile()"', 'id', 'profile', 1);
        echo '<input type="hidden" name="task" value="create_profile" />';
        echo '<br /><span style="display:none" id="create_new_profile"><strong>' . JText::_('ENTER_THE_NAME_OF_THE_NEW_PROFILE') . '</strong><br /><input type="text" id="profile" name="profile" value="" /></span>';
        echo '<br /><input type="button" onclick="create_profile_form()" value="' . JText::_('SAVE') . '" />';
        exit();

    }

    function create_profile()
    {
        $profile = $this->input->get('new_profile_name', '', 'string');
        $profile_id = $this->input->get('profile_id_value', '', 'int');
        if ($profile) {
            $this->table->reset();

            $this->_db->setQuery("UPDATE #__excel2js SET default_profile = 0");
            $this->_db->execute();

            $this->_db->setQuery("SELECT id FROM #__excel2js WHERE profile='$profile'");
            $id = $this->_db->loadResult();
            if ($id) {
                $this->table->id = $id;
                $this->table->default_profile = 1;
                $this->table->update();
                echo sprintf(JText::_('PROFILE_S_EXISTS'), $profile);
            } else {
                $this->table->id = '';
                $this->table->profile = $profile;
                $this->table->default_profile = 1;
                $this->table->insert();
                echo sprintf(JText::_('PROFILE_S_ADDED'), $profile);;
            }
            $this->save_config();
        } elseif ($profile_id) {
            $this->change_profile();
            echo JText::_('PROFILE_IS_SAVED_AND_SET_AS_DEFAULT');
            $this->save_config();
        }

        exit();
    }

    function save_config()
    {
        $_POST['last'] = ((int)$_POST['last'] == 0 AND $_POST['last'] != 'все') ? 'все' : $_POST['last'];

        $active = $_POST['fields_list'];
        unset($_POST['fields_list']);
        unset($_POST['option']);
        unset($_POST['task']);
        unset($_POST['view']);

        $config = serialize((object)$_POST);

        $this->_db->setQuery("UPDATE #__excel2js SET config=" . $this->_db->Quote($config) . ",active='$active' WHERE default_profile=1");
        $this->_db->execute();

        echo JText::_('DATA_UPDATED_SUCCESSFULLY');
        exit();
    }

    function change_profile()
    {
        $profile_id = $this->input->get('profile_id_value', '', 'int');

        $this->_db->setQuery("UPDATE #__excel2js SET default_profile = 0");
        $this->_db->execute();
        $this->table->reset();
        $this->table->id = $profile_id;
        $this->table->default_profile = 1;
        $this->table->update();
        $this->config = $this->getConfig();
    }

    function extra_price()
    {
        $id = $this->input->get('id', '', 'int');
        if (!$id) {
            $list = $this->_getList("SELECT id, title FROM #__excel2js_fields WHERE type = 'extra-cart' AND id NOT IN(SELECT extra_id FROM #__excel2js_fields WHERE type = 'extra-price')");
            if (count($list) == 0) {
                echo "<h3>" . JText::_('FIRST_YOU_NEED_TO_ADD_AN_CUSTOM_FIELD') . "</h3>";
                exit();
            }
            echo "<h3>Выберите доп. поле, к кторому будет привязана цена:</h3>";
            echo JHTML::_('select.genericlist', $list, 'id', 'size="1" style="width:280px"', 'id', 'title');
            echo '<input type="hidden" name="task" value="extra_price" />';
            echo '<br /><input type="button" onclick="add_field_form();" value="' . JText::_('SAVE') . '" />';

            exit();
        }

        $this->_db->setQuery("SELECT title FROM #__excel2js_fields WHERE id = $id");
        $obj = new stdClass();
        $obj->title = $this->_db->loadResult() . JText::_('EXTRA_FIELD_ATTRIBUTE_PRICE');
        $obj->id = $this->getNewId();
        $obj->extra_id = $id;
        $obj->name = "extra_price_{$id}";
        $obj->example = JText::_('PRICE_FOR_CUSTOM_FIELD') . " ($id);" . JText::_('PRICE_FOR_CUSTOM_FIELD') . " ($id)";
        $obj->type = 'extra-price';
        $this->_db->insertObject("#__excel2js_fields", $obj);
        echo json_encode($obj);
        exit();
    }

    function price()
    {

        $start = $this->input->get('start', '', 'int');
        $end = $this->input->get('end', '', 'int');

        if (!$start AND !$end) {
            echo "<h3 style='margin-bottom: 6px;'>";
            echo JText::_('RANGE');
            echo ":";
            echo JHTML::tooltip(JText::_('RANGE_HINT'), '', '', "<span class='ui-icon ui-icon-info2' style='float: right; margin-right: .3em;'></span>");
            echo "</h3>";
            echo '<input class="text_area" type="text" name="start"  size="3" maxlength="250" value="" /> <span style="display: inline-block;margin: 6px 5px 0 1px;">-</span> <input class="text_area" type="text" name="end"  size="3" maxlength="250" value="" />';
            echo '<input type="hidden" name="task" value="price" />';
            echo '<br /><br /><input type="button" onclick="add_field_form();" value="' . JText::_('SAVE') . '" />';
            exit();
        }

        $extra_data = new stdClass();
        $extra_data->product_quantity_start = $start;
        $extra_data->product_quantity_finish = $end;
        $obj = new stdClass();
        $obj->title = JText::_('COST_PRICE') . "($start-$end)";
        $obj->id = $this->getNewId();
        $obj->extra_id = json_encode($extra_data);
        $obj->name = "price_{$start}_{$end}";
        $obj->example = "200;5%";
        $obj->type = 'price';
        $this->_db->setQuery("REPLACE INTO #__excel2js_fields SET title='$obj->title',id='$obj->id',extra_id='$obj->extra_id',name='$obj->name',example='$obj->example',type='$obj->type'");
        $this->_db->execute();
        unset($obj->extra_id);
        echo json_encode($obj);
        exit();
    }

    function empty_field()
    {
        $new_id = $this->getNewId();
        $this->_db->setQuery("INSERT INTO #__excel2js_fields SET id=$new_id,name='empty_{$new_id}',title='EMPTY_COLUMN',type='empty',example='EMPTY;EMPTY'");
        $this->_db->execute();
        echo $new_id;
        exit();
    }

    function custom_field()
    {
        $title_id = $this->getNewId();
        $this->_db->setQuery("INSERT INTO #__excel2js_fields SET id=$title_id,name='custom_title_{$title_id}',title='" . JText::_('CUSTOM_COLUMN') . " ($title_id) " . JText::_('CUSTOM_COLUMN_TITLE') . "',type='custom',example='SOME TITLE;SOME TITLE',extra_id='$title_id'");
        $this->_db->execute();
        $ids = new stdClass();
        $ids->title = $title_id;

        $new_id = $this->getNewId();
        $this->_db->setQuery("INSERT INTO #__excel2js_fields SET id=$new_id,name='custom_units_{$title_id}',title='" . JText::_('CUSTOM_COLUMN') . " ($title_id) " . JText::_('CUSTOM_COLUMN_UNITS') . "',type='custom',example='SOME UNITS;SOME UNITS',extra_id='$title_id'");
        $this->_db->execute();
        $ids->units = $new_id;

        $new_id = $this->getNewId();
        $this->_db->setQuery("INSERT INTO #__excel2js_fields SET id=$new_id,name='custom_value_{$title_id}',title='" . JText::_('CUSTOM_COLUMN') . " ($title_id) " . JText::_('CUSTOM_COLUMN_VALUE') . "',type='custom',example='SOME VALUE;SOME VALUE',extra_id='$title_id'");
        $this->_db->execute();
        @$ids->value = $new_id;
        echo json_encode($ids);
        exit();
    }

    function getNewOrdering()
    {
        $this->_db->setQuery("SELECT MAX(ordering) FROM #__excel2js_fields WHERE status = 0");

        return $this->_db->loadResult() + 1;
    }

    function export_profile($save = false)
    {
        $export['config'] = $this->config;
        $this->_db->setQuery("SELECT id FROM #__excel2js_fields WHERE type ='empty' AND id IN ($this->active_fields)");
        $export['empty'] = $this->_db->loadColumn();

        $this->_db->setQuery("
            SELECT f.id,f.title,ef.type,ef.multilist
            FROM #__excel2js_fields as f
            LEFT JOIN #__jshopping_products_extra_fields as ef ON ef.id = f.extra_id
            WHERE f.type ='extra' AND f.id IN ($this->active_fields)");
        $export['extra'] = $this->_db->loadObjectList('id');

        $this->_db->setQuery("SELECT id,title FROM #__excel2js_fields WHERE type ='independ' AND id IN ($this->active_fields)");
        $export['independ'] = $this->_db->loadObjectList('id');

        $this->_db->setQuery("SELECT id,title FROM #__excel2js_fields WHERE type ='depend' AND id IN ($this->active_fields)");
        $export['depend'] = $this->_db->loadObjectList('id');

        $this->_db->setQuery("SELECT id,extra_id FROM #__excel2js_fields WHERE type ='price' AND id IN ($this->active_fields)");
        $export['price'] = $this->_db->loadObjectList('id');

        $export['fields'] = explode(",", $this->active_fields);
        $export = serialize($export);
        $signature = md5($export . '15dgt328jupievpw9ar8');
        $export = base64_encode(serialize([$export, $signature]));
        if (!$save) {
            header('Content-disposition: attachment; filename=profile.txt');
            header('Content-type: text/plain');
            echo $export;
            exit();
        } else {
            return @file_put_contents(JPATH_ROOT . DS . 'components' . DS . 'com_excel2js' . DS . 'profile.txt', $export);
        }

    }

    function import_profile()
    {

        $profile_file = $_FILES['profile_file'];
        if (!isset($profile_file['name']))
            return JText::_('SPECIFY_THE_PROFILE_FILE');

        if (substr($profile_file['name'], -3) != 'txt')
            return JText::_('PROFILE_FILE_MUST_HAVE_THE_EXTENSION_TXT');
        $profile = file_get_contents($profile_file['tmp_name']);
        $profile = unserialize(base64_decode($profile));
        if (md5($profile[0] . '15dgt328jupievpw9ar8') != $profile[1])
            return JText::_('THE_FILE_IS_DAMAGED.');
        $profile = unserialize($profile[0]);
        $active_fields_list = [];

        $language = $profile['config']->language;
        foreach ($profile['fields'] as $key => $field_id) {
            if (in_array($field_id, $profile['empty'])) {
                $this->_db->setQuery("SELECT id FROM #__excel2js_fields WHERE type ='empty' AND id NOT IN (" . (empty($active_fields_list) ? 0 : implode(",", $active_fields_list)) . ") ORDER BY id LIMIT 0,1");
                $empty_id = $this->_db->loadResult();

                if (!$empty_id) {
                    $empty_id = $this->getNewId();
                    $this->_db->setQuery("INSERT INTO #__excel2js_fields SET id=$empty_id,name='empty_{$empty_id}',title='EMPTY_COLUMN',type='empty',example='EMPTY;EMPTY'");
                    $this->_db->execute();
                }
                $active_fields_list[$key] = $empty_id;
            } elseif (@isset($profile['price'][$field_id])) {

                $data = json_decode($profile['price'][$field_id]->extra_id);
                $this->_db->setQuery("SELECT id FROM #__excel2js_fields WHERE name ='price_{$data->product_quantity_start}_{$data->product_quantity_finish}' ORDER BY id LIMIT 0,1");
                $price_id = $this->_db->loadResult();
                if (!$price_id) {
                    $price_id = $this->getNewId();
                    $title = JText::_('COST_PRICE') . "({$data->product_quantity_start}-{$data->product_quantity_finish})";
                    $this->_db->setQuery("INSERT INTO #__excel2js_fields SET id=$price_id,name='price_{$data->product_quantity_start}_{$data->product_quantity_finish}',title='$title',type='price',example='200;5%',extra_id = '{$profile['price'][$field_id]->extra_id}'");
                    $this->_db->execute();
                }
                $active_fields_list[$key] = $price_id;
            } elseif (@isset($profile['extra'][$field_id])) {
                $data = $profile['extra'][$field_id];

                $this->_db->setQuery("SELECT id FROM #__jshopping_products_extra_fields WHERE `name_{$language}` = " . $this->_db->Quote($data->title));
                $extra_id = $this->_db->loadResult();

                if (!$extra_id) {
                    $this->_db->setQuery("INSERT INTO #__jshopping_products_extra_fields SET allcats = 1,cats = 'a:0:{}',type=" . $this->_db->Quote($data->type) . ",multilist = " . $this->_db->Quote($data->multilist) . ", `name_{$language}` = " . $this->_db->Quote($data->title));
                    $this->_db->execute();
                    $extra_id = $this->_db->insertid();
                    $jshopping_products = $this->_db->getTableColumns($this->_db->getPrefix() . "jshopping_products");
                    if (!isset($jshopping_products['extra_field_' . $extra_id])) {
                        $this->_db->setQuery("ALTER TABLE `#__jshopping_products`
        								ADD `extra_field_{$extra_id}` varchar(100) NOT NULL;");
                        $this->_db->execute();
                    }
                }

                $this->_db->setQuery("SELECT id FROM #__excel2js_fields WHERE extra_id = $extra_id AND type='extra'  AND `title` = " . $this->_db->Quote($data->title));
                $extra_field_id = $this->_db->loadResult();
                if (!$extra_field_id) {
                    $extra_field_id = $this->getNewId();
                    $this->_db->setQuery("INSERT INTO #__excel2js_fields SET id=$extra_field_id,name='extra_{$extra_field_id}',title=" . $this->_db->Quote($data->title) . ",type='extra',example='Матовый экран;Матовый экран|Глянцевый экран',extra_id = '$extra_id'");
                    $this->_db->execute();
                }
                $active_fields_list[$key] = $extra_field_id;
            } elseif (@isset($profile['independ'][$field_id])) {
                $data = $profile['independ'][$field_id];

                $this->_db->setQuery("SELECT  attr_id FROM #__jshopping_attr WHERE independent = 1 AND `name_{$language}` = " . $this->_db->Quote($data->title));
                $attr_id = $this->_db->loadResult();

                if (!$attr_id) {
                    $this->_db->setQuery("INSERT INTO #__jshopping_attr SET independent = 1,attr_type =1, allcats = 1,cats = 'a:0:{}', `name_{$language}` = " . $this->_db->Quote($data->title));
                    $this->_db->execute();
                    $attr_id = $this->_db->insertid();
                }

                $this->_db->setQuery("SELECT id FROM #__excel2js_fields WHERE extra_id = $attr_id AND type='independ'  AND `title` = " . $this->_db->Quote($data->title));
                $extra_field_id = $this->_db->loadResult();
                if (!$extra_field_id) {
                    $extra_field_id = $this->getNewId();
                    $this->_db->setQuery("INSERT INTO #__excel2js_fields SET id=$extra_field_id,name='extra_{$extra_field_id}',title=" . $this->_db->Quote($data->title) . ",type='independ',example=';',extra_id = '$attr_id'");
                    $this->_db->execute();
                }
                $active_fields_list[$key] = $extra_field_id;
            } elseif (@isset($profile['depend'][$field_id])) {
                $data = $profile['depend'][$field_id];

                $this->_db->setQuery("SELECT  attr_id FROM #__jshopping_attr WHERE independent = 0 AND `name_{$language}` = " . $this->_db->Quote($data->title));
                $attr_id = $this->_db->loadResult();

                if (!$attr_id) {
                    $this->_db->setQuery("INSERT INTO #__jshopping_attr SET independent = 0,attr_type =1, allcats = 1,cats = 'a:0:{}', `name_{$language}` = " . $this->_db->Quote($data->title));
                    $this->_db->execute();
                    $attr_id = $this->_db->insertid();
                    $jshopping_products_attr = $this->_db->getTableColumns($this->_db->getPrefix() . "jshopping_products_attr");
                    if (!isset($jshopping_products_attr['attr_' . $attr_id])) {
                        $this->_db->setQuery("ALTER TABLE `#__jshopping_products_attr`
        								ADD `attr_{$attr_id}` varchar(100) NOT NULL;");
                        $this->_db->execute();
                    }
                }

                $this->_db->setQuery("SELECT id FROM #__excel2js_fields WHERE extra_id = $attr_id AND type='depend'  AND `title` = " . $this->_db->Quote($data->title));
                $extra_field_id = $this->_db->loadResult();
                if (!$extra_field_id) {
                    $extra_field_id = $this->getNewId();
                    $this->_db->setQuery("INSERT INTO #__excel2js_fields SET id=$extra_field_id,name='extra_{$extra_field_id}',title=" . $this->_db->Quote($data->title) . ",type='depend',example='XXL;XL',extra_id = '$attr_id'");
                    $this->_db->execute();
                }
                $active_fields_list[$key] = $extra_field_id;
            } else
                $active_fields_list[$key] = $field_id;
        }
        $this->_db->setQuery("SELECT id FROM #__excel2js WHERE profile = " . $this->_db->Quote($profile['config']->profile_name));
        $profile_id = $this->_db->loadResult();
        if ($profile_id) {
            $this->_db->setQuery("UPDATE #__excel2js SET active = '" . implode(",", $active_fields_list) . "', config = " . $this->_db->Quote(serialize($profile['config'])) . " WHERE id = $profile_id");
            $this->_db->execute();
            $msg = sprintf(JText::_('PROFILE_S_UPDATED'), $profile['config']->profile_name);
        } else {
            $this->_db->setQuery("INSERT INTO #__excel2js SET active = '" . implode(",", $active_fields_list) . "', config = " . $this->_db->Quote(serialize($profile['config'])) . ", profile = " . $this->_db->Quote($profile['config']->profile_name));
            $this->_db->execute();
            $profile_id = $this->_db->insertid();
            $msg = sprintf(JText::_('PROFILE_S_ADDED'), $profile['config']->profile_name);
        }
        $this->input->get('profile_id_value', $profile_id);
        $this->change_profile();

        return $msg;

    }
}