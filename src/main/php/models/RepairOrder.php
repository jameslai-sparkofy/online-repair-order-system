<?php

namespace RepairOrders\Models;

if (!defined('ABSPATH')) {
    exit;
}

class RepairOrder {
    
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'repair_orders';
    }
    
    public function create($data) {
        $defaults = [
            'order_number' => $this->generateOrderNumber(),
            'order_date' => current_time('Y-m-d'),
            'status' => 'pending',
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        $data = $this->sanitizeData($data);
        
        $result = $this->wpdb->insert(
            $this->table_name,
            $data,
            [
                '%s', // order_number
                '%s', // order_date
                '%d', // site_id
                '%s', // building
                '%s', // floor
                '%s', // unit
                '%s', // reason
                '%d', // worker_id
                '%f', // amount
                '%s', // photos
                '%s', // signature
                '%s', // status
                '%s', // created_at
                '%s'  // updated_at
            ]
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create repair order', $this->wpdb->last_error);
        }
        
        return $this->wpdb->insert_id;
    }
    
    public function update($id, $data) {
        $data['updated_at'] = current_time('mysql');
        $data = $this->sanitizeData($data);
        
        $result = $this->wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id],
            [
                '%s', // order_number
                '%s', // order_date
                '%d', // site_id
                '%s', // building
                '%s', // floor
                '%s', // unit
                '%s', // reason
                '%d', // worker_id
                '%f', // amount
                '%s', // photos
                '%s', // signature
                '%s', // status
                '%s'  // updated_at
            ],
            ['%d']
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to update repair order', $this->wpdb->last_error);
        }
        
        return $result;
    }
    
    public function get($id) {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        
        if ($result) {
            $result = $this->formatData($result);
        }
        
        return $result;
    }
    
    public function getByOrderNumber($order_number) {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE order_number = %s",
                $order_number
            ),
            ARRAY_A
        );
        
        if ($result) {
            $result = $this->formatData($result);
        }
        
        return $result;
    }
    
    public function getAll($filters = []) {
        $where_clauses = [];
        $where_values = [];
        
        if (!empty($filters['site_id'])) {
            $where_clauses[] = 'site_id = %d';
            $where_values[] = $filters['site_id'];
        }
        
        if (!empty($filters['worker_id'])) {
            $where_clauses[] = 'worker_id = %d';
            $where_values[] = $filters['worker_id'];
        }
        
        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'order_date >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'order_date <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $order_by = 'ORDER BY created_at DESC';
        if (!empty($filters['order_by'])) {
            $allowed_order_by = ['id', 'order_number', 'order_date', 'status', 'created_at', 'updated_at'];
            if (in_array($filters['order_by'], $allowed_order_by)) {
                $order_by = 'ORDER BY ' . $filters['order_by'];
                if (!empty($filters['order_direction']) && in_array(strtoupper($filters['order_direction']), ['ASC', 'DESC'])) {
                    $order_by .= ' ' . strtoupper($filters['order_direction']);
                } else {
                    $order_by .= ' DESC';
                }
            }
        }
        
        $limit_sql = '';
        if (!empty($filters['limit'])) {
            $limit_sql = 'LIMIT ' . intval($filters['limit']);
            if (!empty($filters['offset'])) {
                $limit_sql .= ' OFFSET ' . intval($filters['offset']);
            }
        }
        
        $sql = "SELECT * FROM {$this->table_name} {$where_sql} {$order_by} {$limit_sql}";
        
        if (!empty($where_values)) {
            $sql = $this->wpdb->prepare($sql, $where_values);
        }
        
        $results = $this->wpdb->get_results($sql, ARRAY_A);
        
        if ($results) {
            foreach ($results as &$result) {
                $result = $this->formatData($result);
            }
        }
        
        return $results ?: [];
    }
    
    public function delete($id) {
        $result = $this->wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to delete repair order', $this->wpdb->last_error);
        }
        
        return $result;
    }
    
    public function count($filters = []) {
        $where_clauses = [];
        $where_values = [];
        
        if (!empty($filters['site_id'])) {
            $where_clauses[] = 'site_id = %d';
            $where_values[] = $filters['site_id'];
        }
        
        if (!empty($filters['worker_id'])) {
            $where_clauses[] = 'worker_id = %d';
            $where_values[] = $filters['worker_id'];
        }
        
        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'order_date >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'order_date <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table_name} {$where_sql}";
        
        if (!empty($where_values)) {
            $sql = $this->wpdb->prepare($sql, $where_values);
        }
        
        return $this->wpdb->get_var($sql);
    }
    
    private function generateOrderNumber() {
        $date = current_time('Ymd');
        $sequence = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) + 1 FROM {$this->table_name} WHERE order_number LIKE %s",
                $date . '%'
            )
        );
        
        return $date . sprintf('%04d', $sequence);
    }
    
    private function sanitizeData($data) {
        $sanitized = [];
        
        if (isset($data['order_number'])) {
            $sanitized['order_number'] = sanitize_text_field($data['order_number']);
        }
        
        if (isset($data['order_date'])) {
            $sanitized['order_date'] = sanitize_text_field($data['order_date']);
        }
        
        if (isset($data['site_id'])) {
            $sanitized['site_id'] = intval($data['site_id']);
        }
        
        if (isset($data['building'])) {
            $sanitized['building'] = sanitize_text_field($data['building']);
        }
        
        if (isset($data['floor'])) {
            $sanitized['floor'] = sanitize_text_field($data['floor']);
        }
        
        if (isset($data['unit'])) {
            $sanitized['unit'] = sanitize_text_field($data['unit']);
        }
        
        if (isset($data['reason'])) {
            $sanitized['reason'] = sanitize_textarea_field($data['reason']);
        }
        
        if (isset($data['worker_id'])) {
            $sanitized['worker_id'] = intval($data['worker_id']);
        }
        
        if (isset($data['amount'])) {
            $sanitized['amount'] = floatval($data['amount']);
        }
        
        if (isset($data['photos'])) {
            $sanitized['photos'] = is_array($data['photos']) ? json_encode($data['photos']) : $data['photos'];
        }
        
        if (isset($data['signature'])) {
            $sanitized['signature'] = $data['signature'];
        }
        
        if (isset($data['status'])) {
            $allowed_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
            $sanitized['status'] = in_array($data['status'], $allowed_statuses) ? $data['status'] : 'pending';
        }
        
        if (isset($data['created_at'])) {
            $sanitized['created_at'] = sanitize_text_field($data['created_at']);
        }
        
        if (isset($data['updated_at'])) {
            $sanitized['updated_at'] = sanitize_text_field($data['updated_at']);
        }
        
        return $sanitized;
    }
    
    private function formatData($data) {
        if (isset($data['photos']) && !empty($data['photos'])) {
            $photos = json_decode($data['photos'], true);
            $data['photos'] = is_array($photos) ? $photos : [];
        } else {
            $data['photos'] = [];
        }
        
        if (isset($data['amount'])) {
            $data['amount'] = floatval($data['amount']);
        }
        
        return $data;
    }
}