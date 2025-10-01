<?php

namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use App\Models\PostsModel;
use App\Models\TermsModel;
use App\Models\UsersModel;
use System\Libraries\Render;
use System\Libraries\Validate;
use App\Libraries\Fastlang as Flang;

class PostsController extends BackendController {
   protected $languageModel;
   protected $termsModel;
   protected $usersModel;
   protected $postsModel;
   protected $post_lang;
   
   public function __construct()
   {
        parent::__construct();
        load_helpers(['backend', 'string']);
        Flang::load('Backend/Posts');

        $posttypeSlug = S_GET('type') ?? 'post';
        $postLang = S_GET('post_lang') ?? 'all';

       
        $this->postsModel = new PostsModel($posttypeSlug, $postLang);
        $this->termsModel = new TermsModel();
        $this->usersModel = new UsersModel();
        $this->post_lang = S_GET('post_lang') ?? null;
        
   }

    public function index() {

        $postTypeSlug = S_GET('type') ?? 'post'; 
        $currentLang = $this->post_lang; 
        $search = S_GET('q') ?? '';
        $limit = S_GET('limit') ?? 10;
        $sort = S_GET('sort') ?? '';
        $order = S_GET('order') ?? 'DESC';
        $paged = S_GET('page') ?? 1;
        // If page = 0 or negative, set to 1
        if ($paged < 1) {
            $paged = 1;
        }


        // If limit is invalid, set to 10
        if ($limit < 1) {
            $limit = 10;
        }

        $postType = $this->postsModel->getPostTypeBySlug($postTypeSlug);
        if(empty($postType)) {
            redirect(admin_url('/'));
        }
        $allPostType = $this->postsModel->getAllPostTypes();
       

        $posttypeLanguages = json_decode($postType['languages'], true);
        if(empty($this->post_lang)) $currentLang = $posttypeLanguages[0];
        if(!in_array($currentLang, $posttypeLanguages)) {
            redirect(admin_url('posts').'?type='.$postTypeSlug.'&post_lang='.$posttypeLanguages[0]);
        }
        if(!empty($search)) {
            $where = "title LIKE ? ";
            $params = ['%' . $search . '%'];
        } else {
            $where = '';
            $params = [];
        }
        if(!empty($sort) && !empty($order)) {
            $orderBy = $sort . ' ' . $order;
        } else {
            $orderBy = 'id DESC';
        }

        $postModel = new PostsModel($postTypeSlug, $currentLang);
        $postsOfCurrentLang = $postModel->getPostsFieldsPagination('*', $where, $params, $orderBy, $paged, $limit);
        $idsOfCurrentLang = [];
        $postsLists = $postsOfCurrentLang;
        $postsLists['data'] = [];
        // get id in post['data][$item][id]
        
        foreach ($postsOfCurrentLang['data'] as $item) {
            $idsOfCurrentLang[] = $item['id'];
            // $posts['data'][$item] to array key  $posts['data']['id']
            $item['languages'] = array($currentLang);
            $postsLists['data'][$item['id']] = $item;
        }

        if(!empty($idsOfCurrentLang) && !empty($posttypeLanguages)) {
            foreach ($posttypeLanguages as $lang) {
                if($lang == $currentLang) continue;
                $postLangModel = new PostsModel($postTypeSlug, $lang);  
                // get by list id
                $where = "id IN (" . implode(',', $idsOfCurrentLang) . ")";
                $postsLang = $postLangModel->getPostsFieldsPagination('id, title, status, created_at', $where, []);
                // foreach post add ID main posts
                if (!empty($postsLang['data'])) {
                    foreach ($postsLang['data'] as $item) {
                        // add to languages lang code 
                        $postsLists['data'][$item['id']]['languages'][] = $lang;
                    }
                }
            }
        }
        unset($postsOfCurrentLang);
       

        
        $this->data('posttype', $postType);
        $this->data('allPostType', $allPostType);
        $this->data('limit', $limit);
        $this->data('sort', $sort);
        $this->data('order', $order);
        $this->data('page', $paged);
        $this->data('search', $search);
        $this->data('posts', $postsLists);
        $this->data('currentLang', $currentLang);
        $this->data('languages', $posttypeLanguages);
        $this->data('title', __('List ') . $postType['name'] . ' ' .  $this->post_lang);
        echo Render::html('Backend/posts_index', $this->data);

    }




