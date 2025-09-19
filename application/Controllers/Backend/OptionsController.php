<?php

namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use System\Libraries\Session;
use App\Models\OptionsModel;
use System\Libraries\Render;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Validate;

class OptionsController extends BackendController
{

    protected $optionsModel;
    protected $allowed_types;

    public function __construct()
    {
        parent::__construct();
        $this->optionsModel = new OptionsModel();

        Flang::load('Backend/Global', APP_LANG);
        Flang::load('Backend/Options', APP_LANG);
        $config_files = config('files');
        $this->allowed_types = $config_files['allowed_types'] ?? [];
    }

    public function index()
    {
        if (!empty($_POST)) {
            return $this->_updateValue($_POST);
        }
        $options = $this->optionsModel->getOptions();

        foreach ($options as $index => $option) {
            if (APP_LANG_DF !== $this->post_lang) {
                if (!empty($option['valuelang'])) {
                    $valuelang = json_decode($option['valuelang'], true) ?? [];
                    if (!empty($valuelang) && isset($valuelang[$this->post_lang])) {
                        $options[$index]['value'] =  $valuelang[$this->post_lang];
                    }
                }
            }
        }

        $this->data('options', $options);
        $this->data('errors', []);
        $this->data('post_lang', $this->post_lang);
        $this->data('title', __('Website Settings'));
        $this->data('csrf_token', Session::csrf_token(600));
        // Render::asset('css', 'css/forms.css', ['area' => 'backend', 'location' => 'footer']);
        // Render::asset('js', 'js/forms.js', ['area' => 'backend', 'location' => 'footer']);
        echo Render::html('Backend/options_index', $this->data);
    }

