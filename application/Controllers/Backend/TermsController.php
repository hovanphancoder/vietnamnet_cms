<?php
namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use System\Core\BaseController;
use App\Models\TermsModel;
use App\Models\LanguagesModel;
use App\Models\PosttypeModel;
use System\Libraries\Session;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Render;
use System\Libraries\Validate;

class TermsController extends BackendController {
    protected $termsModel;
    protected $LanguagesModel;
    protected $PosttypeModel;
    public function __construct()
    {
        parent::__construct();
        load_helpers(['backend', 'string']);
        $this->termsModel = new TermsModel();
        $this->LanguagesModel = new LanguagesModel();
        $this->PosttypeModel = new PosttypeModel();
        Flang::load('general', APP_LANG);
        Flang::load('terms', APP_LANG);
    }

    // Display list of all terms
    public function index() {
        Render::asset('js', 'js/jstring.1.1.0.js', ['area' => 'backend', 'location' => 'footer']);
        $this->data('csrf_token', Session::csrf_token(600));
        if(HAS_GET('type') && HAS_GET('posttype'))
        {
            $type = S_GET('type') ?? '';
            $posttype = S_GET('posttype') ?? '';
            $posttypeData = $this->PosttypeModel->getPostTypeBySlug($posttype);
            if(empty($posttypeData)) {
                redirect(admin_url('/'));
            }
            $termsInfo = is_string($posttypeData['terms']) ? json_decode($posttypeData['terms'], true) : [];
            $currentTermInfo = [];
            foreach($termsInfo as $termInfo) {
                if($termInfo['type'] == $type) {
                    $currentTermInfo = $termInfo;
                    break;
                }
            }
            if(empty($currentTermInfo)) {
                redirect(admin_url('/'));
            }
            $languagesPosttype = is_string($posttypeData['languages']) ? json_decode($posttypeData['languages'], true) : [];
            $termsLang= [];
            // check lang có phải bằng all không
            if(in_array('all', $languagesPosttype)) {
                $termsLang = [['code' => 'all', 'name' => 'All']];
            } else {
                foreach(APP_LANGUAGES as $key => $lang) {
                    if(in_array($key, $languagesPosttype)) {
                        $lang['code'] = $key;
                        $termsLang[] = $lang;
                    }
                }
            }
            $mainterms = $this->termsModel->getTermsByTypeAndPostTypeAndLang($posttype, $type, APP_LANG_DF);
            if(!empty($mainterms)) {
                $this->data('mainterms', $mainterms);
            }
            $allTerm = $this->termsModel->getTermsByTypeAndPostType($posttype, $type);
            $tree = $this->treeTerm($allTerm);
            $this->data('default_lang', APP_LANG_DF);
            $this->data('title', Flang::_e('title_index') . ' - ' . $posttype . ($type ? ' - ' . $type : ''));
            $this->data('type', $type); 
            $this->data('currentTermInfo', $currentTermInfo);
            $this->data('posttype', $posttype);
            $this->data('allTerm', $allTerm);
            $this->data('langActive', $termsLang);
            $this->data('tree', $tree);
            echo Render::html('Backend/terms_index', $this->data);
        } else {
            $allTerm = $this->termsModel->getTaxonomies();
            $tree = $this->treeTerm($allTerm);
            // $this->data('csrf_token', Session::csrf_token(600));
            $this->data('allTerm', $allTerm);
            $this->data('title', Flang::_e('title_index'));
            $this->data('tree', $tree);
            $this->data('langActive', array_keys(APP_LANGUAGES));
            // $this->render('backend', 'Backend/Terms/index');
            echo Render::html('Backend/terms_index', $this->data);
        }    
        
    }
    private function treeTerm($term) {
        $result = [];
        $tree = [];
        
        // Sort data by id and parent
        foreach ($term as $item) {
            $result[$item['id']] = $item;
            $result[$item['id']]['children'] = [];
        }
    
        // Build hierarchical tree from data
        foreach ($result as $id => &$node) {
            // get name lang
            if (!empty($node['lang'])) {
                $lang = $this->LanguagesModel->getLanguageByCode($node['lang']);
                if ($lang) {
                    $node['lang_name'] = $lang['name'];
                } else {
                    $node['lang_name'] = '';
                }
            } else {
                $node['lang_name'] = '';
            }

            if (!empty($node['parent'])) {
                $result[$node['parent']]['children'][] = &$node;
                $node['parent_name'] = $result[$node['parent']]['name'];
            } else {
                $tree[] = &$node;
            }
        }
        
        // Recursive function to print hierarchical tree
        function printTree($items, $level = 0) {
            foreach ($items as $item) {
                echo str_repeat(' - ', $level) . $item['name'] . "\n";
                if (!empty($item['children'])) {
                    printTree($item['children'], $level + 1);
                }
            }
        }  
        // Print hierarchical tree
      return $tree;
    }
    
