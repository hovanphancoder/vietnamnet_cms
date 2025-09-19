<?php
    namespace App\Controllers\Api\V1;
    use System\Core\BaseController;
    use App\Models\PostsModel;
use Respect\Validation\Rules\Lowercase;

    class LicenseController extends BaseController {
        protected $licenseModel;

        public function __construct(){
            parent::__construct();
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');

            $this->licenseModel = new PostsModel('license');
        }

        // GET LIST API HOME 
        public function index($domain = '') {
			$domain = $this->normalizeDomain($domain);
            if (!$this->isValidDomain($domain)) {
				return $this->error('License not valid', 403);
			}
            $licenseData = $this->licenseModel->getBySlug($domain);
            if($licenseData) {
                $status = $this->checkLincense($licenseData);
                if ($status){
                    $status = $licenseData;
                }
            } else {
                $status = $this->addLicense($domain);
                if ($status){
                    $status = $this->licenseModel->getBySlug($domain);
                }
            }
            return $this->success($status);
        } 

 
        private function addLicense($domain){
            // viet thuong domain
			$domain = $this->normalizeDomain($domain);
           $expiry_date = date('Y-m-d', strtotime('+2 year'));
            $data = [
            'slug' => $domain,
            'title' => $domain,
            'status' => 'active',
            'type_license' => 'free',
            'expiry_date' => $expiry_date
            ];
            if($this->licenseModel->insert($data)) {
                return true;
            } else {
                return false;
            }
        }

        private function checkLincense($licenseData){
            if($licenseData['status'] == 'active' && strtotime($licenseData['expiry_date']) > time()) {
                return true;
            } else {
                return false;
            }
        }
		
		private function isValidDomain(string $domain): bool
		{
			// Chuyển tên miền Unicode → ASCII (punycode)
			$ascii = idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);
			if ($ascii === false) {                       // Chuỗi Unicode không hợp lệ
				return false;
			}
			// PHP >=7.0: kiểm tra hostname
			return filter_var($ascii, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
		}

		/**
		 * Xoá www, chuẩn hoá về lowercase
		 */
		private function normalizeDomain(string $domain): string
		{
			$domain = strtolower(trim($domain));
			return str_starts_with($domain, 'www.') ? substr($domain, 4) : $domain;
		}
    }