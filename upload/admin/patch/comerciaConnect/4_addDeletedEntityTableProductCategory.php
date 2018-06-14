<?php
use comercia\Util;

return function () {
    Util::db()->query("DROP TRIGGER IF EXISTS `ccDeleteProductCategory`");
    Util::db()->query("CREATE TRIGGER ccDeleteProductCategory AFTER DELETE ON `" . DB_PREFIX . "product_to_category` FOR EACH ROW BEGIN INSERT INTO `" . DB_PREFIX . "ccDeletedEntities` SET `type` = 'prodductCategory', `entityId` = concat(old.product_id,'_',old.category_id), `isCleaned` = 0; END");
};