<?php
class ControllerExtensionPaymentREMIBIT extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/payment/remibit');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_remibit', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

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
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/remibit', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/remibit', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        if (isset($this->request->post['payment_remibit_login_id'])) {
            $data['payment_remibit_login_id'] = $this->request->post['payment_remibit_login_id'];
        } else {
            $data['payment_remibit_login_id'] = $this->config->get('payment_remibit_login_id');
        }


        if (isset($this->request->post['payment_remibit_transaction_key'])) {
            $data['payment_remibit_transaction_key'] = $this->request->post['payment_remibit_transaction_key'];
        } else {
            $data['payment_remibit_transaction_key'] = $this->config->get('payment_remibit_transaction_key');
        }


        if (isset($this->request->post['payment_remibit_signature_key'])) {
            $data['payment_remibit_signature_key'] = $this->request->post['payment_remibit_signature_key'];
        } else {
            $data['payment_remibit_signature_key'] = $this->config->get('payment_remibit_signature_key');
        }


        if (isset($this->request->post['payment_remibit_md5_hash'])) {
            $data['payment_remibit_md5_hash'] = $this->request->post['payment_remibit_md5_hash'];
        } else {
            $data['payment_remibit_md5_hash'] = $this->config->get('payment_remibit_md5_hash');
        }


        if (isset($this->request->post['payment_remibit_getaway_url'])) {
            $data['payment_remibit_getaway_url'] = $this->request->post['payment_remibit_getaway_url'];
        } else{
            $data['payment_remibit_getaway_url'] = $this->config->get('payment_remibit_getaway_url');
        }
        if($data['payment_remibit_getaway_url'] == ''){
            $data['payment_remibit_getaway_url'] = 'https://app.remibit.com/pay';
        }

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        if (isset($this->request->post['payment_remibit_order_status_id'])) {
            $data['payment_remibit_order_status_id'] = $this->request->post['payment_remibit_order_status_id'];
        } else {
            $data['payment_remibit_order_status_id'] = $this->config->get('payment_remibit_order_status_id');
        }

        if (isset($this->request->post['payment_remibit_status'])) {
            $data['payment_remibit_status'] = $this->request->post['payment_remibit_status'];
        } else {
            $data['payment_remibit_status'] = $this->config->get('payment_remibit_status');
        }

        if (isset($this->request->post['payment_remibit_sort_order'])) {
            $data['payment_remibit_sort_order'] = $this->request->post['payment_remibit_sort_order'];
        } else {
            $data['payment_remibit_sort_order'] = $this->config->get('payment_remibit_sort_order');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/remibit', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/remibit')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}
