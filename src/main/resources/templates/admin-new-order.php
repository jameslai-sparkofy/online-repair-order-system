<?php
if (!defined('ABSPATH')) {
    exit;
}

$is_edit = isset($_GET['edit']) && !empty($_GET['edit']);
$order_id = $is_edit ? intval($_GET['edit']) : 0;
$page_title = $is_edit ? __('編輯維修單', 'repair-orders') : __('新增維修單', 'repair-orders');
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=repair-orders'); ?>" class="page-title-action"><?php _e('返回列表', 'repair-orders'); ?></a>
    <hr class="wp-header-end">

    <form id="repair-order-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('repair_order_nonce', 'repair_order_nonce'); ?>
        <input type="hidden" id="order-id" name="order_id" value="<?php echo $order_id; ?>">
        <input type="hidden" id="is-edit" name="is_edit" value="<?php echo $is_edit ? '1' : '0'; ?>">
        
        <div class="form-container">
            <div class="form-section">
                <h2><?php _e('基本資訊', 'repair-orders'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="order-number"><?php _e('維修單號', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="order-number" name="order_number" class="regular-text" readonly>
                            <p class="description"><?php _e('系統自動產生', 'repair-orders'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="order-date"><?php _e('日期', 'repair-orders'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="date" id="order-date" name="order_date" class="regular-text" value="<?php echo current_time('Y-m-d'); ?>" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="site-id"><?php _e('案場', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <select id="site-id" name="site_id" class="regular-text">
                                <option value=""><?php _e('選擇案場', 'repair-orders'); ?></option>
                            </select>
                            <button type="button" id="add-site-btn" class="button button-secondary">
                                <?php _e('新增案場', 'repair-orders'); ?>
                            </button>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="building"><?php _e('棟別', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="building" name="building" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="floor"><?php _e('樓層', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="floor" name="floor" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="unit"><?php _e('戶別', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="unit" name="unit" class="regular-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="worker-id"><?php _e('工務人員', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <select id="worker-id" name="worker_id" class="regular-text">
                                <option value=""><?php _e('選擇工務人員', 'repair-orders'); ?></option>
                            </select>
                            <button type="button" id="add-worker-btn" class="button button-secondary">
                                <?php _e('新增工務人員', 'repair-orders'); ?>
                            </button>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="amount"><?php _e('金額', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="amount" name="amount" class="regular-text" step="0.01" min="0">
                            <span class="currency">NT$</span>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="status"><?php _e('狀態', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <select id="status" name="status" class="regular-text">
                                <option value="pending"><?php _e('待處理', 'repair-orders'); ?></option>
                                <option value="in_progress"><?php _e('處理中', 'repair-orders'); ?></option>
                                <option value="completed"><?php _e('已完成', 'repair-orders'); ?></option>
                                <option value="cancelled"><?php _e('已取消', 'repair-orders'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="form-section">
                <h2><?php _e('維修原因', 'repair-orders'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="reason"><?php _e('原因', 'repair-orders'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <textarea id="reason" name="reason" rows="5" class="large-text" required></textarea>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="form-section">
                <h2><?php _e('照片上傳', 'repair-orders'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="photos"><?php _e('照片', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <div class="photo-upload-area">
                                <input type="file" id="photos" name="photos[]" multiple accept="image/*" style="display: none;">
                                <div class="upload-dropzone" id="upload-dropzone">
                                    <div class="upload-instructions">
                                        <span class="dashicons dashicons-upload"></span>
                                        <p><?php _e('點擊或拖拽上傳照片', 'repair-orders'); ?></p>
                                        <p class="description"><?php _e('支援 JPG, PNG, GIF 格式，可同時上傳多張', 'repair-orders'); ?></p>
                                    </div>
                                </div>
                                <button type="button" id="select-photos-btn" class="button">
                                    <?php _e('選擇照片', 'repair-orders'); ?>
                                </button>
                            </div>
                            
                            <div id="photo-preview-container" class="photo-preview-container">
                                <!-- Uploaded photos will be displayed here -->
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="form-section">
                <h2><?php _e('數位簽名', 'repair-orders'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="signature"><?php _e('簽名', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <div class="signature-container">
                                <canvas id="signature-canvas" width="400" height="200"></canvas>
                                <div class="signature-controls">
                                    <button type="button" id="clear-signature" class="button">
                                        <?php _e('清除簽名', 'repair-orders'); ?>
                                    </button>
                                    <button type="button" id="save-signature" class="button">
                                        <?php _e('儲存簽名', 'repair-orders'); ?>
                                    </button>
                                </div>
                                <input type="hidden" id="signature-data" name="signature_data">
                                <div id="signature-preview" class="signature-preview" style="display: none;">
                                    <img id="signature-image" src="" alt="Signature">
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="form-actions">
            <div class="loading-spinner" id="form-loading" style="display: none;">
                <img src="<?php echo admin_url('images/spinner-2x.gif'); ?>" width="20" height="20" alt="Loading...">
                <?php _e('處理中...', 'repair-orders'); ?>
            </div>
            
            <input type="submit" class="button button-primary" value="<?php echo $is_edit ? __('更新維修單', 'repair-orders') : __('建立維修單', 'repair-orders'); ?>">
            <a href="<?php echo admin_url('admin.php?page=repair-orders'); ?>" class="button button-secondary">
                <?php _e('取消', 'repair-orders'); ?>
            </a>
            
            <?php if ($is_edit): ?>
            <button type="button" id="generate-link-btn" class="button button-secondary">
                <?php _e('產生公開連結', 'repair-orders'); ?>
            </button>
            <div id="public-link-container" style="display: none;">
                <p>
                    <strong><?php _e('公開連結', 'repair-orders'); ?>:</strong>
                    <input type="text" id="public-link" readonly class="large-text">
                    <button type="button" id="copy-link-btn" class="button">
                        <?php _e('複製連結', 'repair-orders'); ?>
                    </button>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Add Site Modal -->
<div id="add-site-modal" class="repair-orders-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php _e('新增案場', 'repair-orders'); ?></h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="add-site-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="site-name"><?php _e('案場名稱', 'repair-orders'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="site-name" name="site_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="site-address"><?php _e('地址', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <textarea id="site-address" name="site_address" rows="3" class="large-text"></textarea>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="button-secondary close-modal"><?php _e('取消', 'repair-orders'); ?></button>
            <button type="button" class="button-primary" id="save-site-btn"><?php _e('儲存', 'repair-orders'); ?></button>
        </div>
    </div>
</div>

<!-- Add Worker Modal -->
<div id="add-worker-modal" class="repair-orders-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php _e('新增工務人員', 'repair-orders'); ?></h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <form id="add-worker-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="worker-name"><?php _e('姓名', 'repair-orders'); ?> <span class="required">*</span></label>
                        </th>
                        <td>
                            <input type="text" id="worker-name" name="worker_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="worker-email"><?php _e('電子郵件', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="worker-email" name="worker_email" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="worker-phone"><?php _e('電話', 'repair-orders'); ?></label>
                        </th>
                        <td>
                            <input type="tel" id="worker-phone" name="worker_phone" class="regular-text">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="button-secondary close-modal"><?php _e('取消', 'repair-orders'); ?></button>
            <button type="button" class="button-primary" id="save-worker-btn"><?php _e('儲存', 'repair-orders'); ?></button>
        </div>
    </div>
</div>

<script type="text/template" id="photo-preview-template">
    <div class="photo-preview-item" data-file-index="<%- index %>">
        <div class="photo-preview">
            <img src="<%- src %>" alt="Preview">
            <button type="button" class="remove-photo" data-file-index="<%- index %>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        <div class="photo-info">
            <div class="photo-name"><%- name %></div>
            <div class="photo-size"><%- size %></div>
        </div>
        <div class="upload-progress">
            <div class="progress-bar" style="width: 0%"></div>
        </div>
    </div>
</script>