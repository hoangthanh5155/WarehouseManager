import { initWarehouseLogic } from './import-warehouse';

// Kiểm tra xem trang hiện tại có Tab Nhập Kho không thì chạy logic luôn
if (document.getElementById('warehouseTab')) {
    initWarehouseLogic();
}

// Gọi file sidebar
import './layout/sidebar';