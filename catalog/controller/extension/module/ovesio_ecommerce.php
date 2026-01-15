<?php

ini_set('serialize_precision', -1);

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
ini_set('memory_limit', '2G');

class ControllerExtensionModuleOvesioEcommerce extends Controller {

	public function index() {
        $this->load->language('extension/module/ovesio_ecommerce');

        if (!$this->config->get('module_ovesio_ecommerce_status')) {
            $this->response->setOutput(json_encode(['error' => $this->language->get('error_disabled')]));
            return;
        }

        $hash = $this->request->get['hash'] ?? null;
        $configured_hash = $this->config->get('module_ovesio_ecommerce_hash');

        if(!$configured_hash || $hash !== $configured_hash)
        {
            $this->response->setOutput(json_encode(['error' => $this->language->get('error_access_denied')]));
            return;
        }

        // Load new model
        $this->load->model('extension/module/ovesio_ecommerce');

        $action = (isset($this->request->get['action']) && $this->request->get['action'] == 'orders') ? 'orders' : 'products';

        if($action == 'orders')
        {
            $duration_months = (int)$this->config->get('module_ovesio_ecommerce_export_duration');
            if ($duration_months <= 0) $duration_months = 12; // default

            // Use model to get orders
            $orders = $this->model_extension_module_ovesio_ecommerce->getOrders($duration_months);

            $data = [];
            /**
             * order_id
             * customer_id | optional
             * total
             * date
             * products
                * sku
                * name
                * quantity
                * price
            */
            foreach($orders as $row)
            {
                $data[$row['order_id']]['order_id'] = $row['order_id'];
                $data[$row['order_id']]['customer_id'] = $row['customer_id'];
                $data[$row['order_id']]['total'] = $row['total'];
                $data[$row['order_id']]['date'] = $row['date'];
                $data[$row['order_id']]['products'][] = [
                    'sku' => $row['sku'],
                    'name' => $row['name'],
                    'quantity' => $row['quantity'],
                    'price' => $row['price'],
                ];
            }
            $data = array_values($data);
        } else {
            /**
             * sku
             * name
             * description | optional
             * category: Cat > Sub Category 1 > Sub Category 2 | optional
             * manufacturer | optional
             * availability: in_stock / preorder / out_of_stock / discontinued | optional | default in_stock
             * quantity
             * price
             * image | optional
             * url | optional
             * lang | optional
            */

            $data = [];

            // Use model to get categories
            $categories = $this->model_extension_module_ovesio_ecommerce->getProductCategories((int)$this->config->get('config_language_id'));

            // Use model to get products
            $products = $this->model_extension_module_ovesio_ecommerce->getProducts(
                $this->config->get('config_store_id'),
                $this->config->get('config_language_id'),
                $this->config->get('config_customer_group_id')
            );

            foreach($products as $row) {
                // Determine SKU (model or product_id fallback handled in query, but redundancy check if model is empty string)
                $sku = $row['sku'];
                if(empty($sku)) {
                    $sku = $row['product_id'];
                }

                $data[$sku]['sku'] = $sku;
                $data[$sku]['name'] = strip_tags(html_entity_decode($row['name'], ENT_QUOTES, 'UTF-8'));
                $data[$sku]['quantity'] = (int) $row['quantity'];

                $price = !empty($row['special']) ? $row['special'] : $row['price'];
                $price = $this->tax->calculate($price, $row['tax_class_id'], $this->config->get('config_tax'));
                $data[$sku]['price'] = $price;

                /**
                 * Optional rows
                 */
                if($row['quantity'] <= 0) {
                    $data[$sku]['availability'] =  'out_of_stock';
                } else {
                    $data[$sku]['availability'] =  'in_stock';
                }

                $data[$sku]['description'] = $this->htmlToPlainText(html_entity_decode($row['description'], ENT_QUOTES, 'UTF-8'));

                $data[$sku]['manufacturer'] = strip_tags(html_entity_decode((string)$row['manufacturer'], ENT_QUOTES, 'UTF-8'));

                $data[$sku]['image'] = $row['image'] ? HTTPS_SERVER . 'image/' . html_entity_decode($row['image'], ENT_QUOTES, 'UTF-8') : null;

                $data[$sku]['url'] = $this->url->link('product/product', 'product_id=' . $row['product_id']);

                $data[$sku]['category'] = isset($categories[$row['product_id']]) ? strip_tags(html_entity_decode($categories[$row['product_id']], ENT_QUOTES, 'UTF-8')) : null;
            }

            $data = array_values($data);
        }

        $filename = "export_" . ($action == 'orders' ? 'orders' : 'products') . "_" . date('Y-m-d');

        $filename .= ".json";

        $this->response->addHeader('Content-Disposition: attachment; filename="' . $filename . '";');
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode(['data' => $data], JSON_PRETTY_PRINT));
    }

    private function htmlToPlainText($content) {
        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\t+/', ' ', $text);
        $text = preg_replace('/ +/', ' ', $text);
        $text = preg_replace("/(\r?\n){2,}/", "\n", $text);

        return trim($text);
    }
}
