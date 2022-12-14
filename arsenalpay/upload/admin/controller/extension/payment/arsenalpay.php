<?php
class ControllerExtensionPaymentArsenalpay extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/payment/arsenalpay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_arsenalpay', $this->request->post);
                        if ($this->request->post)
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		$data = array();

		$lang_params = array(
			'heading_title',
			'text_payment_edit',
			'text_tax_edit',
			'text_edit',
			'text_enabled',
			'text_disabled',
			'text_arsenalpay_tax',
			'text_all_zones',
			'text_tax_tab_header',
			'text_payment_tab_header',

			'entry_widget_id',
			'entry_widget_key',
			'entry_callback_key',
			'entry_callback_url',
			'entry_total',
			'entry_debug',
			'entry_geo_zone',
			'entry_status',
			'entry_sort_order',
			'entry_currency_code',
			'entry_ip',

			'button_save',
			'button_cancel',

			'entry_completed_status',
			'entry_failed_status',
			'entry_canceled_status',
			'entry_holden_status',
			'entry_reversed_status',
			'entry_refunded_status',
			'entry_checked_status',

			'help_widget_id',
			'help_widget_key',
			'help_callback_key',
			'help_callback_url',
			'help_debug',
			'help_total',
			'help_currency_code',
			'help_checked_status',
			'help_ip',

			'entry_tax_table',
			'help_header_ap_tax_rates',
			'help_header_shop_tax_classes',
			'entry_default_tax_rate',
			'help_default_tax_rate',
			'entry_shipment_tax_rate',
		);

		foreach ($lang_params as $param) {
			$data[$param] = $this->language->get($param);
		}

		$errors_keys = array(
			'warning',
			'widget_id',
			'widget_key',
			'callback_key'
		);
		foreach ($errors_keys as $e_key) {
			if (isset($this->error[$e_key])) {
				$data['error_' . $e_key] = $this->error[$e_key];
			}
			else {
				$data['error_' . $e_key] = '';
			}
		}
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extensions'),
			'href' => $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/arsenalpay', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/arsenalpay', 'user_token=' . $this->session->data['user_token'], 'SSL');
		$data['cancel'] = $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'], 'SSL');


		$this->load->model('localisation/geo_zone');
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('localisation/currency');
		$data['currencies'] = $this->model_localisation_currency->getCurrencies();

		$post_params = array(
			'payment_arsenalpay_callback_key',
			'payment_arsenalpay_widget_key',
			'payment_arsenalpay_widget_id',
			'payment_arsenalpay_total',
			'payment_arsenalpay_geo_zone_id',
			'payment_arsenalpay_status',
			'payment_arsenalpay_checked_status_id',
			'payment_arsenalpay_completed_status_id',
			'payment_arsenalpay_failed_status_id',
			'payment_arsenalpay_canceled_status_id',
			'payment_arsenalpay_holden_status_id',
			'payment_arsenalpay_reversed_status_id',
			'payment_arsenalpay_refunded_status_id',
			'payment_arsenalpay_debug',
			'payment_arsenalpay_currency_code',
			'payment_arsenalpay_ip',
			'payment_arsenalpay_default_tax_rate',
			'payment_arsenalpay_shipment_tax_rate',
		);
		foreach ($post_params as $param) {
			$data[$param] = $this->get_param_value($param);
		}
		$data['payment_arsenalpay_sort_order']    = $this->get_param_value('payment_arsenalpay_sort_order', 0);
		$data['payment_arsenalpay_tax_rates_map'] = $this->get_param_value('payment_arsenalpay_tax_rates_map', array());

		$data['callback_url'] = HTTPS_CATALOG . 'index.php?route=extension/payment/arsenalpay/ap_callback';

		$data['header']         = $this->load->controller('common/header');
		$data['column_left']    = $this->load->controller('common/column_left');
		$data['footer']         = $this->load->controller('common/footer');
		$data['ap_tax_rates']   = $this->get_arsenalpay_tax_rates_with_labels();
		$data['shop_tax_classes'] = $this->get_shop_tax_classes();

		$this->response->setOutput($this->load->view('extension/payment/arsenalpay', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/payment/arsenalpay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		$required_params = array(
			'widget_id',
			'widget_key',
			'callback_key',
		);
		foreach ($required_params as $param) {
			if (!$this->request->post['payment_arsenalpay_' . $param]) {
				$this->error[$param] = $this->language->get('error_' . $param);
			}
		}

		if (!$this->error) {
			return true;
		}

		return false;
	}

	protected function get_arsenalpay_tax_rates() {
		return array(
			"none",
			"vat0",
			"vat10",
			"vat18",
			"vat110",
			"vat118",
		);
	}

	protected function get_arsenalpay_tax_rates_with_labels() {
		$tax_rates             = $this->get_arsenalpay_tax_rates();
		$tax_rates_with_labels = array();
		foreach ($tax_rates as $id) {
			$tax_rates_with_labels[$id] = $this->language->get("entry_{$id}_tax_rate");
		}

		return $tax_rates_with_labels;
	}

	protected function get_shop_tax_classes() {
		$this->load->model('localisation/tax_class');
		$model = $this->model_localisation_tax_class;

		$result = array();
		foreach ($model->getTaxClasses() as $tax_class) {
			$result[$tax_class['tax_class_id']] = $tax_class['title'];
		}

		return $result;
	}

	protected function get_param_value($key, $default_val = null) {
		if (isset($this->request->post[$key])) {
			return $this->request->post[$key];
		}
		$config_val = $this->config->get($key);

		if (!$config_val && !is_null($default_val)) {
			return $default_val;
		}

		return $config_val;

	}

}



