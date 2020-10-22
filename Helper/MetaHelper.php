<?php

namespace Kanboard\Plugin\MetaMagik\Helper;

use Kanboard\Core\Base;
use Kanboard\Model\UserModel;
use Kanboard\Model\TaskFinderModel;


/**
 * Meta helper
 * 
 * author Craig Crosby
 *
 */
class MetaHelper extends Base
{
    
    private function beautyName($human_name = '')
    {
        // Replace underscores with whitespaces
        $beauty_name = preg_replace('/_/', ' ', $human_name);

        return $beauty_name;
    }
    
    public function renderMetaTextField($key, $value, array $errors = array(), array $attributes = array())
    {
        $html = "";
        $html .= $this->helper->form->label($this->beautyName($key), 'metamagikkey_' . $key);
        $html .= $this->helper->form->text('metamagikkey_' . $key, ['metamagikkey_' . $key => $value], $errors, $attributes, 'form-input-small');
        return $html;
    }
    
    public function renderMetaTextAreaField($key, $value, array $errors = array(), array $attributes = array())
    {
        $html = "";
        $html .= $this->helper->form->label($this->beautyName($key), 'metamagikkey_' . $key);
        $html .= $this->helper->form->textarea('metamagikkey_' . $key, ['metamagikkey_' . $key => $value], $errors, $attributes, 'metamagik-form-textarea');
        return $html;
    }
    
    public function renderDateField($key, $value, array $errors = array(), array $attributes = array())
    {
        $html = "";
        $html .= $this->helper->form->label($this->beautyName($key), 'metamagikkey_' . $key);
        $html .= $this->helper->form->input('date', 'metamagikkey_' . $key, ['metamagikkey_' . $key => $value], $errors, $attributes, 'form-input-small');
        return $html;
    }

    public function renderMetaNumberField($key, $value, array $errors = array(), array $attributes = array())
    {
        $html = "";
        $html .= $this->helper->form->label($this->beautyName($key), 'metamagikkey_' . $key);
        $html .= $this->helper->form->number('metamagikkey_' . $key, ['metamagikkey_' . $key => $value], $errors, $attributes, 'form-input-small');
        return $html;
    }

    public function renderMetaListField($key, $values, array $list, $type, array $errors = array(), array $attributes = array())
    {
        $map_list = [];
        if($type == "kvlist") {
            foreach ($list as $name => $value) {
                $map_list[$value] = $name;
            }
        } else if($type == "list"){
            $list = array_merge(array(""=>""), $list);
            foreach ($list as $name => $value) {
                $map_list[$value] = $value;
            }
        } else {
            foreach ($list as $name => $value) {
                $map_list[$value] = $value;
            }
        }
        
        $html = "";
        $html .= $this->helper->form->label($this->beautyName($key), 'metamagikkey_' . $key);

        switch ($type){
            case "radio": $html .= $this->helper->form->radios('metamagikkey_' . $key, $map_list, $values); break;
            case "list": $html .= $this->helper->form->select('metamagikkey_' . $key, $map_list, $values, $errors, $attributes, 'form-input-small'); break;
            case "kvlist": $html .= $this->helper->form->select('metamagikkey_' . $key, $map_list, $values, $errors, $attributes, 'form-input-small'); break;
            case "check": $html .= $this->helper->form->checkboxes('metamagikkey_' . $key . '[]', $map_list, $values); break;
        }

        return $html;
    }

    public function renderMetaUsersField($key, $value, array $errors = array(), array $attributes = array()){
        $aux_user = new UserModel($this->container);
        $users_table = $aux_user->getActiveUsersList(false);
        $users = [];
        foreach ($users_table as $name => $valuex) {
            $users[] = $valuex;
        }
        return $this->renderMetaListField($key, $value, $users, 'list', $errors, $attributes);
    }

