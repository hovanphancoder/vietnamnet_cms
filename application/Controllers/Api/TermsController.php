<?php 
namespace App\Controllers\Api;

use App\Controllers\ApiController;
use App\Libraries\Fasttoken;
use System\Libraries\Session;
use System\Core\AppException;
use App\Models\FastModel;

class TermsController extends ApiController
{

    protected $posttypeModel;
    protected $termsModel;
    protected $postModel;
    protected $limit = 10;
    
    public function __construct() {
        parent::__construct();
        load_helpers(['string']);
        $this->posttypeModel = new FastModel(APP_PREFIX.'posttype');
        $this->termsModel = new FastModel(APP_PREFIX.'terms');
    }

    public function list($posttype = '', $type = '') {
        try {
            $this->_check_posttype($posttype);
            $terms = $this->termsModel->where('posttype', $posttype);
            if(!empty($type)) {
                $terms = $terms->where('type', $type);
            }
            $terms = $terms->get();
            // format terms theo type
            if(!empty($terms) && empty($type)) {
                foreach($terms as $term) {
                    $terms_array[$term['type']][] = $term;
                }
            } elseif(!empty($type)) {
                $terms_array = $terms;
            } else {
                $terms_array = [];
            }
            
            $this->success($terms_array, 'Terms list');
        } catch (AppException $e) {
            $this->error($e->getMessage(), [], 500);
        } catch (\Exception $e) {
            $this->error('Internal server error', [], 500);
        }
    }


    protected function _check_posttype($posttype) {

        if(empty($posttype)) {
            return false;
        }
        $posttype = $this->_get_posttype($posttype);
        if(empty($posttype)) {
            return false;
        }
        $posttype_lang = isset($posttype['languages']) && is_string($posttype['languages']) ? json_decode($posttype['languages'], true) : [];
        if($posttype_lang[0] == 'all') {
            $this->postModel = new FastModel(posttype_name($posttype['slug']));
            return $posttype;
        } elseif(in_array(APP_LANG, $posttype_lang)) {
            $this->postModel = new FastModel(posttype_name($posttype['slug'], APP_LANG));
            return $posttype;
        } else {
            return false;
        }
    }


    protected function _get_posttype($posttype_slug) {
        // put in global to avoid calling repeatedly like $posttype['slug']
        global $posttype;
        if(!isset($posttype['slug'])) {
            $posttype = $this->posttypeModel->where('slug', $posttype_slug)->first();
            if (!$posttype) {
                return false;
            }
        }
        return $posttype;
    }
}