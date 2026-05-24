import { initWarehouseLogic } from './import-warehouse';
import { apiMessage, formatMoney, showToast } from './utils/ui';

window.WmsUi = {
    apiMessage,
    formatMoney,
    showToast,
};

if (document.getElementById('warehouseTab')) {
    initWarehouseLogic();
}

import './layout/sidebar';