    public function add() {
        // validate posttype
        $postTypeSlug = S_POST('type') ?? (S_GET('type') ?? '' );
        $postType = $this->postsModel->getPostTypeBySlug($postTypeSlug);
        if(empty($postType)) {
           redirect(admin_url());
        }
        
        // format decode data
        $postType['terms'] = is_string($postType['terms']) ? json_decode($postType['terms'], true) : $postType['terms'];
        $postType['fields'] = is_string($postType['fields']) ? json_decode($postType['fields'], true) : $postType['fields'];
        $languages = is_string($postType['languages']) ? json_decode($postType['languages'], true) : [];    
            
        // check post_lang in_array languages
        if(!in_array($this->post_lang, $languages)) {
           redirect(admin_url('posts/add').'?type='.$postTypeSlug.'&post_lang='.$languages[0]);
        }
        // validate language S_GET
        if(empty($this->post_lang)) {
            if(in_array(APP_LANG_DF, $languages)) {
                $this->post_lang = APP_LANG_DF;
            } else {
                $this->post_lang = $languages[0];
            }   
        };

        // handle submit form 
        if(S_POST('type')) {
            $langadd = S_POST('lang') ?? $this->post_lang;
            $curent_id = $postType['current_id'] ?? 0;
            $postDataFields = $_POST;
            if(is_string($postDataFields['terms'])) {
                $postDataFields['terms'] = json_decode($postDataFields['terms'], true);
            } 

            $terms_list = $postDataFields['terms'];

            // check created_at
            if(!empty($postDataFields['created_at'])) {
                if(is_numeric($postDataFields['created_at'])) {
                    $postDataFields['created_at'] = date('Y-m-d H:i:s', $postDataFields['created_at']);
                } else {
                    $postDataFields['created_at'] = date('Y-m-d H:i:s', strtotime($postDataFields['created_at']));
                }
            } else {
                $postDataFields['created_at'] = date('Y-m-d H:i:s');
            }
            $data = [
                'id' => ($curent_id + 1),
                'title' => $postDataFields['title'],
                'slug' => $postDataFields['slug'],
                'status' => $postDataFields['status'],
                'created_at' => $postDataFields['created_at'],
                'updated_at' => $postDataFields['created_at'],
            ];
            $data['slug'] = $this->checkPostSlug($data['slug'], $postType['slug'], $langadd);
            // validate data form
            $rules = $this->convert_rules($postType['fields']);
            $validator = new Validate();
            if(!$validator->check($postDataFields, $rules)) {
                $errors = $validator->getErrors();
                $this->data('errors', $errors);
            } else {
                foreach($postType['fields'] as $field) {
                    if($field['type'] == 'Reference') {
                        // xoi
                        // if sync then save to relationship table with language as all, otherwise save a simple one
                        if($field['synchronous'] == true) {
                            $langRel = 'all';
                        } else {
                            $langRel = $langadd;
                        }
                    if($field['table_save_data_reference'] == 1) {
                        $tableRelation = table_posttype_relationship($postType['slug']);
                        foreach($postDataFields[$field['field_name']] as $item) {
                            $this->postsModel->addReferenceRelationship($tableRelation, $data['id'], $field['post_type_reference'], $field['id'] ,  $item , $langRel );
                        }
                        } else {
                        $tableRelation = table_posttype_relationship($field['post_type_reference']);
                            if(!empty($postDataFields[$field['field_name']])) {
                                foreach($postDataFields[$field['field_name']] as $item) {
                                    $this->postsModel->addReferenceRelationship($tableRelation, $item, $postType['slug'], $field['id'] , $data['id'], $langRel );
                                }
                            }
                        
                        };
                    } elseif(($field['type'] == 'Point')) {
                        if(isset($postDataFields[$field['field_name']]) && count($postDataFields[$field['field_name']]) === 2) {
                            $latitude = $postDataFields[$field['field_name']][0];
                            $longitude = $postDataFields[$field['field_name']][1];
                            if($latitude >= 90 || $latitude <= -90 || $longitude >= 180 || $longitude <= -180) $data[$field['field_name']] = NULL; continue; 
                            $data[$field['field_name']] = [
                                'expr' => "ST_GeomFromText('POINT($longitude $latitude)')",
                                'params' => []
                            ];
                        } else {
                            $data[$field['field_name']] = NULL;
                        }
                   }else {
                        if(isset($postDataFields[$field['field_name']])) {
                            if ($field['type'] == 'Number'){
                                if ($postDataFields[$field['field_name']] === ""){
                                    $data[$field['field_name']] = null;
                                }else{
                                    $data[$field['field_name']] = (int)$postDataFields[$field['field_name']];
                                }
                            }else{
                                $data[$field['field_name']] = is_array($postDataFields[$field['field_name']]) ? json_encode($postDataFields[$field['field_name']]) : $postDataFields[$field['field_name']];
                            }
                        }
                    }
                }
                $result = $this->_add($postType, $data, $languages, $terms_list, $langadd);
                if($result) {
                    redirect(admin_url('posts').'?type='.$postTypeSlug.'&post_lang='.$langadd);
                } else {
                    $this->data('errors', ['Failed to add post']);
                }
            };
        }

        // update fields for FE format
        $postType['fields'] = $this->_loadDefaultInputs($postType['fields']);
        $postType = $this->_loadTermInputs($postType, $postType['terms']);

        // data for view add post 
        $this->data('posttype', $postType);
        $this->data('title', __('add_new'));
        echo Render::html('Backend/posts_add', $this->data);
    }

    private function _add($postType, $data, $languages, $terms_list, $langadd) {
        $tableRelation = table_posttype_relationship($postType['slug']);
        if(in_array(APP_LANG_DF, $languages)) {
            
            $terms = is_string($postType['terms']) ? json_decode($postType['terms'], true) : $postType['terms'];

            foreach ($terms_list as $termItem) {

                $terminfo = $this->termsModel->getTermById( $termItem);
                if($terminfo['lang'] == APP_LANG_DF) {
                    $term_id_main = $terminfo['id'];
                } else {
                    if($terminfo['id_main'] != 0 ) {
                        $term_id_main = $terminfo['id_main'];
                    }
                }

                foreach ($terms as $term) {
                    if($terminfo['type'] == $term['type']) {
                        if($term['synchronous_init'] === 'true') {
                            $this->postsModel->createRelationship($tableRelation, $data['id'], $term_id_main, 'all');
                        } else {
                            $this->postsModel->createRelationship($tableRelation, $data['id'], $terminfo['id'], $langadd);
                        }
                    }
                }
            }
        } else {
            foreach($terms_list as $termID) {
                $this->postsModel->createRelationship($tableRelation, $data['id'], $termID, $langadd);
        }
        }
        $tableName = posttype_name($postType['slug'], $langadd);
        if ($this->postsModel->addPost($tableName, $data)){
            $postType['terms'] = is_array($postType['terms']) ? json_encode($postType['terms']) : $postType['terms'];
            $postType['fields'] = is_array($postType['fields']) ? json_encode($postType['fields']) : $postType['fields'];
            $postType['current_id'] = $data['id'];
            $this->postsModel->updatePostType($postType['id'], $postType);
            \System\Libraries\Events::run('Backend\\PostsAddEvent', $data);
            return true;
        } else {
            return false;
        }
    }

    public function import() {
        // Validate posttype
        $postTypeSlug = S_POST('type') ?? (S_GET('type') ?? 'post');
        $postType = $this->postsModel->getPostTypeBySlug($postTypeSlug);
        if(empty($postType)) {
            redirect(admin_url('/'));
        }

        $postType['terms'] = is_string($postType['terms']) ? json_decode($postType['terms'], true) : $postType['terms'];
        $postType['fields'] = is_string($postType['fields']) ? json_decode($postType['fields'], true) : $postType['fields'];
        $languages = is_string($postType['languages']) ? json_decode($postType['languages'], true) : [];
        
        // Validate language
        $currentLang = $this->post_lang;
        if(empty($currentLang)) {
            if(in_array(APP_LANG_DF, $languages)) {
                $currentLang = APP_LANG_DF;
            } else {
                $currentLang = $languages[0];
            }
        }

        // Handle CSV upload and parsing
        if(S_POST('action') == 'upload_csv') {
            $response = ['success' => false, 'message' => '', 'data' => []];
            
            if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
                $csvFile = $_FILES['csv_file']['tmp_name'];
                $csvData = $this->parseCSV($csvFile);
                
                if($csvData !== false) {
                    $response['success'] = true;
                    $response['data'] = $csvData;
                    $response['message'] = 'CSV uploaded successfully';
                } else {
                    $response['message'] = 'Error parsing CSV file';
                }
            } else {
                $response['message'] = 'No file uploaded or file error';
            }
            
            header('Content-Type: application/json');
            echo json_encode($response);
            return;
        }

        // Handle CSV import
        if(S_POST('action') == 'import_csv') {
            $csvData = $_POST['csv_data'];
            $columnMapping = $_POST['column_mapping'];
            $importMode = $_POST['import_mode'] ?? 'create';

            if(empty($csvData) || empty($columnMapping)) {
                $response = ['success' => false, 'message' => 'Missing CSV data or column mapping'];
                header('Content-Type: application/json');
                echo json_encode($response);
                return;
            }

            $result = $this->importCSVData($csvData, $columnMapping, $postType, $currentLang, $importMode);
            
            $this->success($result);
        }
        // get template import
        if(S_POST('action') == 'template_import') {
            // $template = $this->_templateImport($postType);
            $template = [];
            header('Content-Type: application/json');
            echo json_encode($template);
            return;
        }



