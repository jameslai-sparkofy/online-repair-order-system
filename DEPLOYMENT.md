# 線上維修單系統 - 部署說明

## 系統概述

線上維修單是一個功能完整的 WordPress 插件，提供以下核心功能：

### ✅ 已完成功能
1. **WordPress 插件架構** - 完整的插件結構與 WordPress 整合
2. **資料庫設計** - 維修單、工務人員、案場三大主要資料表
3. **管理後台** - 完整的管理界面與操作功能
4. **REST API** - 前後端分離的 API 設計
5. **檔案上傳** - 多照片上傳與數位簽名功能
6. **篩選搜尋** - 進階篩選與排序功能
7. **GitHub 整合** - 自動備份與 webhook 支援

### 🎯 核心特色
- **響應式設計** - 支援手機與桌面裝置
- **專業界面** - 遵循 WordPress 設計規範
- **安全性** - 完整的權限檢查與資料驗證
- **擴展性** - 模組化設計，易於維護與擴展

## 部署步驟

### 1. 環境需求
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **MySQL**: 5.7+
- **伺服器**: 支援 WordPress 的網頁伺服器

### 2. 安裝插件

#### 方法一：直接上傳
```bash
# 將整個專案資料夾複製到 WordPress 插件目錄
cp -r "線上維修單" /wp-content/plugins/repair-orders/

# 或使用主插件檔案作為根目錄
cp repair-orders.php /wp-content/plugins/
cp -r src/ /wp-content/plugins/
```

#### 方法二：從 GitHub 部署
```bash
cd /wp-content/plugins/
git clone https://github.com/jameslai-sparkofy/online-repair-order-system.git repair-orders
```

### 3. 啟用插件
1. 登入 WordPress 管理後台
2. 前往「插件」頁面
3. 找到「線上維修單 (Online Repair Order System)」
4. 點擊「啟用」

### 4. 資料庫初始化
插件啟用時會自動執行：
- ✅ 建立資料表 (`wp_repair_orders`, `wp_repair_workers`, `wp_repair_sites`)
- ✅ 建立上傳目錄結構
- ✅ 設定權限與安全性

### 5. 功能驗證
啟用後檢查：
- [ ] 管理選單中出現「維修單」項目
- [ ] 可以新增案場和工務人員
- [ ] 可以建立維修單
- [ ] 照片上傳功能正常
- [ ] 數位簽名功能正常

## 使用說明

### 管理後台操作

#### 1. 維修單管理
- **路徑**: `管理後台 > 維修單 > 所有維修單`
- **功能**: 列表檢視、篩選、排序、詳細檢視、編輯、刪除

#### 2. 新增維修單
- **路徑**: `管理後台 > 維修單 > 新增維修單`
- **必填欄位**: 日期、維修原因
- **可選欄位**: 案場、位置、工務人員、金額、照片、簽名

#### 3. 案場管理
- **路徑**: `管理後台 > 維修單 > 案場管理`
- **功能**: 新增、編輯、刪除案場資訊

#### 4. 工務人員管理
- **路徑**: `管理後台 > 維修單 > 工務人員`
- **功能**: 新增、編輯、刪除工務人員資訊

### API 端點

基礎 URL: `/wp-json/repair-orders/v1/`

#### 維修單 API
- `GET /orders` - 取得維修單列表
- `POST /orders` - 建立新維修單
- `GET /orders/{id}` - 取得特定維修單
- `PUT /orders/{id}` - 更新維修單
- `DELETE /orders/{id}` - 刪除維修單
- `GET /orders/by-number/{number}` - 依維修單號取得（公開）

#### 工務人員 API
- `GET /workers` - 取得工務人員列表
- `POST /workers` - 建立新工務人員
- `GET /workers/{id}` - 取得特定工務人員
- `PUT /workers/{id}` - 更新工務人員
- `DELETE /workers/{id}` - 刪除工務人員