    //Use _ for can not access = url.
    private function _updateValue($data)
    {
        $rules = [];
        $sql_value = [];
        $sql_valuelang = [];
        $file_value = [];
        $file_valuelang = [];
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $option = $this->optionsModel->getByName($key);
                // check key in database
                if (empty($option)) {
                    unset($data[$key]);
                    continue;
                }

                switch ($option['type']){
                    case 'Boolean':
                      if ($value == 'false' || $value == 'False'){
                        $value = 0;
                      }else{
                        $value = 1;
                      }
                    break;
                    case 'Number':
                      $optionOptional = json_decode($option['optional'], true);
                      if (!empty($optionOptional) && isset($optionOptional['step']) && $optionOptional['step'] < 1){
                        $value = (float)$value;
                      }else{
                        $value = (int)$value;
                      }
                    break;
                  }

                $optional = $option['optional'];
                $optional = is_string($optional) ? json_decode($optional, true) : $optional;
                $rules[$key] = $this->_rules_validate($optional);
                if (isset($optional['synchronous']) && $optional['synchronous']) {
                    //Set all $sql_value[$key] to $_POST value ($value) and remove $sql_valuelang[$key] if exists
                    if ($optional['save_file']) { //if have save_file => set $file_value[$key] = $value
                        $file_value[$key] =  $value;
                    }
                    if ($option['value'] != $value) { //set value sql if have change value
                        $sql_value[$key] = $value;
                        $sql_valuelang[$key] = '';
                    }
                    if ($option['valuelang'] != '') { //set value sqllang to empty
                        $sql_valuelang[$key] = '';
                    } elseif (isset($sql_valuelang[$key])) {
                        unset($sql_valuelang[$key]);
                    }
                } else {
                    if (APP_LANG_DF !== $this->post_lang) {
                        //You are  only change valuelang, not update value (sql and file value)
                        if ($optional['save_file']) { //if have save_file => set $file_valuelang[$key] = $value
                            $file_valuelang[$key] = $value;
                        }
                    } else {
                        //Set all $sql_value[$key] to $_POST value ($value), not change valuelang
                        if ($optional['save_file']) { //if have save_file => set $file_value[$key] = $value
                            $file_value[$key] =  $value;
                        }
                        if ($option['value'] != $value) { //set value sql if have change value
                            $sql_value[$key] = $value;
                        }
                    }
                    // Because this is not sync all languages, need checking valuelang valid & FIX value lang.
                    $option_valuelang = json_decode($option['valuelang'], true) ?? [];
                    if (!is_array($option_valuelang)) $option_valuelang = [];
                    if (empty($option_valuelang)) {
                        foreach (APP_LANGUAGES as $lang => $langData) {
                            if ($lang != APP_LANG_DF) {
                                $option_valuelang[$lang] = $value;
                            }
                        }
                    } else {
                        foreach ($option_valuelang as $lang => $valuelang) {
                            if (!isset(APP_LANGUAGES[$lang]) || $lang == APP_LANG_DF) unset($option_valuelang[$lang]);
                        }
                        foreach (APP_LANGUAGES as $lang => $langData) {
                            if (!isset($option_valuelang[$lang]) && $lang != APP_LANG_DF) {
                                $option_valuelang[$lang] = $value;
                            }
                        }
                        if (isset($option_valuelang[$this->post_lang]) && $option_valuelang[$this->post_lang] != $value) {
                            $option_valuelang[$this->post_lang] = $value;
                        }
                    }
                    $sql_valuelang[$key] = $option_valuelang;
                }
            }
        }

        $validator = new Validate();
        if (!$validator->check($data, $rules)) {
            $errors = $validator->getErrors();
        }
        if (!empty($file_value)) {
            foreach ($file_value as $key => $item_data) {
                option_set($key, $item_data);
            }
        }
        if (!empty($file_valuelang)) {
            foreach ($file_valuelang as $key => $item_data) {
                option_set($key, $item_data, $this->post_lang);
            }
        }

        if (!empty($sql_value)) {
            try{
                $this->optionsModel->setValuebyName($sql_value);
            } catch (\PDOException $e) {
                $errors[] = __('Update option error. No have change value sql.') . $e->getMessage();
            }
        }
        if (!empty($sql_valuelang)) {
            try{
                $this->optionsModel->setValuelangByName($sql_valuelang);
            } catch (\PDOException $e) {
                $errors[] = __('Update option error. No have change value translate sql.') . $e->getMessage();
            }
        }
        
        // Set flash messages
        if (!empty($errors)) {
            Session::flash('error', implode(', ', $errors));
        } else {
            Session::flash('success', __('Options updated successfully'));
        }
        
        redirect(admin_url('options/index/') . '?post_lang=' . $this->post_lang);
    }
    private function _rules_validate($optional)
    {
        // Decode JSON if necessary
        $optional = is_string($optional) ? json_decode($optional, true) : $optional;

        // Result array containing rules and messages
        $rules = [
            'rules' => [],
            'messages' => []
        ];

        // Check required field
        if (!empty($optional['required'])) {
            $rules['rules'][] = Validate::notEmpty();
            $rules['messages'][] = __('not_empty');
        }

        // Check minimum and maximum length
        if (!empty($optional['min']) || !empty($optional['max'])) {
            $min = !empty($optional['min']) ? $optional['min'] : 1;
            $max = !empty($optional['max']) ? $optional['max'] : 255;
            $rules['rules'][] = Validate::length($min, $max);
            $rules['messages'][] = __('not_min_max');
        }

        // Check data type if it's 'Number'
        if (!empty($optional['type']) && $optional['type'] == 'Number') {
            $rules['rules'][] = Validate::NumericVal();
            $rules['messages'][] = "{$optional['label']} must be a number.";
        }

        // Check if it's 'slug'
        if (!empty($optional['field_name']) && $optional['field_name'] == 'slug') {
            $rules['rules'][] = Validate::lowercase();
            $rules['messages'][] = __('lowercase_error');
        }

        // Check if it's email
        if (!empty($optional['type']) && $optional['type'] == 'Email') {
            $rules['rules'][] = Validate::email();
            $rules['messages'][] = __('email_valid');
        }

        // Check if it's URL
        if (!empty($optional['type']) && $optional['type'] == 'URL') {
            $rules['rules'][] = Validate::url();
            $rules['messages'][] = __('url_valid');
        }

        // Check if it's date field
        if (!empty($optional['type']) && $optional['type'] == 'Date') {
            $rules['rules'][] = Validate::date();
            $rules['messages'][] = __('date_valid');
        }

        return $rules;
    }

    private function _validate($data = [], $isField = false)
    {
        //Start Validation
        $errors = [];
        //validate Upload File Type if is Upload Files form.
        if (!empty($data['allow_types'])) {
            $filesDiff = array_diff($data['allow_types'], $this->allowed_types);
            if (!empty($filesDiff)) {
                $errors[] =  sprintf(__('posttype_allow_types_required'), implode(',', $filesDiff));
            }
        }
        $validator = new Validate();
        $rules = [
            'type' => [
                'rules' => [Validate::in(['Text', 'Email', 'Number', 'Password',  'Date', 'DateTime', 'ColorPicker',  'URL', 'OEmbed', 'Textarea', 'Boolean', 'Checkbox', 'Radio', 'Select', 'File', 'Image', 'WYSIWYG', 'Reference', 'Repeater', 'User'])],
                'messages' => [__('field_type_invalid')]
            ],
            'label' => [
                'rules' => [Validate::length(3, 100)],
                'messages' => [__('field_label_length')]
            ],
            'description' => [
                'rules' => [Validate::length(0, 250)],
                'messages' => [
                    __('field_description_length')
                ]
            ],
            'status' => [
                'rules' => [Validate::in([true, false])],
                'messages' => [
                    __('field_status_invalid')
                ]
            ],
            'visibility' => [
                'rules' => [Validate::in([true, false])],
                'messages' => [
                    __('field_visibility_invalid')
                ]
            ],
            'collapsed' => [
                'rules' => [Validate::in([true, false])],
                'messages' => [__('field_collapsed_invalid')]
            ],
            'css_class' => [
                'rules' => [Validate::lowercase()],
                'messages' => [__('field_css_class_lowercase')]
            ],
            'placeholder' => [
                'rules' => [Validate::length(0, 250)],
                'messages' => [
                    __('field_placeholder_length')
                ]
            ],
            'default_value' => [
                'rules' => [Validate::length(0, 9999)],
                'messages' => [
                    __('field_default_value_length')
                ]
            ],
            'order' => [
                'rules' => [Validate::notEmpty(), Validate::NumericVal()],
                'messages' => [
                    __('field_order_required'),
                    __('field_order_numeric')
                ]
            ],
            'min' => [
                'rules' => [Validate::optional(Validate::NumericVal())],
                'messages' => [__('field_min_invalid')]
            ],
            'max' => [
                'rules' => [Validate::optional(Validate::NumericVal())],
                'messages' => [__('field_max_invalid')]
            ],
            'rows' => [
                'rules' => [Validate::optional(Validate::NumericVal())],
                'messages' => [__('field_rows_invalid')]
            ],

            'multiple' => [
                'rules' => [Validate::in([null, true, false])],
                'messages' => [__('field_multiple_invalid')]
            ],
            'position' => [
                'rules' => [Validate::in(['left', 'top', 'right', 'bottom'])],
                'messages' => [
                    __('field_position_invalid')
                ]
            ],
            'width_unit' => [
                'rules' => [Validate::in(['px', '%', 'em', 'rem', 'vw', 'vh'])],
                'messages' => [
                    __('field_width_unit_invalid')
                ]
            ],
            'width_value' => [
                'rules' => [Validate::NumericVal()],
                'messages' => [
                    __('field_width_value_invalid')
                ]
            ],
        ];
        if (!$isField && !empty($data['name'])){
            $rules['name'] = [
                'rules' => [Validate::length(3, 100), Validate::regex('/^[a-z0-9_]+$/')],
                'messages' => [
                    __('field_name_length'),
                    sprintf(__('field_name_invalid'), 'a-z0-9_')
                ]
            ];
        }

        if (!$validator->check($data, $rules)) {
            $errors = $validator->getErrors();
        }
        if (!empty($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $item) {
                $itemErrors = $this->_validate($item, true);
                if (is_array($itemErrors) && !empty($itemErrors)) {
                    $errors = array_merge($errors, $itemErrors);
                }
            }
        }
        return $errors;
    }

    // get render posttype
    public function add()
    {
        if (HAS_POST('list_options') && HAS_POST('csrf_token')) {
            if (!Session::csrf_verify(S_POST('csrf_token'))) {
                $this->data('errors', ["csrf_token" => __('csrf_failed')]);
                Session::flash('error', __('Invalid security token'));
            } else {
                $errors = array();
                $options_json = $_POST['list_options'] ?? '';
                $options = json_decode($options_json, true);
                $addItems = [];
                foreach ($options as $key => $option) {
                    $errors[$key] = $this->_validate($option, false);
                    if (is_array($errors[$key]) && !empty($errors[$key])) {
                        continue;
                    } else {
                        $errors[$key] = [];
                        if ($this->optionsModel->getByName($option['name'])) {
                            //Data Option is valid but Option exist in Database.
                            $errors[$key]['duplicates'] = [__('database_exist') . ' - ' . $option['name']];
                        } else {
                            if (empty($option['option_group'])) {
                                $option['option_group'] = 'general';
                            }
                            $addItem = array(
                                'label' => $option['label'],
                                'type' => $option['type'],
                                'name' => $option['name'],
                                'description' => $option['description'],
                                'status' => $option['status'],
                                'value' => $option['default_value'],
                                'valuelang' => '',
                                'optional' => json_encode($option),
                                'created_at' => DateTime(),
                                'updated_at' => DateTime()
                            );
                            if ($this->optionsModel->addOptions($addItem)) {
                                $addItems[] = $addItem;
                                unset($options[$key]);
                            } else {
                                $errors[$key]['database'] = [__('database_add_fail') . ' - ' . $option['name']];
                            }
                        }
                    }
                }
                \System\Libraries\Events::run('Backend\\OptionAddEvent', $addItems);
                if (count($options) <= 0) {
                    Session::flash('success', __('Options added successfully'));
                    redirect(admin_url('options/index'));
                }
                $_POST['list_options'] = json_encode($options);
                $this->data('errors', $errors);
                Session::flash('error', __('Please fix the errors below'));
            }
        }

        // get list options group =.=
        $option_group = $this->optionsModel->getbyname('option_groups');
        if (!empty($option_group) && !empty($option_group['optional'])) {
            $optional = is_string($option_group['optional']) ? json_decode($option_group['optional'], true) : $option_group['optional'];
            $option_groups =  $optional['options'];
        }
        $this->data('allowed_types', $this->allowed_types);
        $this->data('option_groups', $option_groups);
        $this->data('isEditing', false);
        $this->data('title', __('options_add_welcome'));
        $this->data('csrf_token', Session::csrf_token(600));
        // Render::asset('css', 'css/forms.css', ['area' => 'backend', 'location' => 'footer']);
        // Render::asset('js', 'js/forms.js', ['area' => 'backend', 'location' => 'footer']);
        echo Render::html('Backend/options_add', $this->data);
    }

    public function edit($id)
    {
        $option = $this->optionsModel->getById($id);
        if (!$option) {
            redirect(admin_url('options/index/'));
        } else {
            $editOption = json_decode($option['optional'], true);
            $editOption['id'] = $option['id'];
            $editOption['value'] = $option['value'];
            $editOption['valuelang'] = $option['valuelang'];
            $editOption['created_at'] = $option['created_at'];
            $editOption['updated_at'] = $option['updated_at'];
            $editOption['collapsed'] = false;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!empty(S_POST('csrf_token')) && Session::csrf_verify(S_POST('csrf_token'))) {

                $options = $_POST['list_options'] ?? '';
                $options = is_string($options) ? json_decode($options, true) : $options;
                if (!empty($options)) $option = $options[0];
                if (!empty($option)) {
                    $errors = $this->_validate($option, false);
                    $checkExit = $this->optionsModel->getByName($option['name']);
                    if ($checkExit && $checkExit['id'] != $id) {
                        //Data Option is valid but Option exist in Database.
                        $errors['duplicates'] = [__('database_exist') . ' - ' . $option['name']];
                    }
                    if (empty($errors)) {
                        $optionalData = $option;
                        if (isset($optionalData['value'])) unset($optionalData['value']);
                        if (isset($optionalData['valuelang'])) unset($optionalData['valuelang']);
                        $newdata = array(
                            'id' => $option['id'],
                            'label' => $option['label'],
                            'type' => $option['type'],
                            'name' => $option['name'],
                            'description' => $option['description'],
                            'status' => $option['status'],
                            'optional' => json_encode($optionalData),
                            'created_at' => DateTime(),
                            'updated_at' => DateTime()
                        );
                        if ($this->optionsModel->setOptions($id, $newdata)) {
                            \System\Libraries\Events::run('Backend\\OptionEditEvent', $newdata);
                            Session::flash('success', __('Option updated successfully'));
                            redirect(admin_url('options/edit/' . $id));
                        } else {
                            Session::flash('error', __('Failed to update option'));
                        }
                    } else {
                        Session::flash('error', __('Please fix the errors below'));
                    }
                }
            } else {
                $this->data('errors', ["csrf_token" => __('csrf_failed')]);
                Session::flash('error', __('Invalid security token'));
            }
        }
        $option_group = $this->optionsModel->getbyname('option_groups');
        if (!empty($option_group) && !empty($option_group['optional'])) {
            $optional = is_string($option_group['optional']) ? json_decode($option_group['optional'], true) : $option_group['optional'];
            $option_groups =  $optional['options'];
        }
        $this->data('option_groups', $option_groups);
        $this->data('allowed_types', $this->allowed_types);
        $this->data('options', [$editOption]);
        $this->data('isEditing', true);
        $this->data('title', __('option_edit_title') . ' ' . $editOption['name']);
        $this->data('csrf_token', Session::csrf_token(600)); // Create new token for first load
        echo Render::html('Backend/options_add', $this->data);
    }

    public function delete($id)
    {
        // just remove, check if name is option_group then skip

        $option = $this->optionsModel->getById($id);
        if ($option && $option['name'] != 'option_group') {
            if ($this->optionsModel->delOptions($id)) {
                \System\Libraries\Events::run('Backend\\OptionDeleteEvent', $option);
                Session::flash('success', __('Option deleted successfully'));
                redirect(admin_url('options'));
            } else {
                Session::flash('error', __('Failed to delete option'));
                redirect(admin_url('options'));
            }
        } else {
            Session::flash('error', __('Option not found or cannot be deleted'));
            redirect(admin_url('options'));
        }
    }
}
