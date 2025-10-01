<?php
namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use System\Core\BaseController;
use App\Models\TermsModel;
use App\Models\LanguagesModel;
use App\Models\PostsModel;
use System\Libraries\Session;
use App\Libraries\Fastlang as Flang;
use System\Libraries\Render;
use System\Libraries\Validate;

class TermsController extends BackendController {
    protected $termsModel;
    protected $LanguagesModel;
    protected $PostsModel;
    public function __construct()
    {
        parent::__construct();
        load_helpers(['backend', 'string']);
        $this->termsModel = new TermsModel();
        $this->LanguagesModel = new LanguagesModel();
        $this->PostsModel = new PostsModel();
        Flang::load('general', APP_LANG);
        Flang::load('terms', APP_LANG);
    }

    // Display list of all terms
    public function index() {
        $this->data('csrf_token', Session::csrf_token(600));
        if(HAS_GET('type') && HAS_GET('posttype'))
        {   
            $post_lang = S_GET('post_lang') ?? '';
            $type = S_GET('type') ?? '';
            $posttype = S_GET('posttype') ?? '';
            $posttypeData = $this->PostsModel->getPostTypeBySlug($posttype);
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
            if(empty($post_lang) || !in_array($post_lang, $languagesPosttype)) {
                redirect(admin_url('terms/?posttype=' . $posttype . '&type=' . $type . '&post_lang=' . $languagesPosttype[0]));
            }
           
            $allTerm = $this->termsModel->getTermsByTypeAndPostType($posttype, $type);

            // thêm 1 bước term chia theo languages
            // check xem APP_LANG_DF có nằm trong array lang không
            // Nếu có thì lấy APP_LANG_DF là lang cây
            // nếu không có thì lấy lang 0
            $langMain = APP_LANG_DF;
            if(in_array($langMain, $languagesPosttype)) {
                $langMain = $langMain;
            } else {
                $langMain = $languagesPosttype[0];
            }

            $terms_languages = $this->formatTermsByLanguage($allTerm, $post_lang);
            
            $tree = $this->treeTerm($terms_languages);
            $this->data('title', Flang::_e('title_index') . ' - ' . $posttype . ($type ? ' - ' . $type : ''));
            $this->data('currentTermInfo', $currentTermInfo);
            $this->data('allTerm', $allTerm);
            $this->data('posttypeData', $posttypeData);
            $this->data('tree', $tree);
            echo Render::html('Backend/terms_index', $this->data);
        } else {
            redirect(admin_url('/'));
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
        
        return $tree;
    }
    
    public function add($id = null) {
        // check nếu method là post
        if($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = S_POST('name');
            $type = S_POST('type');
            $posttype = S_POST('posttype');
            $parent = S_POST('parent');
            $lang = S_POST('lang');
            $seo_title = S_POST('seo_title');
            $seo_desc = S_POST('seo_desc');
            $id_main = S_POST('id_main');
            $status = S_POST('status') ?? 'active';
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
                'seo_desc' => $seo_desc,
                'status' => $status
            ];
            $rules = [
                'name' => [
                    'rules' => [
                        Validate::notEmpty(''),
                        Validate::length(2, 100)
                    ],
                    'messages' => [
                        Flang::_e('name empty'),
                        Flang::_e('Name length must be between 2 and 100 characters')
                    ]
                ],
                'slug' => [
                    'rules' => [
                        Validate::notEmpty(),
                    ],
                    'messages' => [
                        Flang::_e('slug empty'),
                    ]   
                ],
                'description' => [
                    'rules' => [
                        Validate::optional(
                            Validate::notEmpty(),
                            Validate::length(6, 150)
                        ),
                    ],
                    'messages' => [
                        Flang::_e('description_empty'),
                        Flang::_e('description_length', 6, 150)
                    ]
                ],
                'type' => [
                    'rules' => [
                        Validate::notEmpty()
                    ],
                    'messages' => [
                        Flang::_e('type empty')
                    ]
                ],
                'posttype' => [
                    'rules' => [
                        Validate::notEmpty(),
                    ],
                    'messages' => [
                        Flang::_e('posttype empty'),
                    ]
                ],
                'parent' => [
                    // optional là số
                    'rules' => [
                        Validate::optional(
                            Validate::numericVal(),
                        ),
                    ],
                    'messages' => [
                        Flang::_e('parent valid'),
                    ]
                ],
                'lang' => [
                    'rules' => [
                        Validate::notEmpty(),
                    ],
                    'messages' => [
                        Flang::_e('lang empty'),
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

            $redirectUrl = admin_url('terms/?posttype=' . $posttype . '&type=' . $type . '&post_lang=' . $lang);
            $redirectUrl = rtrim($redirectUrl, '/');

            redirect($redirectUrl);
        } else {
            // check xem có biến mainterm không
            $mainterm = S_GET('mainterm');
            $post_lang = S_GET('post_lang');
            $posttype = S_GET('posttype');
            $type = S_GET('type');
            // check posttype của terms đó xem có tồn tại posttype
            $posttypeData = $this->PostsModel->getPostTypeBySlug($posttype);
            $posttypeData['languages'] = is_string($posttypeData['languages']) ? json_decode($posttypeData['languages'], true) : $posttypeData['languages'];
            $posttypeData['terms'] = is_string($posttypeData['terms']) ? json_decode($posttypeData['terms'], true) : $posttypeData['terms'];
            if(empty($posttypeData)) {
                Session::flash('error', Flang::_e('posttype not found'));
                redirect(admin_url('terms/?posttype=' . $posttype . '&type=' . $type . '&post_lang=' . $posttypeData['languages'][0]));
            }

            if(!in_array($post_lang, $posttypeData['languages'])) {
                Session::flash('error', Flang::_e('lang not in posttype'));
                redirect(admin_url('terms/?posttype=' . $posttype . '&type=' . $type . '&post_lang=' . $posttypeData['languages'][0]));
            }

            $currentTermInfo = [];
            if(!empty($posttypeData['terms'])) {
                foreach($posttypeData['terms'] as $term) {
                    if($term['type'] == $type) {
                        $currentTermInfo = $term;
                        break;
                    }
                }
            }

            if(empty($currentTermInfo)) {
                Session::flash('error', Flang::_e('term type not found'));
                redirect(admin_url('terms/?posttype=' . $posttype . '&type=' . $posttypeData['terms'][0]['type'] . '&post_lang=' . $post_lang));
            }

            // get all terms của post_lang
            $all_terms = $this->termsModel->getTermsByTypeAndPostTypeAndLang($posttype, $type, $post_lang);
            $all_terms_lang = $this->formatTermsByLanguage($all_terms, $post_lang);
            $all_terms_tree = $this->treeTerm($all_terms_lang);
            if(!empty($mainterm)) {
                $main_term = $this->termsModel->getTermById($mainterm);
                if(empty($main_term)) {
                    Session::flash('error', Flang::_e('main term not found'));
                    redirect(admin_url('terms/?posttype=' . $posttype . '&type=' . $type . '&post_lang=' . $post_lang));
                }
                $this->data('mainterm', $mainterm);
            }
            // get ra tất cả main terms
            $main_terms_lang = $this->termsModel->getTermByIdMain($mainterm);
            $main_terms_lang = array_column($main_terms_lang, null, 'lang');
            $this->data('main_terms_lang', $main_terms_lang);
            $this->data('all_terms', $all_terms);
            $this->data('all_terms_tree', $all_terms_tree);
            $this->data('posttypeData', $posttypeData);
            $this->data('type', $type);
            $this->data('post_lang', $post_lang);
            $this->data('csrf_token', Session::csrf_token(600));
            Render::asset('js', 'js/jstring.1.1.0.js', ['area' => 'backend', 'location' => 'footer']);
            echo Render::html('Backend/terms_add', $this->data);

        }
    }

    // Edit a term
    public function edit($termId) {
        $data = $this->termsModel->getTermById($termId);
        $posttype = $data['posttype'];
        $type = $data['type'];;
        $posttypeData = $this->PostsModel->getPostTypeBySlug($posttype);
        $languagesPosttype = is_string($posttypeData['languages']) ? json_decode($posttypeData['languages'], true) : [];
        if(HAS_POST('name')) {
            $input = [
                'name' => S_POST('name'),
                'slug' => S_POST('slug'),
                'type' => S_POST('type'),
                'posttype' => S_POST('posttype'),
                'parent' => S_POST('parent') ?: null,
                'lang' => S_POST('lang'),
                'status' => S_POST('status') ?? 'active',
                'description' => S_POST('description'),
                'seo_title' => S_POST('seo_title'),
                'seo_desc' => S_POST('seo_desc'),
                'id_main' => S_POST('id_main'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $rules = [
                'name' => [
                    'rules' => [
                        Validate::notEmpty(''),
                        Validate::length(2, 60)
                    ],
                    'messages' => [
                        Flang::_e('name empty'),
                        Flang::_e('Name length must be between 2 and 60 characters')
                    ]
                ],
                'slug' => [
                    'rules' => [
                        Validate::notEmpty(),
                    ],
                    'messages' => [
                        Flang::_e('slug empty'),
                    ]   
                ],
                'type' => [
                    'rules' => [
                        Validate::notEmpty()
                    ],
                    'messages' => [
                        Flang::_e('type empty')
                    ]
                ],
                'posttype' => [
                    'rules' => [
                        Validate::notEmpty(),
                    ],
                    'messages' => [
                        Flang::_e('posttype empty')
                    ]
                ],
                'parent' => [
                    'rules' => [
                        Validate::optional(
                            Validate::numericVal(),
                        ),
                    ],
                    'messages' => [
                        Flang::_e('parent valid'),
                    ]
                ],
                'lang' => [
                    'rules' => [
                        Validate::notEmpty(),
                    ],
                    'messages' => [
                        Flang::_e('lang empty'),
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
                    Session::flash('success', Flang::_e('edit terms success'));
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
        $lang = $this->LanguagesModel->getActiveLanguages();
        $allTermLang = $this->formatTermsByLanguage($allTerm, $data['lang']);
        $tree = $this->treeTerm($allTermLang);
        // Get main terms for id_main selection
        $mainterms = [];
        if ($data['lang'] !== APP_LANG_DF) {
            $mainterms = $this->termsModel->getTermsByTypeAndPostTypeAndLang($posttype, $type, APP_LANG_DF);
            $mainterms = $this->treeTerm($mainterms);
        }
        
        $this->data('csrf_token', Session::csrf_token(600));
        $this->data('default_lang', APP_LANG_DF);
        $this->data('title', 'Edit term');
        $this->data('lang', $lang);
        $this->data('langActive', $termsLang);
        $this->data('currentTermInfo', $currentTermInfo);
        $this->data('allTerm', $allTerm);
        $this->data('data', $data);
        $this->data('tree', $tree);
        $this->data('mainterms', $mainterms);
        $this->data('posttypeData', $posttypeData);
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

    /**
     * Format terms data by grouping main language terms and adding sub-language terms
     * 
     * @param array $terms Array of terms data
     * @param string $mainLang Main language code (e.g., 'en', 'vi', 'id')
     * @return array Formatted terms with lang_terms key
     */
    private function formatTermsByLanguage($terms, $mainLang) {
        $formattedTerms = [];
        $termsByMainId = [];
        
        // Group terms by id_main
        foreach ($terms as $term) {
            $idMain = $term['id_main'] ?? $term['id'];
            if (!isset($termsByMainId[$idMain])) {
                $termsByMainId[$idMain] = [];
            }
            $termsByMainId[$idMain][] = $term;
        }
        
        // Process each group
        foreach ($termsByMainId as $idMain => $termGroup) {
            $mainTerm = null;
            $subTerms = [];
            
            // Find main language term and sub-language terms
            foreach ($termGroup as $term) {
                if ($term['lang'] === $mainLang) {
                    $mainTerm = $term;
                } else {
                    $subTerms[] = $term;
                }
            }
            
            // If main language term exists, add it to formatted terms
            if ($mainTerm) {
                // Group sub-terms by language code as key
                $langTerms = [];
                foreach ($subTerms as $subTerm) {
                    $langTerms[$subTerm['lang']] = $subTerm;
                }
                $mainTerm['lang_terms'] = $langTerms;
                $formattedTerms[] = $mainTerm;
            }
        }
        
        return $formattedTerms;
    }

    /**
     * Get terms formatted by language with tree structure
     * 
     * @param array $terms Array of terms data
     * @param string $mainLang Main language code
     * @return array Formatted terms with tree structure
     */
    private function formatTermsWithTree($terms, $mainLang) {
        $formattedTerms = $this->formatTermsByLanguage($terms, $mainLang);
        
        // Build tree structure for each main term and its lang_terms
        foreach ($formattedTerms as &$mainTerm) {
            // Build tree for main term
            $mainTermTree = $this->buildTreeForTerms([$mainTerm]);
            if (!empty($mainTermTree)) {
                $mainTerm = $mainTermTree[0];
            }
            
            // Build tree for each lang_term
            foreach ($mainTerm['lang_terms'] as &$langTerm) {
                $langTermTree = $this->buildTreeForTerms([$langTerm]);
                if (!empty($langTermTree)) {
                    $langTerm = $langTermTree[0];
                }
            }
        }
        
        return $formattedTerms;
    }
    
    /**
     * Build tree structure for terms (helper function)
     * 
     * @param array $terms Array of terms
     * @return array Tree structure
     */
    private function buildTreeForTerms($terms) {
        $tree = [];
        $lookup = [];
        
        // Create lookup array
        foreach ($terms as $term) {
            $lookup[$term['id']] = $term;
            $lookup[$term['id']]['children'] = [];
        }
        
        // Build tree
        foreach ($terms as $term) {
            if (!empty($term['parent']) && isset($lookup[$term['parent']])) {
                $lookup[$term['parent']]['children'][] = &$lookup[$term['id']];
            } else {
                $tree[] = &$lookup[$term['id']];
            }
        }
        
        return $tree;
    }


}