        // Get available fields for mapping
        $availableFields = $this->getAvailableFieldsForImport($postType);
        
        $this->data('posttype', $postType);
        $this->data('languages', $languages);
        $this->data('currentLang', $currentLang);
        $this->data('availableFields', $availableFields);
        $this->data('title', __('Import') . ' ' . $postType['name']);
        
        echo Render::html('Backend/post_import', $this->data);
    }

    private function parseCSV($filePath) {
        if(!file_exists($filePath)) {
            return false;
        }

        $csvData = [];
        $handle = fopen($filePath, 'r');
        
        if($handle !== false) {
            $headers = fgetcsv($handle);
            if($headers === false) {
                fclose($handle);
                return false;
            }
            
            // Clean headers
            $headers = array_map('trim', $headers);
            $csvData['headers'] = $headers;
            $csvData['rows'] = [];
            
            while(($row = fgetcsv($handle)) !== false) {
                $csvData['rows'][] = array_map('trim', $row);
            }
            
            fclose($handle);
            return $csvData;
        }
        
        return false;
    }

    private function getAvailableFieldsForImport($postType) {
        $fields = [];
        // Basic fields
        $fields[] = ['field_name' => 'id', 'label' => 'ID', 'type' => 'Number', 'required' => false];
        $fields[] = ['field_name' => 'title', 'label' => 'Title', 'type' => 'Text', 'required' => true];
        $fields[] = ['field_name' => 'slug', 'label' => 'Slug', 'type' => 'Text', 'required' => false];
        $fields[] = ['field_name' => 'status', 'label' => 'Status', 'type' => 'Select', 'required' => false];
        $fields[] = ['field_name' => 'created_at', 'label' => 'Created At', 'type' => 'Date', 'required' => false];
        $fields[] = ['field_name' => 'updated_at', 'label' => 'Updated At', 'type' => 'Date', 'required' => false];
        // Custom fields
        if(!empty($postType['fields'])) {
            foreach($postType['fields'] as $field) {
                $fields[] = $field;
            }
        }
        
        return $fields;
    }

    private function importCSVData($csvData, $columnMapping, $postType, $currentLang, $importMode = 'create') {
        $response = ['success' => false, 'message' => '', 'imported' => 0, 'errors' => []];
        

        if(!is_array($csvData)) {
            $csvData = json_decode($csvData, true);
        }
        if(!is_array($columnMapping)) {
            $columnMapping = json_decode($columnMapping, true);
        }
        
        if(empty($csvData['rows'])) {
            $response['message'] = 'No data to import';
            return $response;
        }

        $currentId = $postType['current_id'] ?? 0;
        $imported = 0;
        $errors = [];
        $errorRows = []; // Store failed rows with details
        $languages = is_string($postType['languages']) ? json_decode($postType['languages'], true) : [];
        
        // Validate fields for rules
        $rules = $this->convert_rules($postType['fields']);
        $validator = new Validate();

        // Build header index for fast lookup
        $headers = isset($csvData['headers']) && is_array($csvData['headers']) ? $csvData['headers'] : [];
        $headerIndex = [];
        if (!empty($headers)) {
            foreach($headers as $hIndex => $hName) {
                $headerIndex[$hName] = $hIndex;
            }
        }

        foreach($csvData['rows'] as $rowIndex => $row) {
            try {
                // Map CSV row -> post data based on columnMapping
                $postDataFields = [];
                if (!empty($columnMapping) && is_array($columnMapping)) {
                    foreach($columnMapping as $fieldName => $csvHeaderName) {
                        if($csvHeaderName === null || $csvHeaderName === '') continue;
                        if(isset($headerIndex[$csvHeaderName])) {
                            $colIdx = $headerIndex[$csvHeaderName];
                            if(isset($row[$colIdx])) {
                                $postDataFields[$fieldName] = $row[$colIdx];
                            }
                        }
                    }
                }
                // Preserve raw row for debugging
                $terms_list = [];
                $postDataFields = $this->normalizeData($postDataFields, $postType['fields']);

                // Default fallbacks for common meta fields if still missing after mapping
                if (empty($postDataFields['search_string']) && !empty($postDataFields['title'])) {
                    $postDataFields['search_string'] = keyword_slug($postDataFields['title']);
                }
                if (empty($postDataFields['seo_title']) && !empty($postDataFields['title'])) {
                    $postDataFields['seo_title'] = $postDataFields['title'];
                }

                // Validate data using existing rules
                if(!$validator->check($postDataFields, $rules)) {
                    $fieldErrors = $validator->getErrors();
                    $rowErrors = [];
                    foreach($fieldErrors as $field => $fieldError) {
                        $errors[] = "Row " . ($rowIndex + 1) . " - {$field}: " . implode(', ', $fieldError);
                        $rowErrors[] = "{$field}: " . implode(', ', $fieldError);
                    }
                    $errorRows[] = [
                        'row_index' => $rowIndex + 1,
                        'data' => $row,
                        'mapped_data' => $postDataFields,
                        'errors' => $rowErrors
                    ];
                    continue;
                }

                $tableName = posttype_name($postType['slug'], $currentLang);
                
                // Handle different import modes
                $existingPost = null;
                if(in_array($importMode, ['update', 'overwrite'])) {
                    $existingPost = $this->postsModel->getPostBySlug($tableName, $postDataFields['slug']);
                }

                if($importMode === 'update' && $existingPost) {
                    // Update existing post
                    unset($postDataFields['created_at']);
                    $postDataFields['updated_at'] = date('Y-m-d H:i:s');
                    
                    if($this->postsModel->editPost($tableName, $existingPost['id'], $postDataFields)) {
                        $imported++;
                    } else {
                        $errors[] = "Row " . ($rowIndex + 1) . ": Failed to update post";
                        $errorRows[] = [
                            'row_index' => $rowIndex + 1,
                            'data' => $row,
                            'mapped_data' => $postDataFields,
                            'errors' => ['Failed to update post']
                        ];
                    }
                } elseif($importMode === 'overwrite' && $existingPost) {
                    // Delete existing post and create new one
                    $this->postsModel->deletePost($tableName, $existingPost['id']);
                    
                    $curent_id = $postType['current_id'] ?? 0;
                    $data = [
                        'id' => ($curent_id + 1),
                        'title' => $postDataFields['title'],
                        'slug' => $this->checkPostSlug($postDataFields['slug'], $postType['slug'], $currentLang),
                        'status' => $postDataFields['status'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];

                    // Add custom fields
                    foreach($postType['fields'] as $field) {
                        if(isset($postDataFields[$field['field_name']])) {
                            if ($field['type'] == 'Number'){
                                if ($postDataFields[$field['field_name']] === ""){
                                    $data[$field['field_name']] = null;
                                }else{
                                    $data[$field['field_name']] = (int)$postDataFields[$field['field_name']];
                                }
                            } else {
                                $data[$field['field_name']] = is_array($postDataFields[$field['field_name']]) ? json_encode($postDataFields[$field['field_name']]) : $postDataFields[$field['field_name']];
                            }
                        }
                    }

                    $this->_add($postType, $data, $languages, $terms_list, $currentLang);
                    $postType['current_id'] = $data['id'];
                    $imported++;
                } else {
                    // Create new post (default mode or when post doesn't exist)
                    if($importMode !== 'create' || !$existingPost) {
                        $curent_id = $postType['current_id'] ?? 0;
                        $data = [
                            'id' => ($curent_id + 1),
                            'title' => $postDataFields['title'],
                            'slug' => $this->checkPostSlug($postDataFields['slug'], $postType['slug'], $currentLang),
                            'status' => $postDataFields['status'],
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];

                        // Add custom fields
                        foreach($postType['fields'] as $field) {
                            if(isset($postDataFields[$field['field_name']])) {
                                if ($field['type'] == 'Number'){
                                    if ($postDataFields[$field['field_name']] === ""){
                                        $data[$field['field_name']] = null;
                                    }else{
                                        $data[$field['field_name']] = (int)$postDataFields[$field['field_name']];
                                    }
                                } else {
                                    $data[$field['field_name']] = is_array($postDataFields[$field['field_name']]) ? json_encode($postDataFields[$field['field_name']]) : $postDataFields[$field['field_name']];
                                }
                            }
                        }

                        $this->_add($postType, $data, $languages, $terms_list, $currentLang);
                        $postType['current_id'] = $data['id'];
                        $imported++;
                    } else {
                        $errors[] = "Row " . ($rowIndex + 1) . ": Post with slug '{$postDataFields['slug']}' already exists (skipped in create mode)";
                        $errorRows[] = [
                            'row_index' => $rowIndex + 1,
                            'data' => $row,
                            'mapped_data' => $postDataFields,
                            'errors' => ["Post with slug '{$postDataFields['slug']}' already exists"]
                        ];
                    }
                }

            } catch(\Exception $e) {
                $errors[] = "Row " . ($rowIndex + 1) . ": " . $e->getMessage();
                $errorRows[] = [
                    'row_index' => $rowIndex + 1,
                    'data' => $row,
                    'mapped_data' => isset($postDataFields) ? $postDataFields : [],
                    'errors' => [$e->getMessage()]
                ];
            }
        }

        $response['success'] = true;
        $response['imported'] = $imported;
        $response['errors'] = $errors;
        $response['error_rows'] = $errorRows;
        $response['message'] = "Chunk completed. $imported posts imported.";
        
        return $response;
    }

    
    public function edit($id) {
            Render::asset('js', 'js/jstring.1.1.0.js', ['area' => 'backend', 'location' => 'head']);

            $fields = "*";
            $postTypeSlug = S_POST('type') ?? (S_GET('type') ?? 'post' );
            if(!isset($id)) {
                redirect(admin_url('/'));
                return;
            }
            $postType = $this->postsModel->getPostTypeBySlug($postTypeSlug);
            if(empty($postType)) {
            redirect(admin_url('/'));
                return;
            }
            $languagesPosttype = is_string($postType['languages']) ? json_decode($postType['languages'],true) : [];
            if(empty($this->post_lang)) $this->post_lang = $languagesPosttype[0];


            $postType['fields'] = is_string($postType['fields']) ? json_decode($postType['fields'], true) : $postType['fields'];
            if(!empty($postType['fields'])) {
                foreach($postType['fields'] as $field) {
                    if($field['type'] === 'Point') $fields .= ',  ST_AsText('.$field['field_name'].') as '.$field['field_name'];
                }
            }
            $tableName = posttype_name($postType['slug'], $this->post_lang);
            $existingPost = $this->postsModel->getPostById($tableName, $id, $fields);
            if(!$existingPost) {
                redirect(admin_url('posts').'?type='.$postTypeSlug);
                return;
            }
            // different language posttype 
            $langHasPost = [];
            foreach($languagesPosttype as $lang) {
                $tableName = posttype_name($postType['slug'], $lang);
                if($this->postsModel->getPostById($tableName, $id)) {
                    $langHasPost[] = $lang;
                }
            }

            if(!empty($postType['fields'])) {
                foreach($postType['fields'] as $i => $field) {
                    if($field['type'] == 'Reference') {
                        $post_status_filter = $field['post_status_filter'] ?? '';
                        $post_query_filter = $field['post_query_filter'] ?? '';
                        $post_query_sort = $field['post_query_sort'] ?? '';
                        $where = [];
                        $sql = "";
                        if ($post_status_filter !== 'all') {
                            $where[] = "status = '$post_status_filter'";
                        }
                        if (!empty($post_query_filter)) {
                            $where[] = $post_query_filter; 
                        }
                        if (!empty($where)) {
                            $sql .= implode(' AND ', $where);
                        }
                        if(!empty($sql)) {
                            $sql = " WHERE $sql";
                        }
                        if (!empty($post_query_sort)) {
                            $sql .= " ORDER BY $post_query_sort"; 
                        }
                        $postType['fields'][$i]['data'] = $this->postsModel->getPostByQuery(posttype_name($field['post_type_reference'],$this->post_lang),  $sql);
                        if(isset($field['table_save_data_reference']) && $field['table_save_data_reference'] == 1) {
                            $tableRelation = table_posttype_relationship($postType['slug']);
                            $listPost = $this->postsModel->getAllRelPostByPostId($tableRelation, $field['post_type_reference'], $field['id'], $id,$this->post_lang);
                        } else {
                            $tableRelation = table_posttype_relationship($field['post_type_reference']);
                            $listPost = $this->postsModel->getAllPostIdByRenference($tableRelation, $postType['slug'], $field['id'], $id,$this->post_lang);
                            
                        }
                        $existingPost[$field['field_name']] = $listPost;
                    } elseif($field['type'] == 'User') {
                        $userList =  $this->usersModel->getUsers();
                        $postType['fields'][$i]['data'] = $userList;
                    }
                }
            }
            

            $tableRelation = table_posttype_relationship($postType['slug']);
            $termActive = $this->postsModel->getTermsbyPostIDAndLang($tableRelation, $id, $this->post_lang);
            $termActiveIds = array_column($termActive, 'rel_id');
            $existingPost['terms'] =  $termActiveIds;
            $postType['terms'] = is_string($postType['terms']) ? json_decode($postType['terms'], true) : $postType['terms'];

            if(S_POST('type')) {
                if(!in_array($this->post_lang, $languagesPosttype)) {
                    redirect(admin_url('posts').'?type='.$postTypeSlug);
                } 

                
                $postDataFields = $_POST;
                $newtermActiveIds = $postDataFields['terms'] ?? [];
                if(is_string($newtermActiveIds)) {
                    $newtermActiveIds = json_decode($newtermActiveIds, true);
                }
                // kiểm tra xem người dùng có gửi lên ngày tạo không, nếu có thì người dùng gửi 1 vài kiểu dữ liệu time thì cũng format về time chuẩn
                $updated_at = date('Y-m-d H:i:s');
                if(isset($postDataFields['created_at'])) {
                    if(is_numeric($postDataFields['created_at'])) {
                        $created_at = date('Y-m-d H:i:s', $postDataFields['created_at']);
                    } else {
                        $created_at = date('Y-m-d H:i:s', strtotime($postDataFields['created_at']));
                    }

                    // check nếu ngày tạo > hiện tại thì sửa update sang hiện tại luôn
                    if($created_at > date('Y-m-d H:i:s')) {
                        $updated_at = $postDataFields['created_at'];
                    }
                }
                $data = [
                    'title' => S_POST('title'),
                    'slug' => S_POST('slug'),
                    'status' => S_POST('status'),
                    'updated_at' => $updated_at,
                ];
                if(isset($created_at)) {
                    $data['created_at'] = $created_at;
                }
                
                $data['slug'] = $this->checkPostSlug($data['slug'], $postType['slug'], $this->post_lang, $id );
                $postType['fields'] = is_string($postType['fields']) ? json_decode($postType['fields'], true) : $postType['fields'];
                $rules = $this->convert_rules($postType['fields']);
                $validator = new Validate();
                if(!$validator->check($postDataFields, $rules)) {
                    $errors = $validator->getErrors();
                    $this->data('errors', $errors);
                } else {
                    if(!empty($postType['fields'])) {
                        foreach($postType['fields'] as $field) {
                            if($field['type'] == 'Reference') {
                                // add lang for sync
                                if($field['synchronous'] == true) {
                                    $langRel = 'all';
                                } else {
                                    $langRel = $this->post_lang;
                                }
                                // handle 2 cases: add or delete
                                
                                // Check empty array before comparison
                                if (!isset($existingPost[$field['field_name']])) {
                                    // old data empty then take add as new data and don't delete
                                    $deletedIds = [];
                                    $addedIds = $postDataFields[$field['field_name']] ?? [];
                                } elseif (!isset($postDataFields[$field['field_name']])) {
                                    $deletedIds = $existingPost[$field['field_name']];
                                    $addedIds = [];
                                } else {
                                    // If both arrays have data then perform array_diff
                                    $deletedIds = array_diff($existingPost[$field['field_name']], $postDataFields[$field['field_name']]);
                                    $addedIds = array_diff($postDataFields[$field['field_name']], $existingPost[$field['field_name']]);
                                }
                                if($field['table_save_data_reference'] == 1) {
                                    $tableRelation = table_posttype_relationship($postType['slug']);
                                    if(!empty($deletedIds)) {
                                        foreach($deletedIds as $idRel) {
                                            $this->postsModel->deleteReferenceRelationship($tableRelation, $idRel, $postType['slug'] , $field['id'], $id, $langRel);
                                        }
                                    }
                                    if(!empty($addedIds)) {
                                        foreach($addedIds as $idRel) {
                                            $this->postsModel->addReferenceRelationship($tableRelation, $id, $field['post_type_reference'] , $field['id'], $idRel, $langRel);
                                        }
                                    }
                                } else {
                                    $tableRelation = table_posttype_relationship($field['post_type_reference']);
                                    if(!empty($deletedIds)) {
                                        foreach($deletedIds as $idRel) {
                                            $this->postsModel->deleteReferenceRelationship($tableRelation, $idRel, $postType['slug'] , $field['id'], $id, $langRel);
                                        }
                                    }
                                    if(!empty($addedIds)) {
                                        foreach($addedIds as $idRel) {
                                            $this->postsModel->addReferenceRelationship($tableRelation, $idRel, $postType['slug'] , $field['id'], $id, $langRel);
                                        }
                                    }
                                }
                                
                            } elseif(($field['type'] == 'Point')) {
                                if(isset($postDataFields[$field['field_name']]) && count($postDataFields[$field['field_name']]) === 2) {
                                    $latitude = $postDataFields[$field['field_name']][0];
                                    $longitude = $postDataFields[$field['field_name']][1];
                                    if($latitude >= 90 || $latitude <= -90 || $longitude >= 180 || $longitude <= -180) continue;
                                    $data[$field['field_name']] = [
                                        'expr' => "ST_GeomFromText('POINT($longitude $latitude)')",
                                        'params' => []
                                    ];
                                }
                            }else {
                                if(isset($postDataFields[$field['field_name']])) {
                                    if ($field['type'] == 'Number'){
                                        if ($postDataFields[$field['field_name']] === ""){
                                            $data[$field['field_name']] = null;
                                        }else{
                                            $data[$field['field_name']] = (int)$postDataFields[$field['field_name']];
                                        }
                                    }else{
                                        $data[$field['field_name']] = is_array($postDataFields[$field['field_name']]) ? json_encode($postDataFields[$field['field_name']]) : $postDataFields[$field['field_name']];
                                    }
                                }
                            }
                        }
                    }
                    $this->_edit($postType, $id, $data, $this->post_lang, $newtermActiveIds, $termActiveIds);
                    redirect(admin_url('posts').'?type='.$postTypeSlug.'&post_lang='.$this->post_lang);
                };           
            }      
            $postType['fields'] = $this->_loadDefaultInputs($postType['fields']);
            $postType = $this->_loadTermInputs($postType, $postType['terms']);
            $fields = $this->layoutField($postType['fields']);
            $this->data('currentLang', $this->post_lang);
            $this->data('languages', $languagesPosttype);
            $this->data('langHasPost', $langHasPost);
            $this->data('posttype', $postType); 
            $this->data('post', $existingPost);
            $this->data('fields', $fields);
            //Render::asset('css', 'css/forms.css', ['area' => 'backend', 'location' => 'footer']);
            // Render::asset('js', 'js/forms.js', ['area' => 'backend', 'location' => 'footer']);
            $this->data('title', __('Edit'). ' ' . $postType['name']);
            echo Render::html('Backend/posts_add', $this->data);
        }

    private function _edit($postType, $id, $data, $currentLang, $newtermActiveIds, $termActiveIds) {
        $tableName = posttype_name($postType['slug'], $currentLang);
        $tableRelation = table_posttype_relationship($postType['slug']);
        $terms_field = is_string( $postType['terms']) ? json_decode( $postType['terms'], true) :  $postType['terms'];
        $termTrueSync = [];
        if(!empty($terms_field)) { 
            foreach($terms_field as $term_field) { 
                if($term_field['synchronous_init'] === 'true') {
                    $termsbyLang = $this->termsModel->getTermsByTypeAndPostTypeAndLang($postType['slug'], $term_field['type'],  $currentLang );
                    foreach($termsbyLang as $term) {
                        if( $term['id_main'] == 0 ) {
                            $termTrueSync[] = $term['id'];
                        } else {
                            $termTrueSync[] = $term['id_main'];
                        }
                    }
                }
            }
        };
        // Update post data
        if ($this->postsModel->editPost($tableName, $id, $data)){
            \System\Libraries\Events::run('Backend\\PostsEditEvent', $data);
        }

        // $newtermActiveIds, $termActiveIds
        // convert to id_main all
        $newtermActiveIdsMain = [];
        if(!empty($newtermActiveIds)) { 
            foreach($newtermActiveIds as $newtermActiveId) { 
                $id_main = $this->termsModel->getTermById($newtermActiveId)['id_main']; 
                if(in_array($id_main, $termTrueSync )) {
                    if($id_main != 0) {
                        $newtermActiveIdsMain[] = $id_main;
                    } else {
                        $newtermActiveIdsMain[] = $newtermActiveId;
                    }
                } else {
                    $newtermActiveIdsMain[] = $newtermActiveId;
                }
                
            }
        }
        $termActiveIdsMain =[];
        if(!empty($termActiveIds)) {
            foreach($termActiveIds as $termActiveId) {
                $id_main = $this->termsModel->getTermById($termActiveId); 
                if(!empty($id_main)) {
                    $id_main = $id_main['id_main'];
                }
                if(in_array($id_main, $termTrueSync )) {
                    if($id_main != 0) {
                        $termActiveIdsMain[] = $id_main;
                    } else {
                        $termActiveIdsMain[] = $termActiveId;
                    }
                } else {
                    $termActiveIdsMain[] = $termActiveId;
                }
            }
        }
        
        

        $removedterms = array_diff($termActiveIdsMain, $newtermActiveIdsMain);
        $addedterms = array_diff($newtermActiveIdsMain, $termActiveIdsMain);
    
        if(!empty($addedterms)) {
            foreach($addedterms as $term) {
                if(in_array($term, $termTrueSync)) {
                    $this->postsModel->createRelationship($tableRelation, $id, $term, 'all');
                }
                elseif (in_array($id_main, $termTrueSync)) {
                    $this->postsModel->createRelationship($tableRelation, $id, $id_main, 'all');
                }
                else {
                    $this->postsModel->createRelationship($tableRelation, $id, $term, $currentLang);

                }
            }
        }

        if(!empty($removedterms)) {
            foreach($removedterms as $term) {
                $this->postsModel->removeTerms($tableRelation, $id, $term);
                
            }
        }
        
    }

    public function delete($id = null) {
        if($id) {
            $posttype_slug = S_GET('type');
            if(empty($posttype_slug)) {
                // 
                redirect(admin_url());
            }
            $result = $this->_delete($posttype_slug, $id);
            redirect(admin_url('posts').'?type='.$posttype_slug);
        } elseif(S_POST('ids') && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $ids = S_POST('ids');
            $posttype_slug = S_POST('type');
            if(empty($posttype_slug)) {
               $this->error('posttype_not_found');
            }
            
            // Parse JSON if it's a string
            if (is_string($ids)) {
                $ids = json_decode($ids, true);
            }
            
            if (!is_array($ids)) {
                $this->error('Invalid ids format');
            }
            
            $result = [];
            foreach($ids as $id) {
                $result[] = $this->_delete($posttype_slug, $id);
            }
            $this->success($result, 'Success');
        } else {
            // Handle non-AJAX requests or invalid requests
            redirect(admin_url('posts'));
        }
        
    }

    private function _delete($posttype_slug, $id) {
        // handle after deletion
        // delete terms of current language
        // check other languages if not then delete language all
        // relationship similar
        // delete relations of current language
        // check other posts if not then delete all relationship
            $tableRel = table_posttype_relationship($posttype_slug);
            $postType = $this->postsModel->getPostTypeBySlug($posttype_slug);
            $post_field = is_string($postType['fields']) ? json_decode($postType['fields'], true) : $postType['fields'];
            $languages = json_decode($postType['languages'], true);
            $currentLang = S_GET('post_lang');
            // only delete current post
            if($currentLang) {
                $tableName = posttype_name($posttype_slug, $currentLang);
                
                // Delee post data
                if ($this->postsModel->deletePost($tableName, $id)){
                    \System\Libraries\Events::run('Backend\\PostsDeleteEvent', $id);
                }

                // remove terms off lang
                $this->postsModel->removeTermRelsbyPostAndLang($tableRel, $id, $currentLang);
                // check existence difference language
                $existingPost = false;
                foreach($languages as $lang) {
                    $post = $this->postsModel->getPostById(posttype_name($postType['slug'], $lang), $id);
                    if($post) {
                        $existingPost = true;
                        break;
                    }
                }
                if($existingPost) {
                    // delete all relationship references
                    // before deleting check where data is stored =.=
                    // loop through field check reference file 
                    // check reference_save_data 
                    // if 1 then save in current posttype relationship table
                    // if not then save in reference posttype relationship table
                    if(!empty($post_field)) {
                        foreach($post_field as $field) {
                            if($field['type'] === 'Reference') {
                                if($field['table_save_data_reference'] == 1) {
                                    $tableRelation = table_posttype_relationship($postType['slug']);
                                $this->postsModel->deleteReferenceRelationship($tableRelation, $id, $field['post_type_reference'], $field['id'], null , $currentLang);
                                } else {
                                    $tableRelation = table_posttype_relationship($field['post_type_reference']);
                                    $this->postsModel->deleteReferenceRelationship($tableRelation, null , $postType['slug'], $field['id'], $id ,$currentLang);
                                }
                            }
                        }
                    }                
                    
                } else {
                    if(!empty($post_field)) {
                        foreach($post_field as $field) {
                            if($field['type'] === 'Reference') {
                                if($field['table_save_data_reference'] == 1) {
                                    $tableRelation = table_posttype_relationship($postType['slug']);
                                $this->postsModel->deleteReferenceRelationship($tableRelation, $id, $field['post_type_reference'], $field['id'], null, null);
                                } else {
                                    $tableRelation = table_posttype_relationship($field['post_type_reference']);
                                    $this->postsModel->deleteReferenceRelationship($tableRelation, null , $postType['slug'], $field['id'], $id, null);
                                }
                            }
                        }
                    }
                    $this->postsModel->removeTermRelsbyPost($tableRel, $id);
                }
                //
                
            } else {
                // delete all =.=
                
                foreach($languages as $lang) {
                    $tableName = posttype_name($postType['slug'], $lang);
                    // Delee post data
                    if ($this->postsModel->deletePost($tableName, $id)){
                        \System\Libraries\Events::run('Backend\\PostsDeleteEvent', $id);
                    }
                    
                }
                $this->postsModel->removeTermRelsbyPost($tableRel, $id);
                if(!empty($post_field)) {

                    foreach($post_field as $field) {
                        if($field['type'] == 'Reference') {
                            if($field['table_save_data_reference'] == 1) {
                                $tableRelation = table_posttype_relationship($postType['slug']);

                                $this->postsModel->deleteReferenceRelationship($tableRelation, $id, $field['post_type_reference'], $field['id'], null, null);
                            } else {
                                $tableRelation = table_posttype_relationship($field['post_type_reference']);
                                $this->postsModel->deleteReferenceRelationship($tableRelation, null , $postType['slug'], $field['id'], $id, null);
                            }
                        }
                    }
                }
            }
            return true;
    }

    public function clone($id) {
        $postTypeSlug = S_GET('type') ?? 'post'; 
        if(!S_GET('post_lang')) {
            redirect(admin_url('posts').'?type='.$postTypeSlug);
        }
        $oldlang = S_GET('oldpost_lang');
        $languages = explode(',', S_GET('post_lang'));
        $oldPost = $this->postsModel->getPostById(posttype_name($postTypeSlug, $oldlang), $id);
        $postType = $this->postsModel->getPostTypeBySlug($postTypeSlug);

        // check old post exits if no existing language then get language fifferent
        if(!$oldPost) {
            $languagesPostType = json_decode($postType['languages'], true);
            foreach($languagesPostType as $lang) {
            $post = $this->postsModel->getPostById(posttype_name($postTypeSlug, $lang), $id);
            if($post) {
                $oldlang = $lang;
            }
            }
        };

        $tablesName = [];
        $terms_field = is_string( $postType['terms']) ? json_decode( $postType['terms'], true) : '';
        $post_field = is_string( $postType['fields']) ? json_decode( $postType['fields'], true) : '';
        foreach($languages as $index =>  $lang) {
            $tableName = posttype_name($postTypeSlug, $lang);
            if(!$this->postsModel->getPostById($tableName, $id)) {
                $tablesName[] = $tableName;
            }
            $oldTableName = posttype_name($postTypeSlug, $oldlang);
            $this->postsModel->duplicateLanguage($tablesName, $oldTableName, $id);
            // fields referenced handling relationship if first synchronous
            foreach($post_field as $field) {
                if($field['type'] === 'Reference' && $field['synchronous'] === 'first') {
                    // get all relations old lang 
                    if($field['table_save_data_reference'] == 1) {
                        $tableRelation = table_posttype_relationship($postType['slug']);
                        $listPost = $this->postsModel->getAllRelPostByPostId($tableRelation, $field['post_type_reference'], $field['id'], $id, $oldlang, false);
                        if(!empty($listPost)) {
                            foreach($listPost as $item) {
                                $this->postsModel->addReferenceRelationship($tableRelation, $id, $field['post_type_reference'], $field['id'] , $item, $lang );
                            }
                        }
                    } else {
                        $tableRelation = table_posttype_relationship($field['post_type_reference']);
                        $listPost  = $this->postsModel->getAllPostIdByRenference($tableRelation, $postType['slug'], $field['id'], $id, $oldlang, false);
                        if(!empty($listPost)) {
                            foreach($listPost as $item) {
                                $this->postsModel->addReferenceRelationship($tableRelation, $id, $postType['slug'], $field['id'] , $item, $lang );
                            }
                        }

                        
                    };
                }
            }
            // terms hanlde
            if(!empty($terms_field)) { 
                $initTerms = [];
                foreach($terms_field as $term_field) {
                    if($term_field['synchronous_init'] === 'first') {
                        $termsOldLang = $this->termsModel->getTermsByTypeAndPostTypeAndLang($postTypeSlug, $term_field['type'],  $oldlang );
                        $termsNewLang = $this->termsModel->getTermsByTypeAndPostTypeAndLang($postTypeSlug, $term_field['type'],  $languages[$index] );
                        foreach ($termsNewLang as $newTerm) {
                            foreach ($termsOldLang as $oldTerm) {
                                if ($oldTerm['id_main'] == $newTerm['id']) {
                                    $initTerms[] = $newTerm['id'];
                                } elseif ($oldTerm['id_main'] == $newTerm['id_main']) {
                                    $initTerms[] = $newTerm['id'];
                                } elseif($oldTerm['id'] == $newTerm['id_main']) {
                                    $initTerms[] = $newTerm['id'];
                                }
                            }
                        }
                    }
                }

                if(!empty($initTerms)) {
                    $tableRel = table_posttype_relationship($postTypeSlug);
                    foreach($initTerms as $initTerm) {
                    $this->postsModel->createRelationship($tableRel, $id, $initTerm, $languages[$index]);
                    }
                }
            }
        }
        
        redirect(admin_url('posts/edit/' . $id ).'?type='.$postTypeSlug . '&post_lang=' . $languages[0]);
    }

    // xoi fix missing too much
    private function convert_rules($fields) {
        $rules = [];

        if (!empty($fields)) {
            foreach ($fields as $field) {
                $fieldName = $field['field_name'];
                $isRequired = !empty($field['required']); // Check if field is required

                $rules[$fieldName] = [
                    'rules'    => [],
                    'messages' => []
                ];

                // If required => add notEmpty rule
                if ($isRequired) {
                    $rules[$fieldName]['rules'][]    = Validate::notEmpty();
                    $rules[$fieldName]['messages'][] = __('not_empty');
                }

                // Check minimum and maximum length
                if (!empty($field['min']) || !empty($field['max'])) {
                    $min = !empty($field['min']) ? $field['min'] : 1;
                    $max = !empty($field['max']) ? $field['max'] : 255;

                    // If not required => wrap rule with Validate::optional
                    $lengthRule = Validate::length($min, $max);
                    if (!$isRequired) {
                        $lengthRule = Validate::optional($lengthRule);
                    }

                    $rules[$fieldName]['rules'][]    = $lengthRule;
                    $rules[$fieldName]['messages'][] = __('not_min_max');
                }

                // Check Number type
                if ($field['type'] == 'Number') {
                    $numericRule = Validate::numericVal();
                    if (!$isRequired) {
                        $numericRule = Validate::optional($numericRule);
                    }

                    $rules[$fieldName]['rules'][]    = $numericRule;
                    $rules[$fieldName]['messages'][] = "{$field['label']} must be a number.";
                }

                // Check if it's slug
                if ($fieldName == 'slug') {
                    $slugRule = Validate::lowercase();
                    if (!$isRequired) {
                        $slugRule = Validate::optional($slugRule);
                    }

                    $rules[$fieldName]['rules'][]    = $slugRule;
                    $rules[$fieldName]['messages'][] = __('lowercase');
                }

                // Check if it's email
                if ($field['type'] == 'Email') {
                    $emailRule = Validate::email();
                    if (!$isRequired) {
                        $emailRule = Validate::optional($emailRule);
                    }

                    $rules[$fieldName]['rules'][]    = $emailRule;
                    $rules[$fieldName]['messages'][] = __('email_valid');
                }

                // Check if it's URL
                if ($field['type'] == 'URL') {
                    $urlRule = Validate::url();
                    if (!$isRequired) {
                        $urlRule = Validate::optional($urlRule);
                    }

                    $rules[$fieldName]['rules'][]    = $urlRule;
                    $rules[$fieldName]['messages'][] = __('url_valid');
                }

                // Check if it's date
                if ($field['type'] == 'Date') {
                    $dateRule = Validate::date();
                    if (!$isRequired) {
                        $dateRule = Validate::optional($dateRule);
                    }

                    $rules[$fieldName]['rules'][]    = $dateRule;
                    $rules[$fieldName]['messages'][] = __('date_valid');
                }
            }
        }

        return $rules;
    }



    private function checkPostSlug($post_slug, $postTypeSlug, $lang, $curent_id = null) {
        $tableName = posttype_name($postTypeSlug, $lang);
        $original_slug = $post_slug;
        $counter = 2;

        while (true) {
            // Get post with current slug
            $existingPost = $this->postsModel->getPostBySlug($tableName, $post_slug);
            
            // If no post matches or matching post is current_id
            if (!$existingPost || ($curent_id && $existingPost['id'] == $curent_id)) {
                break;
            }
            
            // If matches another post, add number to end of slug
            $post_slug = $original_slug . '-' . $counter;
            $counter++;
        }

        return $post_slug;
    }  

    private function layoutField($fields) {
        // POST TYPE FIELDS
        $fields = is_string($fields) 
            ? json_decode($fields, true) 
            : $fields;
        
        $fieldsTop = [];
        $fieldsLeft = [];
        $fieldsRight = [];
        $fieldsBottom = [];
        if(!empty($fields)) {
            foreach ($fields as $field) {
                $field['position'] = $field['position'] ?? 'left';
                switch($field['position']) {
                    case 'top':
                        $fieldsTop[] = $field;
                        break;
                    case 'left':
                        $fieldsLeft[] = $field;
                        break;
                    case 'right':
                        $fieldsRight[] = $field;
                        break;
                    case 'bottom':
                        $fieldsBottom[] = $field;
                        break;
                }
            }
        }

        return [
            'top' => $fieldsTop,
            'left' => $fieldsLeft,
            'right' => $fieldsRight,
            'bottom' => $fieldsBottom
        ];

    }


    private function normalizeData($data, $fields) {
        if(empty($data['search_string']) && !empty($data['title'])) {
            $data['search_string'] = keyword_slug($data['title']);
        } elseif(!empty($data['search_string'])) {
            $data['search_string'] = keyword_slug($data['search_string']);
        }
        if(empty($data['slug']) && !empty($data['title'])) {
            $data['slug'] = url_slug($data['title']);
        } elseif(!empty($data['slug'])) {
            $data['slug'] = url_slug($data['slug']);
        }
        if(empty($data['status'])) {
            $data['status'] = 'active';
        }
        if(empty($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if(empty($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // Xử lý các field đặc biệt
        foreach($fields as $field) {
            $name = $field['field_name'];
            $type = $field['type'];
            $default = isset($field['default_value']) ? $field['default_value'] : null;
            $isRequired = !empty($field['required']);
            // Boolean/Checkbox
            if(in_array($type, ['Checkbox', 'Boolean'])) {
                if(!isset($data[$name]) || $data[$name] === '') {
                    $data[$name] = $default !== null ? $default : 0;
                }
            }

            if(in_array($type, ['Boolean'])) {
                $data[$name] = $data[$name] === 1 || $data[$name] === '1'  || $data[$name] === true  || $data[$name] === 'true' || $data[$name] === 'on' || $data[$name] === 'yes' ? 1 : 0;
            }
            // Select/Radio/Ratio
            if(in_array($type, ['Select', 'Radio', 'Ratio'])) {
                if(!isset($data[$name]) || $data[$name] === '') {
                    $data[$name] = $default !== null ? $default : '';
                }
            }
            // Date
            if($type == 'Date' && !empty($data[$name])) {
                $data[$name] = date('Y-m-d H:i:s', strtotime($data[$name]));
            }
            // Required
            if($isRequired && (!isset($data[$name]) || $data[$name] === '')) {
                $data[$name] = $default !== null ? $default : (in_array($type, ['Checkbox', 'Boolean']) ? 0 : '');
            }

            // nếu là số thì là int 
            if($type == 'Number') {
                // Nếu không có giá trị, gán mặc định (ưu tiên default_value, nếu không có thì 0)
                if(!isset($data[$name]) || $data[$name] === '' || $data[$name] === null) {
                    $data[$name] = ($default !== null && $default !== '') ? (int)$default : 0;
                } else {
                    // deteach các dấu , . chỉ khi là string/number
                    $value = $data[$name];
                    if(!is_string($value)) {
                        // Cho phép number/bool -> string
                        $value = (string)$value;
                    }
                    $value = str_replace(',', '', $value);
                    $value = str_replace('.', '', $value);
                    // Nếu sau khi làm sạch mà rỗng -> 0
                    $data[$name] = ($value === '' ? 0 : (int)$value);
                }
            }

            // user cho mặc định hoặc = 1
            if($type == 'User') {
                $data[$name] = $data[$name] ?: 1;
            }

            // date cho mặc định hoặc = now
            if($type == 'Date') {
                $data[$name] = $data[$name] ?: date('Y-m-d');
            }

            // DateTime
            if($type == 'DateTime') {
                $data[$name] = $data[$name] ?: date('Y-m-d H:i:s');
            }
        }
        return $data;
    }

    // add slug and title in field posttype
    private function _loadDefaultInputs($fields) {
        $title = [
            "id" => 0,
            "type" => "Text",
            "label" => "Title",
            "field_name" => "title",
            "description" => "",
            "required" => true,
            "order" => 1,
            "min" => 10,
            "max" => 100,
            "width_unit" => '%',
            "width" => 100,
            "position" => "top"

          ];
        $slug = [
            "id" => 510,
            "type" => "Text",
            "label" => "Slug",
            "field_name" => "slug",
            "description" => "",
            "required" => true,
            "autofill" => "title",
            "autofill_type" => "slug",
            "order" => 2,
            "max" => 100,
            "width_unit" => '%',
            "width" => 100,
            "position" => "top"
          ];
        array_unshift($fields, $slug);
        array_unshift($fields, $title);
        return $fields;
    }

    private function _loadTermInputs($posttype, $terms) {
        foreach($terms as $term) {
           $field = [
                "id" => 26,
                "type" => "Taxonomy",
                "label" => $term['name'],
                "field_name" => $term['type'],
                "description" => "Hierarchical taxonomy",
                "required" => false,
                "order" => 26,
                "posttype" => $posttype['slug'],
                "taxonomy" => $term['type'],
                "field_type" => "checkbox",
                "add_term" => true,
                "position" => "right"
            ];
            array_unshift($posttype['fields'], $field);

        }
        return $posttype;
    }
    
}