    public function add($id = null) {
        // if()
        // if($id) {
        //     $data = $this->termsModel->getTermById($id);
        // } else {
        //     $data = [];
        // }
        // if(empty($data)) {
        //     redirect(admin_url('terms/?posttype=' . $posttype . '&type=' . $type));
        // }

        $name = S_POST('name');
        $type = S_POST('type');
        $posttype = S_POST('posttype');
        $parent = S_POST('parent');
        $lang = S_POST('lang');
        $seo_title = S_POST('seo_title');
        $seo_desc = S_POST('seo_desc');
        $id_main = S_POST('id_main');
        $counter = 2;

        if(empty($parent) || $parent == 0) {
            $parent = null;
        }
        if(empty(HAS_POST('slug'))) {
            $slug = url_slug($name);
        } else {
            $slug = S_POST('slug');
        }
        $allTerm = $this->termsModel->getTaxonomies();
        $description = S_POST('description');
        if($this->termsModel->getTermsSlugAndByTypeAndPostTypeAndLang($slug, $posttype, $type, $lang)) {
            $newslug = $slug . '-' . $counter;
            while ($this->termsModel->getTermsSlugAndByTypeAndPostTypeAndLang($newslug, $posttype, $type, $lang)) {
                $newslug = $slug . '-' . $counter;
                $counter++;
            }
            $slug = $newslug;
        }
        $input = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'type' => $type,
            'posttype' => $posttype,
            'parent' => $parent,
            'lang' => $lang,
            'id_main' => $id_main,
            'seo_title' => $seo_title,
            'seo_desc' => $seo_desc
        ];
        $rules = [
            'name' => [
                'rules' => [
                    Validate::notEmpty(''),
                    Validate::length(2, 30)
                ],
                'messages' => [
                    Flang::_e('name_empty'),
                    Flang::_e('name_length', 2, 30)
                ]
            ],
            'slug' => [
                'rules' => [
                    Validate::notEmpty(),
                ],
                'messages' => [
                    Flang::_e('slug_empty'),
                ]   
            ],
            // 'description' => [
            //     'rules' => [
            //         Validate::notEmpty(),
            //         Validate::length(6, 150)
            //     ],
            //     'messages' => [
            //         Flang::_e('description_empty'),
            //         Flang::_e('description_length', 6, 150)
            //     ]
            // ],
            'type' => [
                'rules' => [
                    Validate::notEmpty()
                ],
                'messages' => [
                    Flang::_e('type_empty')
                ]
            ],
            'posttype' => [
                'rules' => [
                    Validate::notEmpty(),
                ],
                'messages' => [
                    Flang::_e('posttype_empty'),
                ]
            ],
            'parent' => [],
            'lang' => [
                'rules' => [
                    Validate::notEmpty(),
                ],
                'messages' => [
                    Flang::_e('lang_empty'),
                ]
            ],

        ];
        $validator = new Validate();
        if (!$validator->check($input, $rules)) {
            // Get errors and display
            $errors = $validator->getErrors();
            $this->data('errors', $errors);     
        }else{
            $id = $this->termsModel->addTerm($input);
            if($id){
                // if main_id = 0 thì gáng lại id_main = id
                if($id_main == 0) {
                    $this->termsModel->setTerm($id, ['id_main' => $id]);
                }
                Session::flash('success', Flang::_e('add_terms_success'));
                \System\Libraries\Events::run('Backend\\TermsAddEvent', $input);
            }
            
        }

        $redirectUrl = admin_url('terms/?posttype=' . $posttype . '&type=' . $type);
        $redirectUrl = rtrim($redirectUrl, '/');

