<?php

namespace App\Controllers\Backend;

use App\Controllers\BackendController;
use App\Models\PosttypeModel;
use System\Libraries\Render;
use System\Libraries\Validate;
use App\Libraries\Fastlang as Flang;

class PosttypeController extends BackendController {
    protected $posttypeModel;
    
    public function __construct()
    {
        parent::__construct();
        load_helpers(['backend', 'string']);
        Flang::load('Backend/Posttype');

        $this->posttypeModel = new PosttypeModel();
    }

    public function index() {
        $this->data('title', __('Post Types'));
        echo Render::html('Backend/posttype_index', $this->data);
    }

    public function add() {
        if (S_POST()) {
            $data = [
                'name' => S_POST('name'),
                'slug' => S_POST('slug'),
                'description' => S_POST('description'),
                'status' => S_POST('status', 'active'),
                'fields' => json_encode(S_POST('fields', [])),
                'terms' => json_encode(S_POST('terms', [])),
                'languages' => json_encode(S_POST('languages', [APP_LANG])),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $validator = new Validate();
            $rules = [
                'name' => 'required|min:3',
                'slug' => 'required|min:3|unique:fast_posttypes,slug'
            ];

            if ($validator->check($data, $rules)) {
                if ($this->posttypeModel->addPostType($data)) {
                    Session::flash('success', __('Post type created successfully'));
                    redirect(admin_url('posttype'));
                } else {
                    $this->data('errors', [__('Failed to create post type')]);
                }
            } else {
                $this->data('errors', $validator->getErrors());
            }
        }

        $this->data('title', __('Add Post Type'));
        echo Render::html('Backend/posttype_add', $this->data);
    }

    public function edit($id) {
        if (!$id) {
            redirect(admin_url('posttype'));
            return;
        }

        $posttype = $this->posttypeModel->getPostTypeById($id);
        if (!$posttype) {
            redirect(admin_url('posttype'));
            return;
        }

        if (S_POST()) {
            $data = [
                'name' => S_POST('name'),
                'slug' => S_POST('slug'),
                'description' => S_POST('description'),
                'status' => S_POST('status'),
                'fields' => json_encode(S_POST('fields', [])),
                'terms' => json_encode(S_POST('terms', [])),
                'languages' => json_encode(S_POST('languages', [APP_LANG])),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $validator = new Validate();
            $rules = [
                'name' => 'required|min:3',
                'slug' => 'required|min:3|unique:fast_posttypes,slug,' . $id
            ];

            if ($validator->check($data, $rules)) {
                if ($this->posttypeModel->updatePostType($id, $data)) {
                    Session::flash('success', __('Post type updated successfully'));
                    redirect(admin_url('posttype'));
                } else {
                    $this->data('errors', [__('Failed to update post type')]);
                }
            } else {
                $this->data('errors', $validator->getErrors());
            }
        }

        $this->data('posttype', $posttype);
        $this->data('title', __('Edit Post Type'));
        echo Render::html('Backend/posttype_edit', $this->data);
    }

    public function delete($id) {
        if (!$id) {
            redirect(admin_url('posttype'));
            return;
        }

        if ($this->posttypeModel->deletePostType($id)) {
            Session::flash('success', __('Post type deleted successfully'));
        } else {
            Session::flash('error', __('Failed to delete post type'));
        }

        redirect(admin_url('posttype'));
    }
}
