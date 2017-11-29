<?php
use comercia\Util;

return function () {
    Util::patch()->table("ccDeletedEntities")
        ->addField('table', 'varchar(250)')
        ->addField('entityId', 'int')
        ->addField('isCleaned', 'tinyint')
        ->save();

    Util::db()->query("CREATE TRIGGER ccDeleteProduct AFTER DELETE ON `" . DB_PREFIX . "product` FOR EACH ROW BEGIN INSERT INTO `" . DB_PREFIX . "ccDeletedEntities` SET `table` = 'product', entityId = old.product_id, isCleaned = 0; END");
    Util::db()->query("CREATE TRIGGER ccDeleteOrder AFTER DELETE ON `" . DB_PREFIX . "order` FOR EACH ROW BEGIN INSERT INTO `" . DB_PREFIX . "ccDeletedEntities` SET `table` = 'order', entityId = old.order_id, isCleaned = 0; END");
    Util::db()->query("CREATE TRIGGER ccDeleteCategory AFTER DELETE ON `" . DB_PREFIX . "category` FOR EACH ROW BEGIN INSERT INTO `" . DB_PREFIX . "ccDeletedEntities` SET `table` = 'category', entityId = old.category_id, isCleaned = 0; END");
};