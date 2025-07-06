jQuery(document).ready(function($) {
    'use strict';
    
    const RepairOrdersAdmin = {
        currentPage: 1,
        totalPages: 1,
        currentSort: 'created_at',
        currentSortDirection: 'DESC',
        uploadedPhotos: [],
        signatureCanvas: null,
        signatureContext: null,
        isDrawing: false,
        
        init: function() {
            this.bindEvents();
            this.initializeSignatureCanvas();
            
            if ($('#repair-orders-list').length) {
                this.loadOrdersList();
                this.loadFilters();
            }
            
            if ($('#repair-order-form').length) {
                this.loadFormData();
            }
        },
        
        bindEvents: function() {
            // List page events
            $(document).on('click', '.view-order', this.viewOrder.bind(this));
            $(document).on('click', '.delete-order', this.deleteOrder.bind(this));
            $(document).on('click', '#prev-page', this.prevPage.bind(this));
            $(document).on('click', '#next-page', this.nextPage.bind(this));
            $(document).on('click', '[data-sort]', this.sortOrders.bind(this));
            $(document).on('change', '.repair-orders-filters select, .repair-orders-filters input', this.filterOrders.bind(this));
            
            // Form page events
            $(document).on('submit', '#repair-order-form', this.saveOrder.bind(this));
            $(document).on('click', '#select-photos-btn', this.selectPhotos.bind(this));
            $(document).on('change', '#photos', this.handlePhotoSelection.bind(this));
            $(document).on('click', '.remove-photo', this.removePhoto.bind(this));
            $(document).on('click', '#add-site-btn', this.showAddSiteModal.bind(this));
            $(document).on('click', '#add-worker-btn', this.showAddWorkerModal.bind(this));
            $(document).on('click', '#save-site-btn', this.saveSite.bind(this));
            $(document).on('click', '#save-worker-btn', this.saveWorker.bind(this));
            $(document).on('click', '#generate-link-btn', this.generatePublicLink.bind(this));
            $(document).on('click', '#copy-link-btn, .copy-link', this.copyLink.bind(this));
            
            // Signature events
            $(document).on('click', '#clear-signature', this.clearSignature.bind(this));
            $(document).on('click', '#save-signature', this.saveSignature.bind(this));
            
            // Modal events
            $(document).on('click', '.close, .close-modal', this.closeModal.bind(this));
            $(document).on('click', '.repair-orders-modal', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
            
            // Confirm delete
            $(document).on('click', '#confirm-delete-btn', this.confirmDelete.bind(this));
            
            // Drag and drop for photos
            this.initPhotoDropzone();
        },
        
        loadOrdersList: function() {
            const self = this;
            
            $('#repair-orders-tbody').html('<tr><td colspan="9" class="loading">載入中...</td></tr>');
            
            const filters = this.getFilters();
            
            $.ajax({
                url: repairOrdersAdmin.restUrl + 'orders',
                method: 'GET',
                data: {
                    ...filters,
                    page: this.currentPage,
                    per_page: 20,
                    order_by: this.currentSort,
                    order_direction: this.currentSortDirection
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', repairOrdersAdmin.restNonce);
                },
                success: function(response) {
                    self.renderOrdersList(response.orders);
                    self.updatePagination(response);
                },
                error: function(xhr) {
                    $('#repair-orders-tbody').html('<tr><td colspan="9" class="error">載入失敗: ' + xhr.responseText + '</td></tr>');
                }
            });
        },
        
        renderOrdersList: function(orders) {
            const template = $('#order-row-template').html();
            const tbody = $('#repair-orders-tbody');
            
            if (orders.length === 0) {
                tbody.html('<tr><td colspan="9" class="no-items">沒有找到維修單</td></tr>');
                return;
            }
            
            tbody.empty();
            
            orders.forEach(function(order) {
                const html = _.template(template)(order);
                tbody.append(html);
            });
        },
        
        updatePagination: function(response) {
            this.totalPages = Math.ceil(response.total / response.per_page);
            
            $('#current-page').text(response.page);
            $('#total-pages').text(this.totalPages);
            $('#pagination-info-text').text('共 ' + response.total + ' 筆記錄');
            
            $('#prev-page').prop('disabled', response.page <= 1);
            $('#next-page').prop('disabled', response.page >= this.totalPages);
        },
        
        getFilters: function() {
            return {
                site_id: $('#site_filter').val() || '',
                worker_id: $('#worker_filter').val() || '',
                status: $('#status_filter').val() || '',
                date_from: $('#date_from').val() || '',
                date_to: $('#date_to').val() || ''
            };
        },
        
        filterOrders: function() {
            this.currentPage = 1;
            this.loadOrdersList();
        },
        
        sortOrders: function(e) {
            e.preventDefault();
            
            const column = $(e.target).data('sort');
            
            if (this.currentSort === column) {
                this.currentSortDirection = this.currentSortDirection === 'ASC' ? 'DESC' : 'ASC';
            } else {
                this.currentSort = column;
                this.currentSortDirection = 'ASC';
            }
            
            // Update UI indicators
            $('.sorting-indicator').removeClass('asc desc');
            $(e.target).find('.sorting-indicator').addClass(this.currentSortDirection.toLowerCase());
            
            this.loadOrdersList();
        },
        
        prevPage: function() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadOrdersList();
            }
        },
        
        nextPage: function() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadOrdersList();
            }
        },
        
        viewOrder: function(e) {
            e.preventDefault();
            
            const orderId = $(e.target).data('order-id');
            const self = this;
            
            $.ajax({
                url: repairOrdersAdmin.restUrl + 'orders/' + orderId,
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', repairOrdersAdmin.restNonce);
                },
                success: function(order) {
                    self.showOrderDetails(order);
                },
                error: function(xhr) {
                    alert('載入失敗: ' + xhr.responseText);
                }
            });
        },
        
        showOrderDetails: function(order) {
            const template = $('#order-details-template').html();
            const html = _.template(template)(order);
            
            $('#order-details-content').html(html);
            $('#order-details-modal').show();
            
            $('#edit-order-btn').off('click').on('click', function() {
                window.location.href = repairOrdersAdmin.adminUrl + 'admin.php?page=repair-orders-new&edit=' + order.id;
            });
        },
        
        deleteOrder: function(e) {
            e.preventDefault();
            
            const orderId = $(e.target).data('order-id');
            const orderNumber = $(e.target).data('order-number');
            
            $('#delete-order-info').html('<strong>維修單號:</strong> ' + orderNumber);
            $('#delete-confirmation-modal').show();
            
            $('#confirm-delete-btn').data('order-id', orderId);
        },
        
        confirmDelete: function(e) {
            const orderId = $(e.target).data('order-id');
            const self = this;
            
            $.ajax({
                url: repairOrdersAdmin.restUrl + 'orders/' + orderId,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', repairOrdersAdmin.restNonce);
                },
                success: function() {
                    $('#delete-confirmation-modal').hide();
                    self.loadOrdersList();
                    self.showNotice('維修單已成功刪除', 'success');
                },
                error: function(xhr) {
                    alert('刪除失敗: ' + xhr.responseText);
                }
            });
        },
        
        loadFilters: function() {
            // Load sites for filter
            $.ajax({
                url: repairOrdersAdmin.restUrl + 'sites',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', repairOrdersAdmin.restNonce);
                },
                success: function(response) {
                    const select = $('#site_filter');
                    response.sites.forEach(function(site) {
                        select.append('<option value="' + site.id + '">' + site.name + '</option>');
                    });
                }
            });
            
            // Load workers for filter
            $.ajax({
                url: repairOrdersAdmin.restUrl + 'workers',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', repairOrdersAdmin.restNonce);
                },
                success: function(response) {
                    const select = $('#worker_filter');
                    response.workers.forEach(function(worker) {
                        select.append('<option value="' + worker.id + '">' + worker.name + '</option>');
                    });
                }
            });
        },
        
        loadFormData: function() {
            this.loadSites();
            this.loadWorkers();
            
            const isEdit = $('#is-edit').val() === '1';
            const orderId = $('#order-id').val();
            
            if (isEdit && orderId) {
                this.loadOrderForEdit(orderId);
            }
        },
        
        loadSites: function() {
            $.ajax({
                url: repairOrdersAdmin.restUrl + 'sites',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', repairOrdersAdmin.restNonce);
                },
                success: function(response) {
                    const select = $('#site-id');
                    response.sites.forEach(function(site) {
                        select.append('<option value="' + site.id + '">' + site.name + '</option>');
                    });
                }
            });
        },
        
        loadWorkers: function() {
            $.ajax({
                url: repairOrdersAdmin.restUrl + 'workers',
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', repairOrdersAdmin.restNonce);
                },
                success: function(response) {
                    const select = $('#worker-id');
                    response.workers.forEach(function(worker) {
                        select.append('<option value="' + worker.id + '">' + worker.name + '</option>');
                    });
                }
            });
        },
        
        loadOrderForEdit: function(orderId) {
            const self = this;
            
            $.ajax({
                url: repairOrdersAdmin.restUrl + 'orders/' + orderId,
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', repairOrdersAdmin.restNonce);
                },
                success: function(order) {
                    self.populateForm(order);
                },
                error: function(xhr) {
                    alert('載入維修單失敗: ' + xhr.responseText);
                }
            });
        },
        
        populateForm: function(order) {
            $('#order-number').val(order.order_number);
            $('#order-date').val(order.order_date);
            $('#site-id').val(order.site_id);
            $('#building').val(order.building);
            $('#floor').val(order.floor);
            $('#unit').val(order.unit);
            $('#worker-id').val(order.worker_id);
            $('#amount').val(order.amount);
            $('#status').val(order.status);
            $('#reason').val(order.reason);
            
            if (order.photos && order.photos.length > 0) {
                this.displayExistingPhotos(order.photos);
            }
            
            if (order.signature) {
                $('#signature-preview img').attr('src', order.signature);
                $('#signature-preview').show();
                $('#signature-data').val(order.signature);
            }
        },
        
        displayExistingPhotos: function(photos) {
            const container = $('#photo-preview-container');
            
            photos.forEach(function(photo, index) {
                const photoHtml = '<div class="photo-preview-item existing-photo" data-photo-url="' + photo + '">' +
                    '<div class="photo-preview">' +
                    '<img src="' + photo + '" alt="Existing photo">' +
                    '<button type="button" class="remove-photo" data-photo-url="' + photo + '">' +
                    '<span class="dashicons dashicons-no-alt"></span>' +
                    '</button>' +
                    '</div>' +
                    '</div>';
                
                container.append(photoHtml);
            });
        },
        
        saveOrder: function(e) {
            e.preventDefault();
            
            const self = this;
            const form = $('#repair-order-form');
            const isEdit = $('#is-edit').val() === '1';
            const orderId = $('#order-id').val();
            
            const formData = {
                order_date: $('#order-date').val(),
                site_id: $('#site-id').val() || null,
                building: $('#building').val(),
                floor: $('#floor').val(),
                unit: $('#unit').val(),
                worker_id: $('#worker-id').val() || null,
                amount: $('#amount').val() || null,
                status: $('#status').val(),
                reason: $('#reason').val(),
                photos: this.getPhotoUrls(),
                signature: $('#signature-data').val()
            };
            
            if (!isEdit) {
                delete formData.order_number;
            }
            
            $('#form-loading').show();
            form.find('input[type="submit"]').prop('disabled', true);
            
            const url = isEdit ? 
                repairOrdersAdmin.restUrl + 'orders/' + orderId :
                repairOrdersAdmin.restUrl + 'orders';
            
            const method = isEdit ? 'PUT' : 'POST';
            
            $.ajax({
                url: url,
                method: method,
                data: JSON.stringify(formData),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', repairOrdersAdmin.restNonce);
                },
                success: function(response) {
                    self.showNotice(response.message, 'success');
                    
                    if (!isEdit) {
                        // Redirect to edit page for new orders
                        window.location.href = repairOrdersAdmin.adminUrl + 'admin.php?page=repair-orders-new&edit=' + response.order.id;
                    } else {
                        // Update order number if editing
                        $('#order-number').val(response.order.order_number);
                    }
                },
                error: function(xhr) {
                    self.showNotice('儲存失敗: ' + xhr.responseText, 'error');
                },
                complete: function() {
                    $('#form-loading').hide();
                    form.find('input[type="submit"]').prop('disabled', false);
                }
            });
        },
        
        getPhotoUrls: function() {
            const urls = [];
            
            // Existing photos
            $('.existing-photo').each(function() {
                urls.push($(this).data('photo-url'));
            });
            
            // Newly uploaded photos
            this.uploadedPhotos.forEach(function(photo) {
                if (photo.url) {
                    urls.push(photo.url);
                }
            });
            
            return urls;
        },
        
        selectPhotos: function() {
            $('#photos').click();
        },
        
        handlePhotoSelection: function(e) {
            const files = e.target.files;
            
            for (let i = 0; i < files.length; i++) {
                this.addPhotoPreview(files[i], i);
                this.uploadPhoto(files[i], i);
            }
        },
        
        addPhotoPreview: function(file, index) {
            const template = $('#photo-preview-template').html();
            const reader = new FileReader();
            const self = this;
            
            reader.onload = function(e) {
                const html = _.template(template)({
                    index: index,
                    src: e.target.result,
                    name: file.name,
                    size: self.formatFileSize(file.size)
                });
                
                $('#photo-preview-container').append(html);
            };
            
            reader.readAsDataURL(file);
        },
        
        uploadPhoto: function(file, index) {
            const self = this;
            const formData = new FormData();
            formData.append('photo', file);
            
            $.ajax({
                url: repairOrdersAdmin.restUrl + 'upload/photo',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', repairOrdersAdmin.restNonce);
                },
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            $('.photo-preview-item[data-file-index="' + index + '"] .progress-bar')
                                .css('width', percentComplete + '%');
                        }
                    });
                    return xhr;
                },
                success: function(response) {
                    self.uploadedPhotos[index] = {
                        url: response.file_url,
                        filename: response.filename
                    };
                    
                    $('.photo-preview-item[data-file-index="' + index + '"] .upload-progress').hide();
                },
                error: function(xhr) {
                    $('.photo-preview-item[data-file-index="' + index + '"]').addClass('upload-error');
                    alert('照片上傳失敗: ' + xhr.responseText);
                }
            });
        },
        
        removePhoto: function(e) {
            const $item = $(e.target).closest('.photo-preview-item');
            
            if ($item.hasClass('existing-photo')) {
                // Remove from existing photos
                $item.remove();
            } else {
                // Remove from uploaded photos
                const index = $(e.target).data('file-index');
                delete this.uploadedPhotos[index];
                $item.remove();
            }
        },
        
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        initPhotoDropzone: function() {
            const dropzone = $('#upload-dropzone');
            
            dropzone.on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });
            
            dropzone.on('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });
            
            dropzone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                
                const files = e.originalEvent.dataTransfer.files;
                const input = $('#photos')[0];
                input.files = files;
                $(input).trigger('change');
            });
            
            dropzone.on('click', function() {
                $('#photos').click();
            });
        },
        
        initializeSignatureCanvas: function() {
            const canvas = $('#signature-canvas')[0];
            if (!canvas) return;
            
            this.signatureCanvas = canvas;
            this.signatureContext = canvas.getContext('2d');
            
            // Set canvas size
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width;
            canvas.height = rect.height;
            
            // Set drawing styles
            this.signatureContext.strokeStyle = '#000000';
            this.signatureContext.lineWidth = 2;
            this.signatureContext.lineCap = 'round';
            
            this.bindSignatureEvents();
        },
        
        bindSignatureEvents: function() {
            const self = this;
            const canvas = $(this.signatureCanvas);
            
            canvas.on('mousedown', function(e) {
                self.isDrawing = true;
                self.signatureContext.beginPath();
                self.signatureContext.moveTo(e.offsetX, e.offsetY);
            });
            
            canvas.on('mousemove', function(e) {
                if (!self.isDrawing) return;
                
                self.signatureContext.lineTo(e.offsetX, e.offsetY);
                self.signatureContext.stroke();
            });
            
            canvas.on('mouseup mouseout', function() {
                self.isDrawing = false;
            });
            
            // Touch events for mobile
            canvas.on('touchstart', function(e) {
                e.preventDefault();
                const touch = e.originalEvent.touches[0];
                const rect = canvas[0].getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;
                
                self.isDrawing = true;
                self.signatureContext.beginPath();
                self.signatureContext.moveTo(x, y);
            });
            
            canvas.on('touchmove', function(e) {
                e.preventDefault();
                if (!self.isDrawing) return;
                
                const touch = e.originalEvent.touches[0];
                const rect = canvas[0].getBoundingClientRect();
                const x = touch.clientX - rect.left;
                const y = touch.clientY - rect.top;
                
                self.signatureContext.lineTo(x, y);
                self.signatureContext.stroke();
            });
            
            canvas.on('touchend', function(e) {
                e.preventDefault();
                self.isDrawing = false;
            });
        },
        
        clearSignature: function() {
            this.signatureContext.clearRect(0, 0, this.signatureCanvas.width, this.signatureCanvas.height);
            $('#signature-preview').hide();
            $('#signature-data').val('');
        },
        
        saveSignature: function() {
            const dataURL = this.signatureCanvas.toDataURL('image/png');
            $('#signature-data').val(dataURL);
            $('#signature-preview img').attr('src', dataURL);
            $('#signature-preview').show();
        },
        
        showAddSiteModal: function() {
            $('#add-site-modal').show();
        },
        
        showAddWorkerModal: function() {
            $('#add-worker-modal').show();
        },
        
        saveSite: function() {
            const self = this;
            const formData = {
                name: $('#site-name').val(),
                address: $('#site-address').val()
            };
            
            if (!formData.name) {
                alert('請輸入案場名稱');
                return;
            }
            
            $.ajax({
                url: repairOrdersAdmin.restUrl + 'sites',
                method: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', repairOrdersAdmin.restNonce);
                },
                success: function(response) {
                    $('#site-id').append('<option value="' + response.site.id + '">' + response.site.name + '</option>');
                    $('#site-id').val(response.site.id);
                    $('#add-site-modal').hide();
                    $('#add-site-form')[0].reset();
                    self.showNotice(response.message, 'success');
                },
                error: function(xhr) {
                    alert('新增案場失敗: ' + xhr.responseText);
                }
            });
        },
        
        saveWorker: function() {
            const self = this;
            const formData = {
                name: $('#worker-name').val(),
                email: $('#worker-email').val(),
                phone: $('#worker-phone').val()
            };
            
            if (!formData.name) {
                alert('請輸入工務人員姓名');
                return;
            }
            
            $.ajax({
                url: repairOrdersAdmin.restUrl + 'workers',
                method: 'POST',
                data: JSON.stringify(formData),
                contentType: 'application/json',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', repairOrdersAdmin.restNonce);
                },
                success: function(response) {
                    $('#worker-id').append('<option value="' + response.worker.id + '">' + response.worker.name + '</option>');
                    $('#worker-id').val(response.worker.id);
                    $('#add-worker-modal').hide();
                    $('#add-worker-form')[0].reset();
                    self.showNotice(response.message, 'success');
                },
                error: function(xhr) {
                    alert('新增工務人員失敗: ' + xhr.responseText);
                }
            });
        },
        
        generatePublicLink: function() {
            const orderNumber = $('#order-number').val();
            if (!orderNumber) {
                alert('請先儲存維修單');
                return;
            }
            
            const link = window.location.origin + '/repair-order/' + orderNumber;
            $('#public-link').val(link);
            $('#public-link-container').show();
        },
        
        copyLink: function(e) {
            const link = $(e.target).data('link') || $('#public-link').val();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(link).then(function() {
                    alert('連結已複製到剪貼簿');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = link;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                alert('連結已複製到剪貼簿');
            }
        },
        
        closeModal: function() {
            $('.repair-orders-modal').hide();
        },
        
        showNotice: function(message, type) {
            const notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after(notice);
            
            setTimeout(function() {
                notice.fadeOut();
            }, 5000);
        }
    };
    
    RepairOrdersAdmin.init();
});