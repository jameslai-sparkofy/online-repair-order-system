<?php
/**
 * Plugin Name: 線上維修單 (Online Repair Order System)
 * Plugin URI: https://github.com/jameslai-sparkofy/online-repair-order-system
 * Description: A comprehensive WordPress plugin for managing repair orders with mobile/desktop support, file uploads, digital signatures, and GitHub auto-sync.
 * Version: 1.0.0
 * Author: Sparkofy
 * Author URI: https://github.com/jameslai-sparkofy
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: repair-orders
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.5
 * Requires PHP: 7.4
 * Network: false
 * 
 * @package RepairOrders
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('REPAIR_ORDERS_VERSION', '1.0.0');
define('REPAIR_ORDERS_PLUGIN_FILE', __FILE__);
define('REPAIR_ORDERS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REPAIR_ORDERS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REPAIR_ORDERS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'RepairOrders\\';
    $base_dir = REPAIR_ORDERS_PLUGIN_DIR . 'src/main/php/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Main plugin class
class RepairOrdersPlugin {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->initHooks();
    }
    
    private function initHooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);
        
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
    }
    
    public function init() {
        load_plugin_textdomain('repair-orders', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        $this->createDatabaseTables();
        $this->createUploadDirectories();
        
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public static function uninstall() {
        global $wpdb;
        
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}repair_orders");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}repair_workers");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}repair_sites");
        
        delete_option('repair_orders_version');
        delete_option('repair_orders_settings');
    }
    
    private function createDatabaseTables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $repair_orders_table = $wpdb->prefix . 'repair_orders';
        $repair_workers_table = $wpdb->prefix . 'repair_workers';
        $repair_sites_table = $wpdb->prefix . 'repair_sites';
        
        $sql_orders = "CREATE TABLE $repair_orders_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_number varchar(50) NOT NULL,
            order_date date NOT NULL,
            site_id bigint(20) unsigned,
            building varchar(100),
            floor varchar(50),
            unit varchar(50),
            reason text,
            worker_id bigint(20) unsigned,
            amount decimal(10,2),
            photos longtext,
            signature longtext,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY order_number (order_number),
            KEY site_id (site_id),
            KEY worker_id (worker_id),
            KEY status (status),
            KEY order_date (order_date)
        ) $charset_collate;";
        
        $sql_workers = "CREATE TABLE $repair_workers_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            email varchar(100),
            phone varchar(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";
        
        $sql_sites = "CREATE TABLE $repair_sites_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            address text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_orders);
        dbDelta($sql_workers);
        dbDelta($sql_sites);
        
        update_option('repair_orders_version', REPAIR_ORDERS_VERSION);
    }
    
    private function createUploadDirectories() {
        $upload_dir = wp_upload_dir();
        $repair_orders_dir = $upload_dir['basedir'] . '/repair-orders';
        
        if (!file_exists($repair_orders_dir)) {
            wp_mkdir_p($repair_orders_dir);
        }
        
        if (!file_exists($repair_orders_dir . '/photos')) {
            wp_mkdir_p($repair_orders_dir . '/photos');
        }
        
        if (!file_exists($repair_orders_dir . '/signatures')) {
            wp_mkdir_p($repair_orders_dir . '/signatures');
        }
        
        $htaccess_content = "Options -Indexes\n";
        file_put_contents($repair_orders_dir . '/.htaccess', $htaccess_content);
    }
    
    public function addAdminMenu() {
        add_menu_page(
            __('線上維修單', 'repair-orders'),
            __('維修單', 'repair-orders'),
            'manage_options',
            'repair-orders',
            [$this, 'adminPage'],
            'dashicons-tools',
            30
        );
        
        add_submenu_page(
            'repair-orders',
            __('所有維修單', 'repair-orders'),
            __('所有維修單', 'repair-orders'),
            'manage_options',
            'repair-orders',
            [$this, 'adminPage']
        );
        
        add_submenu_page(
            'repair-orders',
            __('新增維修單', 'repair-orders'),
            __('新增維修單', 'repair-orders'),
            'manage_options',
            'repair-orders-new',
            [$this, 'newOrderPage']
        );
        
        add_submenu_page(
            'repair-orders',
            __('工務人員', 'repair-orders'),
            __('工務人員', 'repair-orders'),
            'manage_options',
            'repair-orders-workers',
            [$this, 'workersPage']
        );
        
        add_submenu_page(
            'repair-orders',
            __('案場管理', 'repair-orders'),
            __('案場管理', 'repair-orders'),
            'manage_options',
            'repair-orders-sites',
            [$this, 'sitesPage']
        );
    }
    
    public function adminPage() {
        include REPAIR_ORDERS_PLUGIN_DIR . 'src/main/resources/templates/admin-list.php';
    }
    
    public function newOrderPage() {
        include REPAIR_ORDERS_PLUGIN_DIR . 'src/main/resources/templates/admin-new-order.php';
    }
    
    public function workersPage() {
        include REPAIR_ORDERS_PLUGIN_DIR . 'src/main/resources/templates/admin-workers.php';
    }
    
    public function sitesPage() {
        include REPAIR_ORDERS_PLUGIN_DIR . 'src/main/resources/templates/admin-sites.php';
    }
    
    public function enqueueScripts() {
        wp_enqueue_script('repair-orders-frontend', REPAIR_ORDERS_PLUGIN_URL . 'src/main/js/frontend.js', ['jquery'], REPAIR_ORDERS_VERSION, true);
        wp_enqueue_style('repair-orders-frontend', REPAIR_ORDERS_PLUGIN_URL . 'src/main/css/frontend.css', [], REPAIR_ORDERS_VERSION);
        
        wp_localize_script('repair-orders-frontend', 'repairOrdersAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('repair_orders_nonce'),
            'restUrl' => rest_url('repair-orders/v1/'),
            'restNonce' => wp_create_nonce('wp_rest')
        ]);
    }
    
    public function enqueueAdminScripts() {
        wp_enqueue_script('repair-orders-admin', REPAIR_ORDERS_PLUGIN_URL . 'src/main/js/admin.js', ['jquery'], REPAIR_ORDERS_VERSION, true);
        wp_enqueue_style('repair-orders-admin', REPAIR_ORDERS_PLUGIN_URL . 'src/main/css/admin.css', [], REPAIR_ORDERS_VERSION);
        
        wp_localize_script('repair-orders-admin', 'repairOrdersAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('repair_orders_nonce'),
            'restUrl' => rest_url('repair-orders/v1/'),
            'restNonce' => wp_create_nonce('wp_rest')
        ]);
    }
    
    public function registerRestRoutes() {
        $api_controller = new RepairOrders\Api\RestController();
        $api_controller->registerRoutes();
    }
}

// Initialize the plugin
RepairOrdersPlugin::getInstance();