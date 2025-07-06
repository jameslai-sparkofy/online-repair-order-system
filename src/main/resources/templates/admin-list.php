<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('線上維修單', 'repair-orders'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=repair-orders-new'); ?>" class="page-title-action"><?php _e('新增維修單', 'repair-orders'); ?></a>
    <hr class="wp-header-end">

    <div class="repair-orders-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="repair-orders">
            
            <div class="filter-row">
                <label for="site_filter"><?php _e('案場', 'repair-orders'); ?>:</label>
                <select name="site_id" id="site_filter">
                    <option value=""><?php _e('所有案場', 'repair-orders'); ?></option>
                </select>
                
                <label for="worker_filter"><?php _e('工務人員', 'repair-orders'); ?>:</label>
                <select name="worker_id" id="worker_filter">
                    <option value=""><?php _e('所有工務人員', 'repair-orders'); ?></option>
                </select>
                
                <label for="status_filter"><?php _e('狀態', 'repair-orders'); ?>:</label>
                <select name="status" id="status_filter">
                    <option value=""><?php _e('所有狀態', 'repair-orders'); ?></option>
                    <option value="pending"><?php _e('待處理', 'repair-orders'); ?></option>
                    <option value="in_progress"><?php _e('處理中', 'repair-orders'); ?></option>
                    <option value="completed"><?php _e('已完成', 'repair-orders'); ?></option>
                    <option value="cancelled"><?php _e('已取消', 'repair-orders'); ?></option>
                </select>
            </div>
            
            <div class="filter-row">
                <label for="date_from"><?php _e('開始日期', 'repair-orders'); ?>:</label>
                <input type="date" name="date_from" id="date_from" value="<?php echo isset($_GET['date_from']) ? esc_attr($_GET['date_from']) : ''; ?>">
                
                <label for="date_to"><?php _e('結束日期', 'repair-orders'); ?>:</label>
                <input type="date" name="date_to" id="date_to" value="<?php echo isset($_GET['date_to']) ? esc_attr($_GET['date_to']) : ''; ?>">
                
                <input type="submit" class="button" value="<?php _e('篩選', 'repair-orders'); ?>">
                <a href="<?php echo admin_url('admin.php?page=repair-orders'); ?>" class="button"><?php _e('清除', 'repair-orders'); ?></a>
            </div>
        </form>
    </div>

    <div id="repair-orders-list">
        <div class="loading-spinner" style="display: none;">
            <img src="<?php echo admin_url('images/spinner-2x.gif'); ?>" width="20" height="20" alt="Loading...">
            <?php _e('載入中...', 'repair-orders'); ?>
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-order-number">
                        <a href="#" data-sort="order_number">
                            <?php _e('維修單號', 'repair-orders'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column column-date">
                        <a href="#" data-sort="order_date">
                            <?php _e('日期', 'repair-orders'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column column-site">
                        <?php _e('案場', 'repair-orders'); ?>
                    </th>
                    <th scope="col" class="manage-column column-location">
                        <?php _e('位置', 'repair-orders'); ?>
                    </th>
                    <th scope="col" class="manage-column column-reason">
                        <?php _e('原因', 'repair-orders'); ?>
                    </th>
                    <th scope="col" class="manage-column column-worker">
                        <?php _e('工務人員', 'repair-orders'); ?>
                    </th>
                    <th scope="col" class="manage-column column-amount">
                        <?php _e('金額', 'repair-orders'); ?>
                    </th>
                    <th scope="col" class="manage-column column-status">
                        <a href="#" data-sort="status">
                            <?php _e('狀態', 'repair-orders'); ?>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php _e('操作', 'repair-orders'); ?>
                    </th>
                </tr>
            </thead>
            <tbody id="repair-orders-tbody">
                <!-- Content will be loaded via AJAX -->
            </tbody>
        </table>
        
        <div class="pagination-wrap">
            <div class="pagination-info">
                <span id="pagination-info-text"></span>
            </div>
            <div class="pagination-controls">
                <button id="prev-page" class="button" disabled><?php _e('上一頁', 'repair-orders'); ?></button>
                <span class="page-numbers">
                    <span id="current-page">1</span> / <span id="total-pages">1</span>
                </span>
                <button id="next-page" class="button" disabled><?php _e('下一頁', 'repair-orders'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="order-details-modal" class="repair-orders-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php _e('維修單詳細資料', 'repair-orders'); ?></h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <div id="order-details-content">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="button-secondary close-modal"><?php _e('關閉', 'repair-orders'); ?></button>
            <button type="button" class="button-primary" id="edit-order-btn"><?php _e('編輯', 'repair-orders'); ?></button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-confirmation-modal" class="repair-orders-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php _e('確認刪除', 'repair-orders'); ?></h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p><?php _e('您確定要刪除這個維修單嗎？此操作無法復原。', 'repair-orders'); ?></p>
            <div id="delete-order-info"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="button-secondary close-modal"><?php _e('取消', 'repair-orders'); ?></button>
            <button type="button" class="button-primary button-delete" id="confirm-delete-btn"><?php _e('確認刪除', 'repair-orders'); ?></button>
        </div>
    </div>
</div>

<script type="text/template" id="order-row-template">
    <tr data-order-id="<%- id %>">
        <td class="order-number">
            <strong>
                <a href="#" class="view-order" data-order-id="<%- id %>"><%- order_number %></a>
            </strong>
            <div class="row-actions">
                <span class="view">
                    <a href="#" class="view-order" data-order-id="<%- id %>"><?php _e('查看', 'repair-orders'); ?></a> |
                </span>
                <span class="edit">
                    <a href="<?php echo admin_url('admin.php?page=repair-orders-new&edit='); %><%- id %>"><?php _e('編輯', 'repair-orders'); ?></a> |
                </span>
                <span class="delete">
                    <a href="#" class="delete-order" data-order-id="<%- id %>" data-order-number="<%- order_number %>"><?php _e('刪除', 'repair-orders'); ?></a>
                </span>
            </div>
        </td>
        <td class="order-date"><%- order_date %></td>
        <td class="site-name"><%- site ? site.name : '' %></td>
        <td class="location">
            <%- building ? building : '' %>
            <%- floor ? ' / ' + floor : '' %>
            <%- unit ? ' / ' + unit : '' %>
        </td>
        <td class="reason">
            <div class="reason-text" title="<%- reason %>">
                <%- reason.length > 50 ? reason.substring(0, 50) + '...' : reason %>
            </div>
        </td>
        <td class="worker-name"><%- worker ? worker.name : '' %></td>
        <td class="amount">
            <% if (amount && amount > 0) { %>
                NT$ <%- parseFloat(amount).toLocaleString() %>
            <% } %>
        </td>
        <td class="status">
            <span class="status-badge status-<%- status %>">
                <% if (status === 'pending') { %><?php _e('待處理', 'repair-orders'); %><% } %>
                <% if (status === 'in_progress') { %><?php _e('處理中', 'repair-orders'); %><% } %>
                <% if (status === 'completed') { %><?php _e('已完成', 'repair-orders'); %><% } %>
                <% if (status === 'cancelled') { %><?php _e('已取消', 'repair-orders'); %><% } %>
            </span>
        </td>
        <td class="actions">
            <a href="<?php echo home_url('/repair-order/'); ?><%- order_number %>" target="_blank" class="button button-small">
                <?php _e('公開連結', 'repair-orders'); ?>
            </a>
        </td>
    </tr>
</script>

<script type="text/template" id="order-details-template">
    <div class="order-details">
        <div class="order-header">
            <h3><?php _e('維修單號', 'repair-orders'); ?>: <%- order_number %></h3>
            <div class="order-meta">
                <span class="status-badge status-<%- status %>">
                    <% if (status === 'pending') { %><?php _e('待處理', 'repair-orders'); %><% } %>
                    <% if (status === 'in_progress') { %><?php _e('處理中', 'repair-orders'); %><% } %>
                    <% if (status === 'completed') { %><?php _e('已完成', 'repair-orders'); %><% } %>
                    <% if (status === 'cancelled') { %><?php _e('已取消', 'repair-orders'); %><% } %>
                </span>
            </div>
        </div>
        
        <div class="order-info-grid">
            <div class="info-section">
                <h4><?php _e('基本資訊', 'repair-orders'); ?></h4>
                <table class="form-table">
                    <tr>
                        <th><?php _e('日期', 'repair-orders'); ?>:</th>
                        <td><%- order_date %></td>
                    </tr>
                    <tr>
                        <th><?php _e('案場', 'repair-orders'); ?>:</th>
                        <td><%- site ? site.name : '' %></td>
                    </tr>
                    <tr>
                        <th><?php _e('位置', 'repair-orders'); ?>:</th>
                        <td>
                            <%- building ? building : '' %>
                            <%- floor ? ' / ' + floor : '' %>
                            <%- unit ? ' / ' + unit : '' %>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('工務人員', 'repair-orders'); ?>:</th>
                        <td><%- worker ? worker.name : '' %></td>
                    </tr>
                    <tr>
                        <th><?php _e('金額', 'repair-orders'); %>:</th>
                        <td>
                            <% if (amount && amount > 0) { %>
                                NT$ <%- parseFloat(amount).toLocaleString() %>
                            <% } %>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="info-section">
                <h4><?php _e('維修原因', 'repair-orders'); ?></h4>
                <p><%- reason %></p>
            </div>
            
            <% if (photos && photos.length > 0) { %>
            <div class="info-section">
                <h4><?php _e('照片', 'repair-orders'); ?></h4>
                <div class="photos-grid">
                    <% photos.forEach(function(photo) { %>
                        <div class="photo-item">
                            <img src="<%- photo %>" alt="Repair photo" class="repair-photo">
                        </div>
                    <% }); %>
                </div>
            </div>
            <% } %>
            
            <% if (signature) { %>
            <div class="info-section">
                <h4><?php _e('簽名', 'repair-orders'); ?></h4>
                <div class="signature-display">
                    <img src="<%- signature %>" alt="Signature" class="signature-image">
                </div>
            </div>
            <% } %>
        </div>
        
        <div class="order-links">
            <p>
                <strong><?php _e('公開連結', 'repair-orders'); ?>:</strong>
                <a href="<?php echo home_url('/repair-order/'); %><%- order_number %>" target="_blank">
                    <?php echo home_url('/repair-order/'); %><%- order_number %>
                </a>
                <button type="button" class="button button-small copy-link" data-link="<?php echo home_url('/repair-order/'); %><%- order_number %>">
                    <?php _e('複製連結', 'repair-orders'); ?>
                </button>
            </p>
        </div>
    </div>
</script>