#### 案場 API
- `GET /sites` - 取得案場列表
- `POST /sites` - 建立新案場
- `GET /sites/{id}` - 取得特定案場
- `PUT /sites/{id}` - 更新案場
- `DELETE /sites/{id}` - 刪除案場

#### 檔案上傳 API
- `POST /upload/photo` - 上傳照片
- `POST /upload/signature` - 上傳簽名（公開）

## GitHub 自動更新設定

### 1. Webhook 設定
1. 前往 GitHub repository 設定
2. 選擇「Webhooks」
3. 新增 webhook：
   - **Payload URL**: `https://yourdomain.com/wp-json/repair-orders/v1/github-webhook`
   - **Content type**: `application/json`
   - **Secret**: 在 WordPress 設定中配置
   - **Events**: 選擇 "Just the push event"

### 2. WordPress 設定
```php
// 在 wp-config.php 或插件設定中添加
define('REPAIR_ORDERS_GITHUB_WEBHOOK_SECRET', 'your-secret-key');
```

### 3. 自動更新流程
1. 程式碼推送到 GitHub main 分支
2. GitHub 發送 webhook 到 WordPress
3. 插件接收並驗證 webhook
4. 自動更新插件檔案（需要實作更新邏輯）

## 檔案結構說明

```
線上維修單/
├── repair-orders.php              # 主插件檔案
├── src/main/
│   ├── php/                       # PHP 後端程式
│   │   ├── api/RestController.php # REST API 控制器
│   │   └── models/                # 資料模型
│   ├── js/                        # JavaScript 前端程式
│   │   └── admin.js              # 管理後台 JS
│   ├── css/                       # 樣式表
│   │   └── admin.css             # 管理後台 CSS
│   └── resources/
│       └── templates/            # PHP 模板
└── docs/                         # 文件目錄
```

## 安全性考量

### 1. 權限控制
- ✅ 管理功能需要 `manage_options` 權限
- ✅ 公開查看功能允許匿名存取
- ✅ 所有輸入資料經過驗證與清理

### 2. 檔案上傳安全
- ✅ 限制檔案類型（僅允許圖片）
- ✅ 檔案名稱隨機化
- ✅ 上傳目錄禁止直接存取

### 3. 資料庫安全
- ✅ 使用 WordPress 資料庫 API
- ✅ 所有查詢使用預備語句
- ✅ 輸入資料經過清理

## 疑難排解

### 常見問題

#### 1. 插件啟用失敗
- 檢查 PHP 版本是否 7.4+
- 檢查檔案權限
- 查看 WordPress 錯誤日誌

#### 2. 資料表建立失敗
- 檢查資料庫權限
- 確認 WordPress 資料庫設定正確
- 查看 MySQL 錯誤日誌

#### 3. 檔案上傳失敗
- 檢查上傳目錄權限
- 確認 PHP 上傳設定
- 檢查磁碟空間

#### 4. JavaScript 功能異常
- 檢查瀏覽器控制台錯誤
- 確認 WordPress 載入 jQuery
- 檢查檔案路徑

### 除錯模式
```php
// 在 wp-config.php 中啟用除錯
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## 效能優化

### 1. 資料庫優化
- 定期清理過期資料
- 考慮新增索引
- 監控查詢效能

### 2. 檔案快取
- 設定適當的快取標頭
- 考慮使用 CDN
- 壓縮圖片檔案

### 3. 程式碼優化
- 最小化 CSS/JS 檔案
- 延遲載入非必要資源
- 使用物件快取

## 未來擴展

### 計畫功能
- [ ] 電子郵件通知
- [ ] 行動裝置 APP
- [ ] 報表統計功能
- [ ] 多語言支援
- [ ] 工作流程自動化

### 客製化建議
- 可依需求調整欄位
- 可整合第三方服務
- 可擴展權限系統
- 可客製化報表

---

**開發者**: Claude Code  
**專案地址**: https://github.com/jameslai-sparkofy/online-repair-order-system  
**版本**: 1.0.0  
**更新日期**: 2025-07-05