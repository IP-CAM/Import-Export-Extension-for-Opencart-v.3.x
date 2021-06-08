<?php
class ControllerExtensionModuleImportExportNik extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/import_export_nik');

		$this->document->setTitle($this->language->get('heading_title'));

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

        if (isset($this->error['empty_categories'])) {
            $data['error_empty_categories'] = $this->error['empty_categories'];
        } else {
            $data['error_empty_categories'] = '';
        }

        if (isset($this->error['empty_products'])) {
            $data['error_empty_products'] = $this->error['empty_products'];
        } else {
            $data['error_empty_products'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->request->get['type'])) {
            $type = $this->request->get['type'];
        } else {
            $type = 'categories';
        }

        $url = '';

        if (isset($this->request->get['type'])) {
            $url .= '&type=' . $type;
        }

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/import_export_nik', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

        $data['actionExportCategories'] = $this->url->link('extension/module/import_export_nik/exportCategories', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['actionImportCategories'] = $this->url->link('extension/module/import_export_nik/importCategories', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['actionExportProducts'] = $this->url->link('extension/module/import_export_nik/exportProducts', 'user_token=' . $this->session->data['user_token'] . $url, true);
        $data['actionImportProducts'] = $this->url->link('extension/module/import_export_nik/importProducts', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        $data['user_token'] = $this->session->data['user_token'];

        $data['link_categories'] = $this->url->link('extension/module/import_export_nik', 'user_token=' . $this->session->data['user_token'] . '&type=categories', true);
        $data['link_products'] = $this->url->link('extension/module/import_export_nik', 'user_token=' . $this->session->data['user_token'] . '&type=products', true);

        $data['type'] = $type;

        $this->load->model('catalog/category');

        $data['categories'] = array();

        $categories = $this->model_catalog_category->getCategories();

        foreach ($categories as $category) {
            if ($category) {
                $data['categories'][] = array(
                    'category_id' => $category['category_id'],
                    'name'       => $category['name']
                );
            }
        }

        $sort_order = array();

        foreach ($data['categories'] as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $data['categories']);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/import_export_nik', $data));
	}

    public function getCategoryByCategory() {
        $json = array();
        $results = array();

        if (isset($this->request->get['category_id'])) {
            $this->load->model('catalog/category');
            $this->load->model('extension/module/import_export_nik');
            if ($this->request->get['category_id']) {
                // get by id
                $results[] = $this->model_catalog_category->getCategory($this->request->get['category_id']);
            } else {
                // get all
                $results = $this->model_extension_module_import_export_nik->getCategoriesList();
            }

            foreach ($results as $result) {
                $json[] = array(
                    'category_id'=> $result['category_id'],
                    'name'       => $result['name'],
                );
            }
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getProductsByCategory() {
        $json = array();

        if (isset($this->request->get['category_id'])) {
            $this->load->model('catalog/product');
            $this->load->model('extension/module/import_export_nik');

            if ($this->request->get['category_id']) {
                // get by id
                $results = $this->model_catalog_product->getProductsByCategoryId($this->request->get['category_id']);
            } else {
                // get all
                $results = $this->model_extension_module_import_export_nik->getProductsWithCategories();
            }
            foreach ($results as $result) {
                $products_categories = $this->model_catalog_product->getProductCategories($result['product_id']);
                $json[] = array(
                    'product_id'    => $result['product_id'],
                    'name'          => $result['name'],
                    'categories_id' => isset($products_categories) ? $products_categories : array(),
                    'category_id'   => $this->request->get['category_id'] ? $this->request->get['category_id'] : '',
                );
            }
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function getAllCategories() {
        $json = array();
        $results = array();

        $this->load->model('extension/module/import_export_nik');

        $results = $this->model_extension_module_import_export_nik->getCategoriesList();

        foreach ($results as $result) {
            $json[] = array(
                'category_id'=> $result['category_id'],
                'name'       => $result['name'],
            );
        }

        $sort_order = array();

        foreach ($json as $key => $value) {
            $sort_order[$key] = $value['name'];
        }

        array_multisort($sort_order, SORT_ASC, $json);

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

	public function exportCategories() {
        $this->load->language('extension/module/import_export_nik');
        $this->load->model('extension/module/import_export_nik');

        if (isset($this->request->get['type'])) {
            $type = $this->request->get['type'];
        } else {
            $type = 'categories';
        }

        $url = '';

        if (isset($this->request->get['type'])) {
            $url .= '&type=' . $type;
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate() && $this->validateExportCategories()) {
            // process category data
            $post = $this->request->post;
            $categories = isset($post['category']) ? $post['category'] : array();

            $this->model_extension_module_import_export_nik->download('c', null, null, $categories, $post);

            $this->session->data['success'] = $this->language->get('text_export_success');

            $this->response->redirect($this->url->link('extension/module/import_export_nik', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }


        $this->index();
    }

    public function importCategories() {

    }

    public function exportProducts() {
        $this->load->language('extension/module/import_export_nik');
        $this->load->model('extension/module/import_export_nik');

        if (isset($this->request->get['type'])) {
            $type = $this->request->get['type'];
        } else {
            $type = 'categories';
        }

        $url = '';

        if (isset($this->request->get['type'])) {
            $url .= '&type=' . $type;
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate() && $this->validateExportProducts()) {
            // process category data
            $post = $this->request->post;
            $products = isset($post['product']) ? $post['product'] : array();

            $this->model_extension_module_import_export_nik->download('p', null, null, $products, $post);

            $this->session->data['success'] = $this->language->get('text_export_success');

            $this->response->redirect($this->url->link('extension/module/import_export_nik', 'user_token=' . $this->session->data['user_token'] . $url, true));
        }


        $this->index();
    }

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/import_export_nik')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

    protected function validateExportCategories() {
        if (!isset($this->request->post['category'])) {
            $this->error['empty_categories'] = $this->language->get('error_empty_categories');
        }

        return !$this->error;
    }

    protected function validateExportProducts() {
        if (!isset($this->request->post['product'])) {
            $this->error['empty_products'] = $this->language->get('error_empty_products');
        }

        return !$this->error;
    }
}