    public function renderMetaTableField($key, $value, $table_name, $keycolumn, $valuecolumn, array $errors = array(), array $attributes = array()){
        $meta_opt[''] = '';
        $aux_table = $this->db->table($table_name)->columns($keycolumn, $valuecolumn)->findAll();
        foreach ($aux_table as $valuex) {
            $meta_opt[$valuex[$keycolumn]] = $valuex[$valuecolumn];
        }
        return $this->renderMetaListField($key, $value, $meta_opt, 'kvlist', $errors, $attributes);
    }
    
    public function renderMetaColumnCriteriaField($key, $value, $table_name, $keycolumn, $criteria, $valuecolumn, array $errors = array(), array $attributes = array()){
        $meta_opt[''] = '';
        $aux_table = $this->db->table($table_name)->eq($keycolumn, $criteria)->findAllByColumn($valuecolumn);

        return $this->renderMetaListField($key, $value, $aux_table, 'list', $errors, $attributes);
    }

    public function renderMetaFields(array $values, $column_number, array $errors = array(), array $attributes = array())
    {
        $metasettings = $this->metadataTypeModel->getAllInColumn($column_number, $values['project_id']);
        $html = '';

        if (isset($values['id'])) {
        $metadata = $this->taskMetadataModel->getAll($values['id']);
            foreach ($metasettings as $setting) {
                if ($setting['attached_to'] == 'task') {
                    $metaisset = $this->taskMetadataModel->exists($values['id'], $setting['human_name']);
                    if (!$metaisset) {
                        $this->taskMetadataModel->save($values['id'], [$setting['human_name'] => '']);
                    }
                }
            }
        } else {
            $metadata = array();
        }

        foreach ($metasettings as $setting) {
            $key = $setting['human_name'];
            if (isset($values['id']) && $setting['data_type'] !== 'check') {
                if (isset($metadata[$key])) { $values['metamagikkey_' . $key] = $metadata[$key]; }
            } elseif (isset($values['id']) && $setting['data_type'] == 'check') {
                if (isset($metadata[$key])) {
                    $wtf = explode(',', $metadata[$key]);
              
                    foreach ($wtf as $key_fix) {
                        $values['metamagikkey_' . $key . '[]'][$key_fix] = $key_fix;
                    } 
                }
            }
            
            $new_attributes = $attributes;
            if($setting['is_required']) {
                $new_attributes['required'] = "required";
            }
            if ($setting['data_type'] == 'text') {
                $html .= $this->renderMetaTextField($key, isset($metadata[$key]) ? $metadata[$key] : "", $errors, $new_attributes);
            } elseif ($setting['data_type'] == 'textarea') {
                $html .= $this->renderMetaTextAreaField($key, isset($metadata[$key]) ? $metadata[$key] : "", $errors, $new_attributes);
            } elseif ($setting['data_type'] == 'number') {
                $html .= $this->renderMetaNumberField($key, isset($metadata[$key]) ? $metadata[$key] : "", $errors, $new_attributes);
            } elseif ($setting['data_type'] == 'date') {
                $html .= $this->renderDateField($key, isset($metadata[$key]) ? $metadata[$key] : "", $errors, $new_attributes);
            } else if ($setting['data_type'] == 'table') {
                $opt_explode = explode(',', $setting['options']);
                $html .= $this->renderMetaTableField($key, $values, $opt_explode[0], $opt_explode[1], $opt_explode[2], $errors, $new_attributes);
            } else if ($setting['data_type'] == 'columneqcriteria') {
                $opt_explode = explode(',', $setting['options']);
                $html .= $this->renderMetaColumnCriteriaField($key, $values, $opt_explode[0], $opt_explode[1], $opt_explode[2], $opt_explode[3], $errors, $new_attributes);
            } elseif ($setting['data_type'] == 'users') {
                $html .= $this->renderMetaUsersField($key, $values, $errors, $new_attributes);
            } elseif ($setting['data_type'] == 'list' || $setting['data_type'] == 'radio' || $setting['data_type'] == 'check') {
                $opt_explode = explode(',', $setting['options']);
                $html .= $this->renderMetaListField($key, $values, $opt_explode, $setting['data_type'], $errors, $new_attributes);
            }
        }

        return $html;
    }

}
