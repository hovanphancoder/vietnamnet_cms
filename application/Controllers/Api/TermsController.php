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
        $this->posttypeModel = new FastModel('fast_posttype');
        $this->termsModel = new FastModel('fast_terms');
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
}