<?php
class ControllerExtensionModuleOvesioEcommerce extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/ovesio_ecommerce');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_ovesio_ecommerce', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_instructions'] = $this->language->get('text_instructions');
		$data['text_help_signup'] = $this->language->get('text_help_signup');
		$data['text_feed_urls'] = $this->language->get('text_feed_urls');
		$data['text_warning_hash'] = $this->language->get('text_warning_hash');

		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_export_duration'] = $this->language->get('entry_export_duration');
		$data['entry_product_feed'] = $this->language->get('entry_product_feed');
		$data['entry_order_feed'] = $this->language->get('entry_order_feed');

		$data['option_12_months'] = $this->language->get('option_12_months');
		$data['option_24_months'] = $this->language->get('option_24_months');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
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
			'href' => $this->url->link('extension/module/ovesio_ecommerce', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/ovesio_ecommerce', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_ovesio_ecommerce_status'])) {
			$data['module_ovesio_ecommerce_status'] = $this->request->post['module_ovesio_ecommerce_status'];
		} else {
			$data['module_ovesio_ecommerce_status'] = $this->config->get('module_ovesio_ecommerce_status');
		}

		if (isset($this->request->post['module_ovesio_ecommerce_export_duration'])) {
			$data['module_ovesio_ecommerce_export_duration'] = $this->request->post['module_ovesio_ecommerce_export_duration'];
		} else {
			$data['module_ovesio_ecommerce_export_duration'] = $this->config->get('module_ovesio_ecommerce_export_duration');
		}

		// If hash is missing (e.g. manual update), generate it
		if(isset($this->request->post['module_ovesio_ecommerce_hash'])) {
			$hash = $this->request->post['module_ovesio_ecommerce_hash'];
		} elseif ($this->config->get('module_ovesio_ecommerce_hash')) {
			$hash = $this->config->get('module_ovesio_ecommerce_hash');
		} else {
			$hash = md5(uniqid(mt_rand(), true));
		}

		$data['module_ovesio_ecommerce_hash'] = $hash;

		// Generate URLs
		$data['product_feed_url'] = HTTP_CATALOG . 'index.php?route=extension/module/ovesio_ecommerce&hash=' . $hash;
		$data['order_feed_url'] = HTTP_CATALOG . 'index.php?route=extension/module/ovesio_ecommerce&action=orders&hash=' . $hash;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/ovesio_ecommerce', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/ovesio_ecommerce')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function install() {
		$this->load->model('setting/setting');

		$hash = md5(uniqid(mt_rand(), true));
		$settings = array(
			'module_ovesio_ecommerce_status' => 0,
			'module_ovesio_ecommerce_export_duration' => 12,
			'module_ovesio_ecommerce_hash' => $hash
		);

		$this->model_setting_setting->editSetting('module_ovesio_ecommerce', $settings);
	}

	public function uninstall() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_ovesio_ecommerce');
	}
}
