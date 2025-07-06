<?php

namespace RepairOrders\Api;

use RepairOrders\Models\RepairOrder;
use RepairOrders\Models\Worker;
use RepairOrders\Models\Site;

if (!defined('ABSPATH')) {
    exit;
}

class RestController {
    
    private $repair_order_model;
    private $worker_model;
    private $site_model;
    private $namespace = 'repair-orders/v1';
    
    public function __construct() {
        $this->repair_order_model = new RepairOrder();
        $this->worker_model = new Worker();
        $this->site_model = new Site();
    }
    
    public function registerRoutes() {
        // Repair Orders endpoints
        register_rest_route($this->namespace, '/orders', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getOrders'],
                'permission_callback' => [$this, 'checkPermissions']
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'createOrder'],
                'permission_callback' => [$this, 'checkPermissions']
            ]
        ]);
        
        register_rest_route($this->namespace, '/orders/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getOrder'],
                'permission_callback' => [$this, 'checkPermissions']
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'updateOrder'],
                'permission_callback' => [$this, 'checkPermissions']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'deleteOrder'],
                'permission_callback' => [$this, 'checkPermissions']
            ]
        ]);
        
        register_rest_route($this->namespace, '/orders/by-number/(?P<number>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'getOrderByNumber'],
            'permission_callback' => '__return_true' // Public endpoint for site confirmation
        ]);
        
        // Workers endpoints
        register_rest_route($this->namespace, '/workers', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getWorkers'],
                'permission_callback' => [$this, 'checkPermissions']
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'createWorker'],
                'permission_callback' => [$this, 'checkPermissions']
            ]
        ]);
        
        register_rest_route($this->namespace, '/workers/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getWorker'],
                'permission_callback' => [$this, 'checkPermissions']
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'updateWorker'],
                'permission_callback' => [$this, 'checkPermissions']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'deleteWorker'],
                'permission_callback' => [$this, 'checkPermissions']
            ]
        ]);
        
        // Sites endpoints
        register_rest_route($this->namespace, '/sites', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getSites'],
                'permission_callback' => [$this, 'checkPermissions']
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'createSite'],
                'permission_callback' => [$this, 'checkPermissions']
            ]
        ]);
        
        register_rest_route($this->namespace, '/sites/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getSite'],
                'permission_callback' => [$this, 'checkPermissions']
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'updateSite'],
                'permission_callback' => [$this, 'checkPermissions']
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'deleteSite'],
                'permission_callback' => [$this, 'checkPermissions']
            ]
        ]);
        
        // File upload endpoints
        register_rest_route($this->namespace, '/upload/photo', [
            'methods' => 'POST',
            'callback' => [$this, 'uploadPhoto'],
            'permission_callback' => [$this, 'checkPermissions']
        ]);
        
        register_rest_route($this->namespace, '/upload/signature', [
            'methods' => 'POST',
            'callback' => [$this, 'uploadSignature'],
            'permission_callback' => '__return_true' // Public endpoint for site confirmation
        ]);
        
        // GitHub webhook endpoint
        register_rest_route($this->namespace, '/github-webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'githubWebhook'],
            'permission_callback' => '__return_true'
        ]);
    }
    
    // Repair Orders methods
    public function getOrders($request) {
        $params = $request->get_params();
        
        $filters = [
            'site_id' => !empty($params['site_id']) ? intval($params['site_id']) : null,
            'worker_id' => !empty($params['worker_id']) ? intval($params['worker_id']) : null,
            'status' => !empty($params['status']) ? sanitize_text_field($params['status']) : null,
            'date_from' => !empty($params['date_from']) ? sanitize_text_field($params['date_from']) : null,
            'date_to' => !empty($params['date_to']) ? sanitize_text_field($params['date_to']) : null,
            'order_by' => !empty($params['order_by']) ? sanitize_text_field($params['order_by']) : 'created_at',
            'order_direction' => !empty($params['order_direction']) ? sanitize_text_field($params['order_direction']) : 'DESC',
            'limit' => !empty($params['per_page']) ? intval($params['per_page']) : 20,
            'offset' => !empty($params['page']) ? (intval($params['page']) - 1) * (!empty($params['per_page']) ? intval($params['per_page']) : 20) : 0
        ];
        
        $orders = $this->repair_order_model->getAll($filters);
        $total = $this->repair_order_model->count($filters);
        
        // Add site and worker information
        foreach ($orders as &$order) {
            if ($order['site_id']) {
                $order['site'] = $this->site_model->get($order['site_id']);
            }
            if ($order['worker_id']) {
                $order['worker'] = $this->worker_model->get($order['worker_id']);
            }
        }
        
        return rest_ensure_response([
            'orders' => $orders,
            'total' => $total,
            'page' => !empty($params['page']) ? intval($params['page']) : 1,
            'per_page' => $filters['limit']
        ]);
    }
    
    public function getOrder($request) {
        $id = $request['id'];
        $order = $this->repair_order_model->get($id);
        
        if (!$order) {
            return new \WP_Error('not_found', 'Repair order not found', ['status' => 404]);
        }
        
        // Add site and worker information
        if ($order['site_id']) {
            $order['site'] = $this->site_model->get($order['site_id']);
        }
        if ($order['worker_id']) {
            $order['worker'] = $this->worker_model->get($order['worker_id']);
        }
        
        return rest_ensure_response($order);
    }
    
    public function getOrderByNumber($request) {
        $order_number = $request['number'];
        $order = $this->repair_order_model->getByOrderNumber($order_number);
        
        if (!$order) {
            return new \WP_Error('not_found', 'Repair order not found', ['status' => 404]);
        }
        
        // Add site and worker information
        if ($order['site_id']) {
            $order['site'] = $this->site_model->get($order['site_id']);
        }
        if ($order['worker_id']) {
            $order['worker'] = $this->worker_model->get($order['worker_id']);
        }
        
        return rest_ensure_response($order);
    }
    
    public function createOrder($request) {
        $params = $request->get_json_params();
        
        $required_fields = ['reason'];
        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new \WP_Error('missing_field', "Required field '{$field}' is missing", ['status' => 400]);
            }
        }
        
        $result = $this->repair_order_model->create($params);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $order = $this->repair_order_model->get($result);
        
        return rest_ensure_response([
            'message' => 'Repair order created successfully',
            'order' => $order
        ]);
    }
    
    public function updateOrder($request) {
        $id = $request['id'];
        $params = $request->get_json_params();
        
        $order = $this->repair_order_model->get($id);
        if (!$order) {
            return new \WP_Error('not_found', 'Repair order not found', ['status' => 404]);
        }
        
        $result = $this->repair_order_model->update($id, $params);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $updated_order = $this->repair_order_model->get($id);
        
        return rest_ensure_response([
            'message' => 'Repair order updated successfully',
            'order' => $updated_order
        ]);
    }
    
    public function deleteOrder($request) {
        $id = $request['id'];
        
        $order = $this->repair_order_model->get($id);
        if (!$order) {
            return new \WP_Error('not_found', 'Repair order not found', ['status' => 404]);
        }
        
        $result = $this->repair_order_model->delete($id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(['message' => 'Repair order deleted successfully']);
    }
    
    // Workers methods
    public function getWorkers($request) {
        $params = $request->get_params();
        
        $filters = [
            'search' => !empty($params['search']) ? sanitize_text_field($params['search']) : null,
            'order_by' => !empty($params['order_by']) ? sanitize_text_field($params['order_by']) : 'name',
            'order_direction' => !empty($params['order_direction']) ? sanitize_text_field($params['order_direction']) : 'ASC',
            'limit' => !empty($params['per_page']) ? intval($params['per_page']) : 50,
            'offset' => !empty($params['page']) ? (intval($params['page']) - 1) * (!empty($params['per_page']) ? intval($params['per_page']) : 50) : 0
        ];
        
        $workers = $this->worker_model->getAll($filters);
        $total = $this->worker_model->count($filters);
        
        return rest_ensure_response([
            'workers' => $workers,
            'total' => $total,
            'page' => !empty($params['page']) ? intval($params['page']) : 1,
            'per_page' => $filters['limit']
        ]);
    }
    
    public function getWorker($request) {
        $id = $request['id'];
        $worker = $this->worker_model->get($id);
        
        if (!$worker) {
            return new \WP_Error('not_found', 'Worker not found', ['status' => 404]);
        }
        
        return rest_ensure_response($worker);
    }
    
    public function createWorker($request) {
        $params = $request->get_json_params();
        
        $required_fields = ['name'];
        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new \WP_Error('missing_field', "Required field '{$field}' is missing", ['status' => 400]);
            }
        }
        
        $result = $this->worker_model->create($params);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $worker = $this->worker_model->get($result);
        
        return rest_ensure_response([
            'message' => 'Worker created successfully',
            'worker' => $worker
        ]);
    }
    
    public function updateWorker($request) {
        $id = $request['id'];
        $params = $request->get_json_params();
        
        $worker = $this->worker_model->get($id);
        if (!$worker) {
            return new \WP_Error('not_found', 'Worker not found', ['status' => 404]);
        }
        
        $result = $this->worker_model->update($id, $params);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $updated_worker = $this->worker_model->get($id);
        
        return rest_ensure_response([
            'message' => 'Worker updated successfully',
            'worker' => $updated_worker
        ]);
    }
    
    public function deleteWorker($request) {
        $id = $request['id'];
        
        $worker = $this->worker_model->get($id);
        if (!$worker) {
            return new \WP_Error('not_found', 'Worker not found', ['status' => 404]);
        }
        
        $result = $this->worker_model->delete($id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(['message' => 'Worker deleted successfully']);
    }
    
    // Sites methods
    public function getSites($request) {
        $params = $request->get_params();
        
        $filters = [
            'search' => !empty($params['search']) ? sanitize_text_field($params['search']) : null,
            'order_by' => !empty($params['order_by']) ? sanitize_text_field($params['order_by']) : 'name',
            'order_direction' => !empty($params['order_direction']) ? sanitize_text_field($params['order_direction']) : 'ASC',
            'limit' => !empty($params['per_page']) ? intval($params['per_page']) : 50,
            'offset' => !empty($params['page']) ? (intval($params['page']) - 1) * (!empty($params['per_page']) ? intval($params['per_page']) : 50) : 0
        ];
        
        $sites = $this->site_model->getAll($filters);
        $total = $this->site_model->count($filters);
        
        return rest_ensure_response([
            'sites' => $sites,
            'total' => $total,
            'page' => !empty($params['page']) ? intval($params['page']) : 1,
            'per_page' => $filters['limit']
        ]);
    }
    
    public function getSite($request) {
        $id = $request['id'];
        $site = $this->site_model->get($id);
        
        if (!$site) {
            return new \WP_Error('not_found', 'Site not found', ['status' => 404]);
        }
        
        return rest_ensure_response($site);
    }
    
    public function createSite($request) {
        $params = $request->get_json_params();
        
        $required_fields = ['name'];
        foreach ($required_fields as $field) {
            if (empty($params[$field])) {
                return new \WP_Error('missing_field', "Required field '{$field}' is missing", ['status' => 400]);
            }
        }
        
        $result = $this->site_model->create($params);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $site = $this->site_model->get($result);
        
        return rest_ensure_response([
            'message' => 'Site created successfully',
            'site' => $site
        ]);
    }
    
    public function updateSite($request) {
        $id = $request['id'];
        $params = $request->get_json_params();
        
        $site = $this->site_model->get($id);
        if (!$site) {
            return new \WP_Error('not_found', 'Site not found', ['status' => 404]);
        }
        
        $result = $this->site_model->update($id, $params);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        $updated_site = $this->site_model->get($id);
        
        return rest_ensure_response([
            'message' => 'Site updated successfully',
            'site' => $updated_site
        ]);
    }
    
    public function deleteSite($request) {
        $id = $request['id'];
        
        $site = $this->site_model->get($id);
        if (!$site) {
            return new \WP_Error('not_found', 'Site not found', ['status' => 404]);
        }
        
        $result = $this->site_model->delete($id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(['message' => 'Site deleted successfully']);
    }
    
    // File upload methods
    public function uploadPhoto($request) {
        $files = $request->get_file_params();
        
        if (empty($files['photo'])) {
            return new \WP_Error('missing_file', 'No photo file provided', ['status' => 400]);
        }
        
        $file = $files['photo'];
        $upload_dir = wp_upload_dir();
        $photos_dir = $upload_dir['basedir'] . '/repair-orders/photos';
        
        if (!file_exists($photos_dir)) {
            wp_mkdir_p($photos_dir);
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            return new \WP_Error('invalid_file_type', 'Only JPEG, PNG and GIF files are allowed', ['status' => 400]);
        }
        
        $filename = uniqid() . '_' . sanitize_file_name($file['name']);
        $file_path = $photos_dir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $file_url = $upload_dir['baseurl'] . '/repair-orders/photos/' . $filename;
            
            return rest_ensure_response([
                'message' => 'Photo uploaded successfully',
                'file_path' => $file_path,
                'file_url' => $file_url,
                'filename' => $filename
            ]);
        }
        
        return new \WP_Error('upload_failed', 'Failed to upload photo', ['status' => 500]);
    }
    
    public function uploadSignature($request) {
        $params = $request->get_json_params();
        
        if (empty($params['signature_data'])) {
            return new \WP_Error('missing_data', 'No signature data provided', ['status' => 400]);
        }
        
        $signature_data = $params['signature_data'];
        $order_number = !empty($params['order_number']) ? sanitize_text_field($params['order_number']) : uniqid();
        
        $upload_dir = wp_upload_dir();
        $signatures_dir = $upload_dir['basedir'] . '/repair-orders/signatures';
        
        if (!file_exists($signatures_dir)) {
            wp_mkdir_p($signatures_dir);
        }
        
        // Remove data:image/png;base64, prefix if present
        if (strpos($signature_data, 'data:image') === 0) {
            $signature_data = substr($signature_data, strpos($signature_data, ',') + 1);
        }
        
        $signature_data = base64_decode($signature_data);
        if ($signature_data === false) {
            return new \WP_Error('invalid_data', 'Invalid signature data', ['status' => 400]);
        }
        
        $filename = 'signature_' . $order_number . '_' . time() . '.png';
        $file_path = $signatures_dir . '/' . $filename;
        
        if (file_put_contents($file_path, $signature_data)) {
            $file_url = $upload_dir['baseurl'] . '/repair-orders/signatures/' . $filename;
            
            return rest_ensure_response([
                'message' => 'Signature uploaded successfully',
                'file_path' => $file_path,
                'file_url' => $file_url,
                'filename' => $filename
            ]);
        }
        
        return new \WP_Error('upload_failed', 'Failed to upload signature', ['status' => 500]);
    }
    
    // GitHub webhook method
    public function githubWebhook($request) {
        $headers = $request->get_headers();
        $body = $request->get_body();
        
        // Verify GitHub webhook signature if secret is configured
        $webhook_secret = get_option('repair_orders_github_webhook_secret');
        if ($webhook_secret) {
            $signature = 'sha1=' . hash_hmac('sha1', $body, $webhook_secret);
            if (!hash_equals($signature, $headers['x_hub_signature'][0] ?? '')) {
                return new \WP_Error('invalid_signature', 'Invalid webhook signature', ['status' => 403]);
            }
        }
        
        $payload = json_decode($body, true);
        
        if (isset($payload['ref']) && $payload['ref'] === 'refs/heads/main') {
            // Update plugin files from GitHub
            $this->updatePluginFromGitHub();
        }
        
        return rest_ensure_response(['message' => 'Webhook processed successfully']);
    }
    
    private function updatePluginFromGitHub() {
        // This would implement the auto-update functionality
        // For now, just log the webhook event
        error_log('GitHub webhook received - plugin update triggered');
    }
    
    public function checkPermissions() {
        return current_user_can('manage_options');
    }
}