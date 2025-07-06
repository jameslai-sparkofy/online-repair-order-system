<?php

namespace RepairOrders\Models;

if (!defined('ABSPATH')) {
    exit;
}

class Worker {
    
    private $wpdb;
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $wpdb->prefix . 'repair_workers';
    }
    
    public function create($data) {
        $defaults = [
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $data = wp_parse_args($data, $defaults);
        $data = $this->sanitizeData($data);
        
        $result = $this->wpdb->insert(
            $this->table_name,
            $data,
            [
                '%s', // name
                '%s', // email
                '%s', // phone
                '%s', // created_at
                '%s'  // updated_at
            ]
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to create worker', $this->wpdb->last_error);
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
                '%s', // name
                '%s', // email
                '%s', // phone
                '%s'  // updated_at
            ],
            ['%d']
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to update worker', $this->wpdb->last_error);
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
        
        return $result;
    }
    
    public function getByEmail($email) {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE email = %s",
                $email
            ),
            ARRAY_A
        );
        
        return $result;
    }
    
    public function getAll($filters = []) {
        $where_clauses = [];
        $where_values = [];
        
        if (!empty($filters['search'])) {
            $where_clauses[] = '(name LIKE %s OR email LIKE %s OR phone LIKE %s)';
            $search_term = '%' . $filters['search'] . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $order_by = 'ORDER BY name ASC';
        if (!empty($filters['order_by'])) {
            $allowed_order_by = ['id', 'name', 'email', 'phone', 'created_at', 'updated_at'];
            if (in_array($filters['order_by'], $allowed_order_by)) {
                $order_by = 'ORDER BY ' . $filters['order_by'];
                if (!empty($filters['order_direction']) && in_array(strtoupper($filters['order_direction']), ['ASC', 'DESC'])) {
                    $order_by .= ' ' . strtoupper($filters['order_direction']);
                } else {
                    $order_by .= ' ASC';
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
        
        return $results ?: [];
    }
    
    public function delete($id) {
        $result = $this->wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', 'Failed to delete worker', $this->wpdb->last_error);
        }
        
        return $result;
    }
    
    public function count($filters = []) {
        $where_clauses = [];
        $where_values = [];
        
        if (!empty($filters['search'])) {
            $where_clauses[] = '(name LIKE %s OR email LIKE %s OR phone LIKE %s)';
            $search_term = '%' . $filters['search'] . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
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
    
    private function sanitizeData($data) {
        $sanitized = [];
        
        if (isset($data['name'])) {
            $sanitized['name'] = sanitize_text_field($data['name']);
        }
        
        if (isset($data['email'])) {
            $sanitized['email'] = sanitize_email($data['email']);
        }
        
        if (isset($data['phone'])) {
            $sanitized['phone'] = sanitize_text_field($data['phone']);
        }
        
        if (isset($data['created_at'])) {
            $sanitized['created_at'] = sanitize_text_field($data['created_at']);
        }
        
        if (isset($data['updated_at'])) {
            $sanitized['updated_at'] = sanitize_text_field($data['updated_at']);
        }
        
        return $sanitized;
    }
}