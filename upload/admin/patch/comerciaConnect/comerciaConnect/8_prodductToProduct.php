<?php
use comercia\Util;

return function () {
    Util::db()->query("DROP TRIGGER IF EXISTS `ccDeleteProductCategory`;CREATE TRIGGER ccDeleteProductStore AFTER DELETE ON `" . DB_PREFIX . "product_to_store` FOR EACH ROW BEGIN INSERT INTO `" . DB_PREFIX . "ccDeletedEntities` SET `type` = 'productStore', `entityId` = concat(old.product_id,'_',old.store_id), `isCleaned` = 0; END");
    Util::db()->query("DROP TRIGGER IF EXISTS `ccDeleteProductCategory`;CREATE TRIGGER ccDeleteProductCategory AFTER DELETE ON `" . DB_PREFIX . "product_to_category` FOR EACH ROW BEGIN INSERT INTO `" . DB_PREFIX . "ccDeletedEntities` SET `type` = 'productCategory', `entityId` = concat(old.product_id,'_',old.category_id), `isCleaned` = 0; END");
};