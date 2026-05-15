import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/js/app.js',                         // File tổng (Sidebar + Nhập kho cũ)
                'resources/js/products/lookup.js',             // File tra cứu sản phẩm mới tách
                'resources/js/products/pricing.js',            // File tính giá % mới tách
                'resources/js/warehouse/export.js',            // Thêm file xử lý Xuất kho mới
                'resources/js/pages/print_invoice.js'          // 💡 Thêm file xử lý In hóa đơn
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0', // Cho phép truy cập từ bên ngoài vào Vite
        hmr: {
            host: '103.77.214.206' // Giữ nguyên IP VPS của bạn
        }
    },
});