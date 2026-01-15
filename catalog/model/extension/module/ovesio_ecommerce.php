<?php
class ModelExtensionModuleOvesioEcommerce extends Model {

	public function getOrders($period_months = 12) {
		$period_months = (int)$period_months;

		$sql = "SELECT op.order_id as order_id, MD5(o.email) as customer_id, p.product_id as sku, op.name, op.quantity, (op.price + op.tax) as price, o.total, o.date_added as `date`
                FROM `" . DB_PREFIX . "order_product` op
                JOIN `" . DB_PREFIX . "product` p ON p.product_id = op.product_id
                JOIN `" . DB_PREFIX . "order` o ON o.order_id = op.order_id
                WHERE o.date_added >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL " . (int)$period_months . " MONTH), '%Y-%m-01') AND o.order_status_id > 0
                ORDER BY op.order_product_id ASC";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getProducts($store_id, $language_id, $customer_group_id) {
		$store_id = (int)$store_id;
		$language_id = (int)$language_id;
		$customer_group_id = (int)$customer_group_id;

		$sql = "SELECT p.product_id as sku, p.product_id, pd.name AS `name`, pd.description AS `description`, p.image, m.name AS manufacturer, p.quantity as quantity, p.stock_status_id, p.price, p.tax_class_id,
            (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$customer_group_id . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) as special
            FROM " . DB_PREFIX . "product p
            JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id)
            LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id)
            LEFT JOIN " . DB_PREFIX . "manufacturer m ON (p.manufacturer_id = m.manufacturer_id)
            WHERE
            pd.language_id = '" . (int)$language_id . "'
            AND p.status = '1' AND p.date_available <= NOW()
            AND p2s.store_id = '" . (int)$store_id . "'
            ORDER BY p.product_id ASC";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getProductCategories($language_id) {
		$language_id = (int)$language_id;
		$cache_key = 'ovesio_ecommerce.categories.' . $language_id;

        $product_categories = $this->cache->get($cache_key);

        if (!$product_categories) {
            $product_categories = [];

            $query = $this->db->query(
            "SELECT p.product_id,
            (
            SELECT GROUP_CONCAT(TRIM(cd.name) SEPARATOR  ' > ')
            FROM " . DB_PREFIX . "category_path cp
            JOIN " . DB_PREFIX . "category_description cd ON cd.category_id = cp.path_id
            WHERE cp.category_id = (SELECT cd.category_id FROM " . DB_PREFIX . "category_path cp
                LEFT JOIN " . DB_PREFIX . "product_to_category p2c ON (cp.category_id = p2c.category_id AND cp.path_id = p2c.category_id)
                LEFT JOIN " . DB_PREFIX . "category c ON (c.category_id = p2c.category_ID)
                LEFT JOIN " . DB_PREFIX . "category_description cd ON (cd.category_id = c.category_id AND cd.language_id = $language_id)
            WHERE p2c.product_id = p.product_id AND c.status = 1
            ORDER BY cp.level DESC
            LIMIT 1)
            ORDER BY level
            ) as `last_category_path`
            FROM " . DB_PREFIX . "product p");

            foreach ($query->rows as $row) {
                $product_categories[$row['product_id']] = $row['last_category_path'];
            }

            $this->cache->set($cache_key, $product_categories);
        }

        return $product_categories;
	}
}