        redirect($redirectUrl);
    }

    // Edit a term
    public function edit($termId) {
        $type = S_GET('type') ?? '';
        $posttype = S_GET('posttype')?? '';
        $posttypeData = $this->PosttypeModel->getPostTypeBySlug($posttype);
            $languagesPosttype = is_string($posttypeData['languages']) ? json_decode($posttypeData['languages'], true) : [];
        if(HAS_POST('name')) {
            $input = [
                'name' => S_POST('name'),
                'slug' => S_POST('slug'),
                'type' => S_POST('type'),
                'posttype' => S_POST('posttype'),
                'parent' => S_POST('parent'),
                'lang' => S_POST('lang'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $rules = [
                'name' => [
                    'rules' => [
                        Validate::notEmpty(''),
                        Validate::length(6, 30)
                    ],
                    'messages' => [
                        Flang::_e('name_empty'),
                        Flang::_e('name_length', 6, 30)
                    ]
                ],
                'slug' => [
                    'rules' => [
                        Validate::notEmpty(),
                    ],
                    'messages' => [
                        Flang::_e('slug_empty'),
                    ]   
                ],
                'type' => [
                    'rules' => [
                        Validate::notEmpty()
                    ],
                    'messages' => [
                        Flang::_e('type_empty', 6, 30)
                    ]
                ],
                'posttype' => [
                    'rules' => [
                        Validate::notEmpty(),
                    ],
                    'messages' => [
                        Flang::_e('posttype_empty', 6, 60),
                    ]
                ],
                'parent' => [],
                'lang' => [
                    'rules' => [
                        Validate::notEmpty(),
                    ],
                    'messages' => [
                        Flang::_e('lang_empty'),
                    ]
                ],
    
            ];
            
            $validator = new Validate();
            if (!$validator->check($input, $rules)) {
                // Get errors and display
                $errors = $validator->getErrors();
                $this->data('errors', $errors);     
            }else{
                if($this->termsModel->setTerm($termId, $input)) {
                    Session::flash('success', Flang::_e('edit_terms_success'));
                    \System\Libraries\Events::run('Backend\\TermsEditEvent', $input);
                }

                $redirectUrl = admin_url('terms/?posttype=' . $posttype . '&type=' . $type);
                $redirectUrl = rtrim($redirectUrl, '/');
                redirect($redirectUrl);
            }

        }
            // check lang có phải bằng all không
            if(in_array('all', $languagesPosttype)) {
                $termsLang = [['code' => 'all', 'name' => 'All']];
            } else {
                foreach(APP_LANGUAGES as $key => $lang) {
                    if(in_array($key, $languagesPosttype)) {
                        $lang['code'] = $key;
                        $termsLang[] = $lang;
                    }
                }
            }
        $termsInfo = is_string($posttypeData['terms']) ? json_decode($posttypeData['terms'], true) : [];
        $currentTermInfo = [];
        foreach($termsInfo as $termInfo) {
            if($termInfo['type'] == $type) {
                $currentTermInfo = $termInfo;
                break;
            }
        }
        $allTerm = $this->termsModel->getTermsByTypeAndPostType($posttype, $type);
        $data = $this->termsModel->getTermById($termId);
        $lang = $this->LanguagesModel->getActiveLanguages();
        $tree = $this->treeTerm($this->termsModel->getTermsByTypeAndPostType($posttype, $type));
        $this->data('csrf_token', Session::csrf_token(600));
        $this->data('default_lang', APP_LANG_DF);
        $this->data('title', 'Edit term');
        $this->data('lang', $lang);
        $this->data('langActive', $termsLang);
        $this->data('currentTermInfo', $currentTermInfo);
        $this->data('allTerm', $allTerm);
        $this->data('data', $data);
        $this->data('tree', $tree);
        echo Render::html('Backend/terms_edit', $this->data);
        
        // $this->render('backend', 'Backend/Terms/edit');
    }
    public function delete($termId = null) {
        if(!empty($termId)) {
            $posttype = S_GET('posttype')?? '';
            $type = S_GET('type') ?? '';
            $this->_delete($posttype, $type, $termId);
            redirect(admin_url('terms/?posttype=' . $posttype . '&type=' . $type));
        } elseif($_POST['ids']) {
            $ids = $_POST['ids'];
            $ids = json_decode($ids, true);
            $posttype = S_POST('posttype')?? '';
            $type = S_POST('type') ?? '';
            foreach($ids as $id) {
                $this->_delete($posttype, $type, $id);
            }
            
            $this->success([], Flang::_e('delete_terms_success'));
        } else {
           return admin_url();
        }
        
    }
    // Delete term
    private function _delete($posttype, $type, $termId) {
        $children = $this->termsModel->getTermByParent($termId);
        if (!empty($children)) {
            foreach ($children as $child) {
                $newdata = [
                    'parent' => null,
                ];
                $this->termsModel->setTerm($child['id'], $newdata);
            }
        }
    
        if($this->termsModel->delTerm($termId)) {
            Session::flash('success', Flang::_e('delete_terms_success'));
            \System\Libraries\Events::run('Backend\\TermsDeleteEvent', $posttype);
        } else {
            Session::flash('error', Flang::_e('delete_terms_error'));
        }
        
        return true;

    }

    public function gettermsbylang() {
        // Ensure no output before header and echo
        header('Content-Type: application/json');
        
        if(!HAS_POST('lang') || !HAS_POST('type') || !HAS_POST('posttype')) {
            exit(json_encode([
                'status' => 'error', 
                'message' => 'Missing required parameters'
            ]));
        }
        
        $lang = S_POST('lang');
        $type = S_POST('type');
        $posttype = S_POST('posttype');
        
        try {
            $terms = $this->termsModel->getTermsByTypeAndPostTypeAndLang($posttype, $type, $lang);
            $tree = $this->treeTerm($terms);
            
            exit(json_encode([
                'status' => 'success',
                'data' => $tree
            ]));
        } catch (\Exception $e) {
            exit(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
        }
    }


}
