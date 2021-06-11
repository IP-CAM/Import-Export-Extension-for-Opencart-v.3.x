<?php
class ModelExtensionModuleImportExportNik extends Model {
    protected $null_array = array();

//	public function install() {
//		$this->db->query("
//		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "fraud_ip` (
//		  `ip` varchar(40) NOT NULL,
//		  `date_added` datetime NOT NULL,
//		  PRIMARY KEY (`ip`)
//		) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
//		");
//	}

//	public function uninstall() {
//		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ip`");
//	}

    public function getCategory($category_id, $data) {
        $sql = "SELECT cp.category_id AS category_id";

        if (isset($data['category_name']) && !empty($data['category_name'])) {
            $sql .= ", cd2.name AS name";
        }

        if (isset($data['category_description']) && !empty($data['category_description'])) {
            $sql .= ", cd2.description AS description";
        }

        if (isset($data['category_meta_title']) && !empty($data['category_meta_title'])) {
            $sql .= ", cd2.meta_title AS meta_title";
        }

        if (isset($data['category_meta_description']) && !empty($data['category_meta_description'])) {
            $sql .= ", cd2.meta_description AS meta_description";
        }

        if (isset($data['category_meta_keywords']) && !empty($data['category_meta_keywords'])) {
            $sql .= ", cd2.meta_keyword AS meta_keyword";
        }

        if (isset($data['category_parent']) && !empty($data['category_parent'])) {
            $sql .= ", c1.parent_id AS parent_id";
        }

        $sql .= " FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c1.category_id = '" . (int)$category_id . "'";

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function getCategoriesList() {
        $sql = "SELECT cp.category_id AS category_id, cd2.name AS name, c1.parent_id, c1.sort_order FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        if (!empty($data['filter_name'])) {
            $sql .= " AND cd2.name LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
        }

        $sql .= " GROUP BY cp.category_id";

        $sort_data = array(
            'name',
            'sort_order'
        );

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY sort_order";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProductsWithCategories($data = array()) {
        $sql = "SELECT p.product_id, pd.name FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY pd.name ASC";

        $query = $this->db->query($sql);

        return $query->rows;
    }

    protected function getStoreIdsForCategories() {
        $sql =  "SELECT category_id, store_id FROM `".DB_PREFIX."category_to_store` cs;";
        $store_ids = array();
        $result = $this->db->query( $sql );
        foreach ($result->rows as $row) {
            $categoryId = $row['category_id'];
            $store_id = $row['store_id'];
            if (!isset($store_ids[$categoryId])) {
                $store_ids[$categoryId] = array();
            }
            if (!in_array($store_id,$store_ids[$categoryId])) {
                $store_ids[$categoryId][] = $store_id;
            }
        }
        return $store_ids;
    }


    protected function getLayoutsForCategories() {
        $sql  = "SELECT cl.*, l.name FROM `".DB_PREFIX."category_to_layout` cl ";
        $sql .= "LEFT JOIN `".DB_PREFIX."layout` l ON cl.layout_id = l.layout_id ";
        $sql .= "ORDER BY cl.category_id, cl.store_id;";
        $result = $this->db->query( $sql );
        $layouts = array();
        foreach ($result->rows as $row) {
            $categoryId = $row['category_id'];
            $store_id = $row['store_id'];
            $name = $row['name'];
            if (!isset($layouts[$categoryId])) {
                $layouts[$categoryId] = array();
            }
            $layouts[$categoryId][$store_id] = $name;
        }
        return $layouts;
    }

    protected function getCategories( &$languages, $exist_meta_title, $exist_seo_url_table, $offset=null, $rows=null, $objects_ids=null ) {
        if ($exist_seo_url_table) {
            $sql  = "SELECT c.* FROM `".DB_PREFIX."category` c ";
        } else {
            $sql  = "SELECT c.*, su.keyword FROM `".DB_PREFIX."category` c ";
            $sql .= "LEFT JOIN `".DB_PREFIX."seo_url` su ON su.query=CONCAT('category_id=',c.category_id) ";
        }
        if (isset($objects_ids)) {
            $sql .= "WHERE c.`category_id` IN (" . implode(',', array_map('intval', $objects_ids)) . ") ";
        }
        $sql .= "GROUP BY c.`category_id` ";
        $sql .= "ORDER BY c.`category_id` ASC ";
        if (isset($offset) && isset($rows)) {
            $sql .= "LIMIT $offset,$rows; ";
        } else {
            $sql .= "; ";
        }
        $results = $this->db->query( $sql );

        $category_descriptions = $this->getCategoryDescriptions( $languages, $offset, $rows, $objects_ids );
        foreach ($languages as $language) {
            $language_code = $language['code'];
            foreach ($results->rows as $key=>$row) {
                if (isset($category_descriptions[$language_code][$key])) {
                    $results->rows[$key]['name'][$language_code] = $category_descriptions[$language_code][$key]['name'];
                    $results->rows[$key]['description'][$language_code] = $category_descriptions[$language_code][$key]['description'];
                    if ($exist_meta_title) {
                        $results->rows[$key]['meta_title'][$language_code] = $category_descriptions[$language_code][$key]['meta_title'];
                    }
                    $results->rows[$key]['meta_description'][$language_code] = $category_descriptions[$language_code][$key]['meta_description'];
                    $results->rows[$key]['meta_keyword'][$language_code] = $category_descriptions[$language_code][$key]['meta_keyword'];
                } else {
                    $results->rows[$key]['name'][$language_code] = '';
                    $results->rows[$key]['description'][$language_code] = '';
                    if ($exist_meta_title) {
                        $results->rows[$key]['meta_title'][$language_code] = '';
                    }
                    $results->rows[$key]['meta_description'][$language_code] = '';
                    $results->rows[$key]['meta_keyword'][$language_code] = '';
                }
            }
        }
        return $results->rows;
    }

    protected function getCategoryDescriptions( &$languages, $offset=null, $rows=null, $objects_ids=null ) {
        // query the category_description table for each language
        $category_descriptions = array();
        foreach ($languages as $language) {
            $language_id = $language['language_id'];
            $language_code = $language['code'];
            $sql  = "SELECT c.category_id, cd.* ";
            $sql .= "FROM `".DB_PREFIX."category` c ";
            $sql .= "LEFT JOIN `".DB_PREFIX."category_description` cd ON cd.category_id=c.category_id AND cd.language_id='".(int)$language_id."' ";
            if (isset($objects_ids)) {
                $sql .= "WHERE c.`category_id` IN (" . implode(',', array_map('intval', $objects_ids)) . ") ";
            }
            $sql .= "GROUP BY c.`category_id` ";
            $sql .= "ORDER BY c.`category_id` ASC ";
            if (isset($offset) && isset($rows)) {
                $sql .= "LIMIT $offset,$rows; ";
            } else {
                $sql .= "; ";
            }

            $query = $this->db->query( $sql );
            $category_descriptions[$language_code] = $query->rows;
        }
        return $category_descriptions;
    }

    protected function populateCategoriesWorksheet( &$worksheet, &$languages, &$box_format, &$text_format, $offset=null, $rows=null, &$objects_ids=null, $options = array() ) {
        // Opencart versions from 2.0 onwards also have category_description.meta_title
        $sql = "SHOW COLUMNS FROM `".DB_PREFIX."category_description` LIKE 'meta_title'";
        $query = $this->db->query( $sql );
        $exist_meta_title = ($query->num_rows > 0) ? true : false;

        // Opencart versions from 3.0 onwards use the seo_url DB table
        $exist_seo_url_table = $this->use_table_seo_url;

        // Set the column widths
        $j = 0;
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('category_id')+1);

        if (isset($options['category_parent']) && !empty($options['category_parent'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('parent_id') + 1);
        }

        if (isset($options['category_name']) && !empty($options['category_name'])) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('name') + 4, 30) + 1);
            }
        }

        if (isset($options['category_description']) && !empty($options['category_description'])) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('description'), 32) + 1);
            }
        }

        if (isset($options['category_meta_title']) && !empty($options['category_meta_title'])) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_title'),20)+1);
            }
        }

        if (isset($options['category_meta_description']) && !empty($options['category_meta_description'])) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_description'), 32) + 1);
            }
        }

        if (isset($options['category_meta_keywords']) && !empty($options['category_meta_keywords'])) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_keywords'), 32) + 1);
            }
        }

        // The heading row and column styles
        $styles = array();
        $data = array();
        $i = 1;
        $j = 0;
        $data[$j++] = 'category_id';
        
        if (isset($options['category_parent']) && !empty($options['category_parent'])) {
            $data[$j++] = 'parent_id';
        }
        
        if (isset($options['category_name']) && !empty($options['category_name'])) {
            foreach ($languages as $language) {
                $styles[$j] = &$text_format;
                $data[$j++] = 'name(' . $language['code'] . ')';
            }
        }

        if (isset($options['category_description']) && !empty($options['category_description'])) {
            foreach ($languages as $language) {
                $styles[$j] = &$text_format;
                $data[$j++] = 'description(' . $language['code'] . ')';
            }
        }
        
        if (isset($options['category_meta_title']) && !empty($options['category_meta_title'])) {
            foreach ($languages as $language) {
                $styles[$j] = &$text_format;
                $data[$j++] = 'meta_title('.$language['code'].')';
            }
        }

        if (isset($options['category_meta_description']) && !empty($options['category_meta_description'])) {
            foreach ($languages as $language) {
                $styles[$j] = &$text_format;
                $data[$j++] = 'meta_description(' . $language['code'] . ')';
            }
        }

        if (isset($options['category_meta_keywords']) && !empty($options['category_meta_keywords'])) {
            foreach ($languages as $language) {
                $styles[$j] = &$text_format;
                $data[$j++] = 'meta_keywords(' . $language['code'] . ')';
            }
        }
        
        $worksheet->getRowDimension($i)->setRowHeight(30);
        $this->setCellRow( $worksheet, $i, $data, $box_format );

        // The actual categories data
        $i += 1;
        $j = 0;
        $categories = $this->getCategories( $languages, $exist_meta_title, $exist_seo_url_table, $offset, $rows, $objects_ids );

        foreach ($categories as $row) {
            $worksheet->getRowDimension($i)->setRowHeight(26);
            $data = array();
            $data[$j++] = $row['category_id'];

            if (isset($options['category_parent']) && !empty($options['category_parent'])) {
                $data[$j++] = $row['parent_id'];
            }

            if (isset($options['category_name']) && !empty($options['category_name'])) {
                foreach ($languages as $language) {
                    $data[$j++] = html_entity_decode($row['name'][$language['code']], ENT_QUOTES, 'UTF-8');
                }
            }

            if (isset($options['category_description']) && !empty($options['category_description'])) {
                foreach ($languages as $language) {
                    $data[$j++] = html_entity_decode($row['description'][$language['code']], ENT_QUOTES, 'UTF-8');
                }
            }

            if (isset($options['category_meta_title']) && !empty($options['category_meta_title'])) {
                foreach ($languages as $language) {
                    $data[$j++] = html_entity_decode($row['meta_title'][$language['code']],ENT_QUOTES,'UTF-8');
                }
            }

            if (isset($options['category_meta_description']) && !empty($options['category_meta_description'])) {
                foreach ($languages as $language) {
                    $data[$j++] = html_entity_decode($row['meta_description'][$language['code']], ENT_QUOTES, 'UTF-8');
                }
            }

            if (isset($options['category_meta_keywords']) && !empty($options['category_meta_keywords'])) {
                foreach ($languages as $language) {
                    $data[$j++] = html_entity_decode($row['meta_keyword'][$language['code']], ENT_QUOTES, 'UTF-8');
                }
            }

            $this->setCellRow( $worksheet, $i, $data, $this->null_array, $styles );
            $i += 1;
            $j = 0;
        }
    }

    protected function getStoreIdsForProducts() {
        $sql =  "SELECT product_id, store_id FROM `".DB_PREFIX."product_to_store` ps;";
        $store_ids = array();
        $result = $this->db->query( $sql );
        foreach ($result->rows as $row) {
            $productId = $row['product_id'];
            $store_id = $row['store_id'];
            if (!isset($store_ids[$productId])) {
                $store_ids[$productId] = array();
            }
            if (!in_array($store_id,$store_ids[$productId])) {
                $store_ids[$productId][] = $store_id;
            }
        }
        return $store_ids;
    }


    protected function getLayoutsForProducts() {
        $sql  = "SELECT pl.*, l.name FROM `".DB_PREFIX."product_to_layout` pl ";
        $sql .= "LEFT JOIN `".DB_PREFIX."layout` l ON pl.layout_id = l.layout_id ";
        $sql .= "ORDER BY pl.product_id, pl.store_id;";
        $result = $this->db->query( $sql );
        $layouts = array();
        foreach ($result->rows as $row) {
            $productId = $row['product_id'];
            $store_id = $row['store_id'];
            $name = $row['name'];
            if (!isset($layouts[$productId])) {
                $layouts[$productId] = array();
            }
            $layouts[$productId][$store_id] = $name;
        }
        return $layouts;
    }


    protected function getProductDescriptions( &$languages, $offset=null, $rows=null, $objects_ids=null ) {
        // some older versions of OpenCart use the 'product_tag' table
        $exist_table_product_tag = false;
        $query = $this->db->query( "SHOW TABLES LIKE '".DB_PREFIX."product_tag'" );
        $exist_table_product_tag = ($query->num_rows > 0);

        // query the product_description table for each language
        $product_descriptions = array();
        foreach ($languages as $language) {
            $language_id = $language['language_id'];
            $language_code = $language['code'];
            $sql  = "SELECT p.product_id, ".(($exist_table_product_tag) ? "GROUP_CONCAT(pt.tag SEPARATOR \",\") AS tag, " : "")."pd.* ";
            $sql .= "FROM `".DB_PREFIX."product` p ";
            $sql .= "LEFT JOIN `".DB_PREFIX."product_description` pd ON pd.product_id=p.product_id AND pd.language_id='".(int)$language_id."' ";
            if ($exist_table_product_tag) {
                $sql .= "LEFT JOIN `".DB_PREFIX."product_tag` pt ON pt.product_id=p.product_id AND pt.language_id='".(int)$language_id."' ";
            }
            if ($this->posted_categories) {
                $sql .= "LEFT JOIN `".DB_PREFIX."product_to_category` pc ON pc.product_id=p.product_id ";
            }
            if (isset($objects_ids)) {
                $sql .= "WHERE p.`product_id` IN (" . implode(',', array_map('intval', $objects_ids)) . ") ";
            }
            $sql .= "GROUP BY p.product_id ";
            $sql .= "ORDER BY p.product_id ";
            if (isset($offset) && isset($rows)) {
                $sql .= "LIMIT $offset,$rows; ";
            } else {
                $sql .= "; ";
            }
            $query = $this->db->query( $sql );
            $product_descriptions[$language_code] = $query->rows;
        }
        return $product_descriptions;
    }


    protected function getProducts( &$languages, $default_language_id, $product_fields, $exist_meta_title, $exist_seo_url_table, $offset=null, $rows=null, $objects_ids = null, $options = array() ) {
        $sql  = "SELECT p.product_id";

        if (isset($options['product_categories']) && !empty($options['product_categories'])) {
            $sql .= ", GROUP_CONCAT( DISTINCT CAST(pc.category_id AS CHAR(11)) SEPARATOR \",\" ) AS categories";
        }

        if (isset($options['product_sku']) && !empty($options['product_sku'])) {
            $sql .= ", p.sku";
        }

        if (isset($options['product_location']) && !empty($options['product_location'])) {
            $sql .= ", p.location";
        }

        if (isset($options['product_quantity']) && !empty($options['product_quantity'])) {
            $sql .= ", p.quantity";
        }

        if (isset($options['product_model']) && !empty($options['product_model'])) {
            $sql .= ", p.model";
        }

        if (isset($options['product_manufacturer']) && !empty($options['product_manufacturer'])) {
            $sql .= ", m.name AS manufacturer";
        }

        if (isset($options['product_shipping']) && !empty($options['product_shipping'])) {
            $sql .= ", p.shipping";
        }

        if (isset($options['product_price']) && !empty($options['product_price'])) {
            $sql .= ", p.price";
        }

        if (isset($options['product_reward']) && !empty($options['product_reward'])) {
            $sql .= ", p.points";
        }

        if (isset($options['product_date_available']) && !empty($options['product_date_available'])) {
            $sql .= ", p.date_available";
        }

        if (isset($options['product_weight']) && !empty($options['product_weight'])) {
            $sql .= ", p.weight";
        }

        if (isset($options['product_weight_class']) && !empty($options['product_weight_class'])) {
            $sql .= ", wc.unit AS weight_unit";
        }

        if (isset($options['product_length']) && !empty($options['product_length'])) {
            $sql .= ", p.length,";
            $sql .= " p.width,";
            $sql .= " p.height";
        }

        if (isset($options['product_status']) && !empty($options['product_status'])) {
            $sql .= ", p.status";
        }

        if (isset($options['product_sort_order']) && !empty($options['product_sort_order'])) {
            $sql .= ", p.sort_order";
        }

        if (isset($options['product_stock_status']) && !empty($options['product_stock_status'])) {
            $sql .= ", p.stock_status_id";
        }

        if (isset($options['product_length_class']) && !empty($options['product_length_class'])) {
            $sql .= ", mc.unit AS length_unit";
        }

        if (isset($options['product_minimum']) && !empty($options['product_minimum'])) {
            $sql .= ", p.minimum";
        }

        $sql .= " FROM `".DB_PREFIX."product` p ";

        if (isset($options['product_categories']) && !empty($options['product_categories'])) {
            $sql .= "LEFT JOIN `" . DB_PREFIX . "product_to_category` pc ON p.product_id=pc.product_id ";
        }

        if (isset($options['product_manufacturer']) && !empty($options['product_manufacturer'])) {
            $sql .= "LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON m.manufacturer_id = p.manufacturer_id ";
        }

        if (isset($options['product_weight_class']) && !empty($options['product_weight_class'])) {
            $sql .= "LEFT JOIN `" . DB_PREFIX . "weight_class_description` wc ON wc.weight_class_id = p.weight_class_id ";
            $sql .= "  AND wc.language_id=$default_language_id ";
        }

        if (isset($options['product_length_class']) && !empty($options['product_length_class'])) {
            $sql .= "LEFT JOIN `" . DB_PREFIX . "length_class_description` mc ON mc.length_class_id=p.length_class_id ";
            $sql .= "  AND mc.language_id=$default_language_id ";
        }

        if (isset($objects_ids)) {
            $sql .= "WHERE p.`product_id` IN (" . implode(',', array_map('intval', $objects_ids)) . ") ";
        }
        $sql .= "GROUP BY p.product_id ";
        $sql .= "ORDER BY p.product_id ";
        if (isset($offset) && isset($rows)) {
            $sql .= "LIMIT $offset,$rows; ";
        } else {
            $sql .= "; ";
        }

        $results = $this->db->query( $sql );

        $product_descriptions = $this->getProductDescriptions( $languages, $offset, $rows, $objects_ids );
        foreach ($languages as $language) {
            $language_code = $language['code'];
            foreach ($results->rows as $key=>$row) {
                if (isset($product_descriptions[$language_code][$key])) {
                    $results->rows[$key]['name'][$language_code] = $product_descriptions[$language_code][$key]['name'];
                    $results->rows[$key]['description'][$language_code] = $product_descriptions[$language_code][$key]['description'];
                    if ($exist_meta_title) {
                        $results->rows[$key]['meta_title'][$language_code] = $product_descriptions[$language_code][$key]['meta_title'];
                    }
                    $results->rows[$key]['meta_description'][$language_code] = $product_descriptions[$language_code][$key]['meta_description'];
                    $results->rows[$key]['meta_keyword'][$language_code] = $product_descriptions[$language_code][$key]['meta_keyword'];
                    $results->rows[$key]['tag'][$language_code] = $product_descriptions[$language_code][$key]['tag'];
                } else {
                    $results->rows[$key]['name'][$language_code] = '';
                    $results->rows[$key]['description'][$language_code] = '';
                    if ($exist_meta_title) {
                        $results->rows[$key]['meta_title'][$language_code] = '';
                    }
                    $results->rows[$key]['meta_description'][$language_code] = '';
                    $results->rows[$key]['meta_keyword'][$language_code] = '';
                    $results->rows[$key]['tag'][$language_code] = '';
                }
            }
        }
        return $results->rows;
    }

    protected function getProductCategories($categories_ids, $default_language_id) {
        $results = array();
        $categories = explode(',', $categories_ids);
        foreach ($categories as $category_id) {
            $query = $this->db->query("SELECT GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR '&gt;') AS name FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c1.`category_id` = '" . (int)$category_id . "'");
            $results[] = $query->row['name'];
        }

        return $results;
    }

    protected function populateProductsWorksheet( &$worksheet, &$languages, $default_language_id, &$price_format, &$box_format, &$weight_format, &$text_format, $offset=null, $rows=null, &$objects_ids = null, &$options = array()) {
        // get list of the field names, some are only available for certain OpenCart versions
        $query = $this->db->query( "DESCRIBE `".DB_PREFIX."product`" );
        $product_fields = array();
        foreach ($query->rows as $row) {
            $product_fields[] = $row['Field'];
        }

        // Opencart versions from 2.0 onwards also have product_description.meta_title
        $sql = "SHOW COLUMNS FROM `".DB_PREFIX."product_description` LIKE 'meta_title'";
        $query = $this->db->query( $sql );
        $exist_meta_title = ($query->num_rows > 0) ? true : false;

        // Opencart versions from 3.0 onwards use the seo_url DB table
        $exist_seo_url_table = true;

        // Set the column widths
        $j = 0;
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('product_id'),4)+1);

        if (isset($options['product_name']) && !empty($options['product_name'])) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('name') + 4, 30) + 1);
            }
        }

        if (isset($options['product_description']) && !empty($options['product_description'])) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('description') + 4, 32) + 1);
            }
        }

        if (isset($options['product_meta_title']) && !empty($options['product_meta_title'])) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_title')+4,20)+1);
            }
        }

        if (isset($options['product_meta_description']) && !empty($options['product_meta_description'])) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_description') + 4, 32) + 1);
            }
        }

        if (isset($options['product_meta_keywords']) && !empty($options['product_meta_keywords'])) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_keywords') + 4, 32) + 1);
            }
        }

        if (isset($options['product_tags']) && !empty($options['product_tags'])) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('tags') + 4, 32) + 1);
            }
        }

        if (isset($options['product_model']) && !empty($options['product_model'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('model'), 12) + 1);
        }

        if (isset($options['product_sku']) && !empty($options['product_sku'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('sku'), 10) + 1);
        }

        if (isset($options['product_location']) && !empty($options['product_location'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('location'), 10) + 1);
        }

        if (isset($options['product_price']) && !empty($options['product_price'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('price'), 10) + 1);
        }

        if (isset($options['product_quantity']) && !empty($options['product_quantity'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('quantity'), 4) + 1);
        }

        if (isset($options['product_minimum']) && !empty($options['product_minimum'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('minimum'), 8) + 1);
        }

        if (isset($options['product_stock_status']) && !empty($options['product_stock_status'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('stock_status_id'), 3) + 1);
        }

        if (isset($options['product_shipping']) && !empty($options['product_shipping'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('shipping'), 5) + 1);
        }

        if (isset($options['product_date_available']) && !empty($options['product_date_available'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_available'), 10) + 1);
        }

        if (isset($options['product_length']) && !empty($options['product_length'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('length'), 8) + 1);
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('width'),8)+1);
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('height'),8)+1);
        }

        if (isset($options['product_length_class']) && !empty($options['product_length_class'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('length_unit'), 3) + 1);
        }

        if (isset($options['product_weight']) && !empty($options['product_weight'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('weight'), 6) + 1);
        }

        if (isset($options['product_weight_class']) && !empty($options['product_weight_class'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('weight_unit'), 3) + 1);
        }

        if (isset($options['product_status']) && !empty($options['product_status'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('status'), 5) + 1);
        }

        if (isset($options['product_sort_order']) && !empty($options['product_sort_order'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('sort_order'), 8) + 1);
        }

        if (isset($options['product_reward']) && !empty($options['product_reward'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('points'), 5) + 1);
        }

        if (isset($options['product_manufacturer']) && !empty($options['product_manufacturer'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('manufacturer'), 15) + 1);
        }

        if (isset($options['product_categories']) && !empty($options['product_categories'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('categories'), 30) + 1);
        }

        // The product headings row and column styles
        $styles = array();
        $data = array();
        $i = 1;
        $j = 0;
        $data[$j++] = 'product_id';

        if (isset($options['product_name']) && !empty($options['product_name'])) {
            foreach ($languages as $language) {
                $styles[$j] = &$text_format;
                $data[$j++] = 'name(' . $language['code'] . ')';
            }
        }

        if (isset($options['product_description']) && !empty($options['product_description'])) {
            foreach ($languages as $language) {
                $styles[$j] = &$text_format;
                $data[$j++] = 'description(' . $language['code'] . ')';
            }
        }

        if (isset($options['product_meta_title']) && !empty($options['product_meta_title'])) {
            foreach ($languages as $language) {
                $styles[$j] = &$text_format;
                $data[$j++] = 'meta_title('.$language['code'].')';
            }
        }

        if (isset($options['product_meta_description']) && !empty($options['product_meta_description'])) {
            foreach ($languages as $language) {
                $styles[$j] = &$text_format;
                $data[$j++] = 'meta_description(' . $language['code'] . ')';
            }
        }

        if (isset($options['product_meta_keywords']) && !empty($options['product_meta_keywords'])) {
            foreach ($languages as $language) {
                $styles[$j] = &$text_format;
                $data[$j++] = 'meta_keywords(' . $language['code'] . ')';
            }
        }

        if (isset($options['product_tags']) && !empty($options['product_tags'])) {
            foreach ($languages as $language) {
                $styles[$j] = &$text_format;
                $data[$j++] = 'tags(' . $language['code'] . ')';
            }
        }

        if (isset($options['product_model']) && !empty($options['product_model'])) {
            $styles[$j] = &$text_format;
            $data[$j++] = 'model';
        }

        if (isset($options['product_sku']) && !empty($options['product_sku'])) {
            $styles[$j] = &$text_format;
            $data[$j++] = 'sku';
        }

        if (isset($options['product_location']) && !empty($options['product_location'])) {
            $styles[$j] = &$text_format;
            $data[$j++] = 'location';
        }

        if (isset($options['product_price']) && !empty($options['product_price'])) {
            $styles[$j] = &$price_format;
            $data[$j++] = 'price';
        }

        if (isset($options['product_quantity']) && !empty($options['product_quantity'])) {
            $data[$j++] = 'quantity';
        }

        if (isset($options['product_minimum']) && !empty($options['product_minimum'])) {
            $data[$j++] = 'minimum';
        }

        if (isset($options['product_stock_status']) && !empty($options['product_stock_status'])) {
            $data[$j++] = 'stock_status_id';
        }

        if (isset($options['product_shipping']) && !empty($options['product_shipping'])) {
            $data[$j++] = 'shipping';
        }

        if (isset($options['product_date_available']) && !empty($options['product_date_available'])) {
            $data[$j++] = 'date_available';
        }

        if (isset($options['product_length']) && !empty($options['product_length'])) {
            $data[$j++] = 'length';
            $data[$j++] = 'width';
            $data[$j++] = 'height';
        }

        if (isset($options['product_length_class']) && !empty($options['product_length_class'])) {
            $data[$j++] = 'length_unit';
        }

        if (isset($options['product_weight']) && !empty($options['product_weight'])) {
            $styles[$j] = &$weight_format;
            $data[$j++] = 'weight';
        }

        if (isset($options['product_weight_class']) && !empty($options['product_weight_class'])) {
            $data[$j++] = 'weight_unit';
        }

        if (isset($options['product_status']) && !empty($options['product_status'])) {
            $data[$j++] = 'status';
        }

        if (isset($options['product_sort_order']) && !empty($options['product_sort_order'])) {
            $data[$j++] = 'sort_order';
        }

        if (isset($options['product_reward']) && !empty($options['product_reward'])) {
            $data[$j++] = 'points';
        }

        if (isset($options['product_manufacturer']) && !empty($options['product_manufacturer'])) {
            $styles[$j] = &$text_format;
            $data[$j++] = 'manufacturer';
        }

        if (isset($options['product_categories']) && !empty($options['product_categories'])) {
            $styles[$j] = &$text_format;
            $data[$j++] = 'categories';
        }

        $worksheet->getRowDimension($i)->setRowHeight(30);
        $this->setCellRow( $worksheet, $i, $data, $box_format );

        // The actual products data
        $i += 1;
        $j = 0;

        $products = $this->getProducts( $languages, $default_language_id, $product_fields, $exist_meta_title, $exist_seo_url_table, $offset, $rows, $objects_ids, $options );

        foreach ($products as $row) {
            $data = array();
            $worksheet->getRowDimension($i)->setRowHeight(26);
            $product_id = $row['product_id'];
            $data[$j++] = $product_id;

            if (isset($options['product_name']) && !empty($options['product_name'])) {
                foreach ($languages as $language) {
                    $data[$j++] = html_entity_decode($row['name'][$language['code']], ENT_QUOTES, 'UTF-8');
                }
            }

            if (isset($options['product_description']) && !empty($options['product_description'])) {
                foreach ($languages as $language) {
                    $data[$j++] = html_entity_decode($row['description'][$language['code']], ENT_QUOTES, 'UTF-8');
                }
            }

            if (isset($options['product_meta_title']) && !empty($options['product_meta_title'])) {
                foreach ($languages as $language) {
                    $data[$j++] = html_entity_decode($row['meta_title'][$language['code']],ENT_QUOTES,'UTF-8');
                }
            }

            if (isset($options['product_meta_description']) && !empty($options['product_meta_description'])) {
                foreach ($languages as $language) {
                    $data[$j++] = html_entity_decode($row['meta_description'][$language['code']], ENT_QUOTES, 'UTF-8');
                }
            }

            if (isset($options['product_meta_keywords']) && !empty($options['product_meta_keywords'])) {
                foreach ($languages as $language) {
                    $data[$j++] = html_entity_decode($row['meta_keyword'][$language['code']], ENT_QUOTES, 'UTF-8');
                }
            }

            if (isset($options['product_tags']) && !empty($options['product_tags'])) {
                foreach ($languages as $language) {
                    $data[$j++] = html_entity_decode($row['tag'][$language['code']], ENT_QUOTES, 'UTF-8');
                }
            }

            if (isset($options['product_model']) && !empty($options['product_model'])) {
                $data[$j++] = $row['model'];
            }

            if (isset($options['product_sku']) && !empty($options['product_sku'])) {
                $data[$j++] = $row['sku'];
            }

            if (isset($options['product_location']) && !empty($options['product_location'])) {
                $data[$j++] = $row['location'];
            }

            if (isset($options['product_price']) && !empty($options['product_price'])) {
                $data[$j++] = $row['price'];
            }

            if (isset($options['product_quantity']) && !empty($options['product_quantity'])) {
                $data[$j++] = $row['quantity'];
            }

            if (isset($options['product_minimum']) && !empty($options['product_minimum'])) {
                $data[$j++] = $row['minimum'];
            }

            if (isset($options['product_stock_status']) && !empty($options['product_stock_status'])) {
                $data[$j++] = $row['stock_status_id'];
            }

            if (isset($options['product_shipping']) && !empty($options['product_shipping'])) {
                $data[$j++] = ($row['shipping'] == 0) ? '0' : '1';
            }

            if (isset($options['product_date_available']) && !empty($options['product_date_available'])) {
                $data[$j++] = $row['date_available'];
            }

            if (isset($options['product_length']) && !empty($options['product_length'])) {
                $data[$j++] = $row['length'];
                $data[$j++] = $row['width'];
                $data[$j++] = $row['height'];
            }

            if (isset($options['product_length_class']) && !empty($options['product_length_class'])) {
                $data[$j++] = $row['length_unit'];
            }

            if (isset($options['product_weight']) && !empty($options['product_weight'])) {
                $data[$j++] = $row['weight'];
            }

            if (isset($options['product_weight_class']) && !empty($options['product_weight_class'])) {
                $data[$j++] = $row['weight_unit'];
            }

            if (isset($options['product_status']) && !empty($options['product_status'])) {
                $data[$j++] = ($row['status'] == 0) ? '0' : '1';
            }

            if (isset($options['product_sort_order']) && !empty($options['product_sort_order'])) {
                $data[$j++] = $row['sort_order'];
            }

            if (isset($options['product_reward']) && !empty($options['product_reward'])) {
                $data[$j++] = $row['points'];
            }

            if (isset($options['product_manufacturer']) && !empty($options['product_manufacturer'])) {
                $data[$j++] = $row['manufacturer'];
            }

            if (isset($options['product_categories']) && !empty($options['product_categories'])) {
                $categories = $this->getProductCategories($row['categories'], $default_language_id);

                $categories_row = '';

                foreach ($categories as $k => $category) {
                    if ($k < count($categories)) {
                        $categories_row .= html_entity_decode($category) . '/';
                    } else {
                        $categories_row .= html_entity_decode($category);
                    }
                }

                $data[$j++] = $categories_row;
            }

            $this->setCellRow( $worksheet, $i, $data, $this->null_array, $styles );
            $i += 1;
            $j = 0;
        }
    }

    protected function getSpecials( $language_id, $objects_ids=null ) {
        // Newer OC versions use the 'customer_group_description' instead of 'customer_group' table for the 'name' field
        $exist_table_customer_group_description = false;
        $query = $this->db->query( "SHOW TABLES LIKE '".DB_PREFIX."customer_group_description'" );
        $exist_table_customer_group_description = ($query->num_rows > 0);

        // get the product specials
        $sql  = "SELECT DISTINCT ps.*, ";
        $sql .= ($exist_table_customer_group_description) ? "cgd.name " : "cg.name ";
        $sql .= "FROM `".DB_PREFIX."product_special` ps ";
        if ($exist_table_customer_group_description) {
            $sql .= "LEFT JOIN `".DB_PREFIX."customer_group_description` cgd ON cgd.customer_group_id=ps.customer_group_id ";
            $sql .= "  AND cgd.language_id=$language_id ";
        } else {
            $sql .= "LEFT JOIN `".DB_PREFIX."customer_group` cg ON cg.customer_group_id=ps.customer_group_id ";
        }
        if ($this->posted_categories) {
            $sql .= "LEFT JOIN `".DB_PREFIX."product_to_category` pc ON pc.product_id=ps.product_id ";
        }
        if (isset($objects_ids)) {
            $sql .= "WHERE ps.`product_id` IN (" . implode(',', array_map('intval', $objects_ids)) . ") ";
        }
        $sql .= "ORDER BY ps.product_id, name, ps.priority";
        $result = $this->db->query( $sql );
        return $result->rows;
    }


    protected function populateSpecialsWorksheet( &$worksheet, $language_id, &$price_format, &$box_format, &$text_format, $objects_ids=null, $options=array() ) {
        // Set the column widths
        $j = 0;
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('product_id')+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('customer_group')+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('priority')+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('price'),10)+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_start'),19)+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_end'),19)+1);

        // The heading row and column styles
        $styles = array();
        $data = array();
        $i = 1;
        $j = 0;
        $data[$j++] = 'product_id';
        $styles[$j] = &$text_format;
        $data[$j++] = 'customer_group';
        $data[$j++] = 'priority';
        $styles[$j] = &$price_format;
        $data[$j++] = 'price';
        $data[$j++] = 'date_start';
        $data[$j++] = 'date_end';
        $worksheet->getRowDimension($i)->setRowHeight(30);
        $this->setCellRow( $worksheet, $i, $data, $box_format );

        // The actual product specials data
        $i += 1;
        $j = 0;
        $specials = $this->getSpecials( $language_id, $objects_ids );
        foreach ($specials as $row) {
            $worksheet->getRowDimension($i)->setRowHeight(13);
            $data = array();
            $data[$j++] = $row['product_id'];
            $data[$j++] = $row['name'];
            $data[$j++] = $row['priority'];
            $data[$j++] = $row['price'];
            $data[$j++] = $row['date_start'];
            $data[$j++] = $row['date_end'];
            $this->setCellRow( $worksheet, $i, $data, $this->null_array, $styles );
            $i += 1;
            $j = 0;
        }
    }

    protected function getDiscounts( $language_id, $objects_ids=null ) {
        // Newer OC versions use the 'customer_group_description' instead of 'customer_group' table for the 'name' field
        $exist_table_customer_group_description = false;
        $query = $this->db->query( "SHOW TABLES LIKE '".DB_PREFIX."customer_group_description'" );
        $exist_table_customer_group_description = ($query->num_rows > 0);

        // get the product discounts
        $sql  = "SELECT pd.*, ";
        $sql .= ($exist_table_customer_group_description) ? "cgd.name " : "cg.name ";
        $sql .= "FROM `".DB_PREFIX."product_discount` pd ";
        if ($exist_table_customer_group_description) {
            $sql .= "LEFT JOIN `".DB_PREFIX."customer_group_description` cgd ON cgd.customer_group_id=pd.customer_group_id ";
            $sql .= "  AND cgd.language_id=$language_id ";
        } else {
            $sql .= "LEFT JOIN `".DB_PREFIX."customer_group` cg ON cg.customer_group_id=pd.customer_group_id ";
        }
        if ($this->posted_categories) {
            $sql .= "LEFT JOIN `".DB_PREFIX."product_to_category` pc ON pc.product_id=pd.product_id ";
        }
        if (isset($objects_ids)) {
            $sql .= "WHERE pd.`product_id` IN (" . implode(',', array_map('intval', $objects_ids)) . ") ";
        }
        $sql .= "ORDER BY pd.product_id ASC, name ASC, pd.quantity ASC";
        $result = $this->db->query( $sql );
        return $result->rows;
    }


    protected function populateDiscountsWorksheet( &$worksheet, $language_id, &$price_format, &$box_format, &$text_format, $objects_ds=null, $options=array() ) {
        // Set the column widths
        $j = 0;
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('product_id')+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('customer_group')+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('quantity')+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('priority')+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('price'),10)+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_start'),19)+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_end'),19)+1);

        // The heading row and column styles
        $styles = array();
        $data = array();
        $i = 1;
        $j = 0;
        $data[$j++] = 'product_id';
        $styles[$j] = &$text_format;
        $data[$j++] = 'customer_group';
        $data[$j++] = 'quantity';
        $data[$j++] = 'priority';
        $styles[$j] = &$price_format;
        $data[$j++] = 'price';
        $data[$j++] = 'date_start';
        $data[$j++] = 'date_end';
        $worksheet->getRowDimension($i)->setRowHeight(30);
        $this->setCellRow( $worksheet, $i, $data, $box_format );

        // The actual product discounts data
        $i += 1;
        $j = 0;
        $discounts = $this->getDiscounts( $language_id, $objects_ds );
        foreach ($discounts as $row) {
            $worksheet->getRowDimension($i)->setRowHeight(13);
            $data = array();
            $data[$j++] = $row['product_id'];
            $data[$j++] = $row['name'];
            $data[$j++] = $row['quantity'];
            $data[$j++] = $row['priority'];
            $data[$j++] = $row['price'];
            $data[$j++] = $row['date_start'];
            $data[$j++] = $row['date_end'];
            $this->setCellRow( $worksheet, $i, $data, $this->null_array, $styles );
            $i += 1;
            $j = 0;
        }
    }

    protected function getRewards( $language_id, $objects_ids=null ) {
        // Newer OC versions use the 'customer_group_description' instead of 'customer_group' table for the 'name' field
        $exist_table_customer_group_description = false;
        $query = $this->db->query( "SHOW TABLES LIKE '".DB_PREFIX."customer_group_description'" );
        $exist_table_customer_group_description = ($query->num_rows > 0);

        // get the product rewards
        $sql  = "SELECT pr.*, ";
        $sql .= ($exist_table_customer_group_description) ? "cgd.name " : "cg.name ";
        $sql .= "FROM `".DB_PREFIX."product_reward` pr ";
        if ($exist_table_customer_group_description) {
            $sql .= "LEFT JOIN `".DB_PREFIX."customer_group_description` cgd ON cgd.customer_group_id=pr.customer_group_id ";
            $sql .= "  AND cgd.language_id=$language_id ";
        } else {
            $sql .= "LEFT JOIN `".DB_PREFIX."customer_group` cg ON cg.customer_group_id=pr.customer_group_id ";
        }
        if ($this->posted_categories) {
            $sql .= "LEFT JOIN `".DB_PREFIX."product_to_category` pc ON pc.product_id=pr.product_id ";
        }
        if (isset($objects_ids)) {
            $sql .= "WHERE pr.`product_id` IN (" . implode(',', array_map('intval', $objects_ids)) . ") ";
        }
        $sql .= "ORDER BY pr.product_id, name";
        $result = $this->db->query( $sql );
        return $result->rows;
    }


    protected function populateRewardsWorksheet( &$worksheet, $language_id, &$box_format, &$text_format, $object_ids=null, $options=array() ) {
        // Set the column widths
        $j = 0;
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('product_id')+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('customer_group')+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(strlen('points')+1);

        // The heading row and column styles
        $styles = array();
        $data = array();
        $i = 1;
        $j = 0;
        $data[$j++] = 'product_id';
        $styles[$j] = &$text_format;
        $data[$j++] = 'customer_group';
        $data[$j++] = 'points';
        $worksheet->getRowDimension($i)->setRowHeight(30);
        $this->setCellRow( $worksheet, $i, $data, $box_format );

        // The actual product rewards data
        $i += 1;
        $j = 0;
        $rewards = $this->getRewards( $language_id, $object_ids );
        foreach ($rewards as $row) {
            $worksheet->getRowDimension($i)->setRowHeight(13);
            $data = array();
            $data[$j++] = $row['product_id'];
            $data[$j++] = $row['name'];
            $data[$j++] = $row['points'];
            $this->setCellRow( $worksheet, $i, $data, $this->null_array, $styles );
            $i += 1;
            $j = 0;
        }
    }

    protected function setCellRow( $worksheet, $row/*1-based*/, $data, &$default_style=null, &$styles=null ) {
        if (!empty($default_style)) {
            $worksheet->getStyle( "$row:$row" )->applyFromArray( $default_style, false );
        }
        if (!empty($styles)) {
            foreach ($styles as $col=>$style) {
                $worksheet->getStyleByColumnAndRow($col,$row)->applyFromArray($style,false);
            }
        }
        $worksheet->fromArray( $data, null, 'A'.$row, true );
//		foreach ($data as $col=>$val) {
//			$worksheet->setCellValueExplicitByColumnAndRow( $col, $row-1, $val );
//		}
//		foreach ($data as $col=>$val) {
//			$worksheet->setCellValueByColumnAndRow( $col, $row, $val );
//		}
    }

    public function download( $export_type, $offset=null, $rows=null, $objects_ids=null, $options=array() ) {
        // we use our own error handler
        global $registry;
        $registry = $this->registry;
//        set_error_handler('error_handler_for_export_import',E_ALL);
//        register_shutdown_function('fatal_error_shutdown_handler_for_export_import');

        // Use the PHPExcel package from https://github.com/PHPOffice/PHPExcel
        $cwd = getcwd();
        $dir = (strcmp(VERSION,'3.0.0.0')>=0) ? 'library/export_import' : 'PHPExcel';
        chdir( DIR_SYSTEM.$dir );
        require_once( 'Classes/PHPExcel.php' );
        PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_ExportImportValueBinder() );
        chdir( $cwd );

        // find out whether all data is to be downloaded
        $all = !isset($offset) && !isset($rows);

        // Memory Optimization
        if ($this->config->get( 'export_import_settings_use_export_cache' )) {
            $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
            $cacheSettings = array( 'memoryCacheSize'  => '16MB' );
            PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
        }

        try {
            // set appropriate timeout limit
            set_time_limit( 1800 );

            $languages = $this->getLanguages();
            $default_language_id = $this->getDefaultLanguageId();

            // create a new workbook
            $workbook = new PHPExcel();


            // set some default styles
            $workbook->getDefaultStyle()->getFont()->setName('Arial');
            $workbook->getDefaultStyle()->getFont()->setSize(10);
            //$workbook->getDefaultStyle()->getAlignment()->setIndent(0.5);
            $workbook->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $workbook->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $workbook->getDefaultStyle()->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_GENERAL);


            // pre-define some commonly used styles
            $box_format = array(
                'fill' => array(
                    'type'      => PHPExcel_Style_Fill::FILL_SOLID,
                    'color'     => array( 'rgb' => 'F0F0F0')
                ),
                /*
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                    'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'wrap'       => false,
                    'indent'     => 0
                )
                */
            );
            $text_format = array(
                'numberformat' => array(
                    'code' => PHPExcel_Style_NumberFormat::FORMAT_TEXT
                ),
                /*
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                    'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'wrap'       => false,
                    'indent'     => 0
                )
                */
            );
            $price_format = array(
                'numberformat' => array(
                    'code' => '######0.00'
                ),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                    /*
                    'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'wrap'       => false,
                    'indent'     => 0
                    */
                )
            );
            $weight_format = array(
                'numberformat' => array(
                    'code' => '##0.00'
                ),
                'alignment' => array(
                    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                    /*
                    'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                    'wrap'       => false,
                    'indent'     => 0
                    */
                )
            );

            // create the worksheets
            $worksheet_index = 0;
            switch ($export_type) {
                case 'c':
                    // creating the Categories worksheet
                    $workbook->setActiveSheetIndex($worksheet_index++);
                    $worksheet = $workbook->getActiveSheet();
                    $worksheet->setTitle( 'Categories' );
                    $this->populateCategoriesWorksheet( $worksheet, $languages, $box_format, $text_format, $offset, $rows, $objects_ids, $options );
                    $worksheet->freezePaneByColumnAndRow( 1, 2 );
                    break;

                case 'p':
                    // creating the Products worksheet
                    $workbook->setActiveSheetIndex($worksheet_index++);
                    $worksheet = $workbook->getActiveSheet();
                    $worksheet->setTitle( 'Products' );
                    $this->populateProductsWorksheet( $worksheet, $languages, $default_language_id, $price_format, $box_format, $weight_format, $text_format, $offset, $rows, $objects_ids, $options );
                    $worksheet->freezePaneByColumnAndRow( 1, 2 );

                    if (isset($options['product_special']) && !empty($options['product_special'])) {
                        // creating the Specials worksheet
                        $workbook->createSheet();
                        $workbook->setActiveSheetIndex($worksheet_index++);
                        $worksheet = $workbook->getActiveSheet();
                        $worksheet->setTitle('Specials');
                        $this->populateSpecialsWorksheet($worksheet, $default_language_id, $price_format, $box_format, $text_format, $objects_ids, $options);
                        $worksheet->freezePaneByColumnAndRow(1, 2);
                    }

                    if (isset($options['product_discount']) && !empty($options['product_discount'])) {
                        // creating the Discounts worksheet
                        $workbook->createSheet();
                        $workbook->setActiveSheetIndex($worksheet_index++);
                        $worksheet = $workbook->getActiveSheet();
                        $worksheet->setTitle('Discounts');
                        $this->populateDiscountsWorksheet($worksheet, $default_language_id, $price_format, $box_format, $text_format, $objects_ids, $options);
                        $worksheet->freezePaneByColumnAndRow(1, 2);
                    }

                    if (isset($options['product_reward']) && !empty($options['product_reward'])) {
                        // creating the Rewards worksheet
                        $workbook->createSheet();
                        $workbook->setActiveSheetIndex($worksheet_index++);
                        $worksheet = $workbook->getActiveSheet();
                        $worksheet->setTitle('Rewards');
                        $this->populateRewardsWorksheet($worksheet, $default_language_id, $box_format, $text_format, $objects_ids, $options);
                        $worksheet->freezePaneByColumnAndRow(1, 2);
                    }

                    break;

                default:
                    break;
            }

            $workbook->setActiveSheetIndex(0);

            // redirect output to client browser
            $datetime = date('Y-m-d');
            switch ($export_type) {
                case 'c':
                    $filename = 'categories-'.$datetime;
                    if (!$all) {
                        if (isset($offset)) {
                            $filename .= "-offset-$offset";
                        } else if (isset($min_id)) {
                            $filename .= "-start-$min_id";
                        }
                        if (isset($rows)) {
                            $filename .= "-rows-$rows";
                        } else if (isset($max_id)) {
                            $filename .= "-end-$max_id";
                        }
                    }
                    $filename .= '.xlsx';
                    break;
                case 'p':
                    $filename = 'products-'.$datetime;
                    if (!$all) {
                        if (isset($offset)) {
                            $filename .= "-offset-$offset";
                        } else if (isset($min_id)) {
                            $filename .= "-start-$min_id";
                        }
                        if (isset($rows)) {
                            $filename .= "-rows-$rows";
                        } else if (isset($max_id)) {
                            $filename .= "-end-$max_id";
                        }
                    }
                    $filename .= '.xlsx';
                    break;
                case 'u':
                    $filename = 'customers-'.$datetime;
                    if (!$all) {
                        if (isset($offset)) {
                            $filename .= "-offset-$offset";
                        } else if (isset($min_id)) {
                            $filename .= "-start-$min_id";
                        }
                        if (isset($rows)) {
                            $filename .= "-rows-$rows";
                        } else if (isset($max_id)) {
                            $filename .= "-end-$max_id";
                        }
                    }
                    $filename .= '.xlsx';
                    break;
                default:
                    $filename = $datetime.'.xlsx';
                    break;
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'.$filename.'"');
            header('Cache-Control: max-age=0');
            $objWriter = PHPExcel_IOFactory::createWriter($workbook, 'Excel2007');
            $objWriter->setPreCalculateFormulas(false);
            $objWriter->save('php://output');

            // Clear the spreadsheet caches
            $this->clearSpreadsheetCache();
            exit;

        } catch (Exception $e) {
            $errstr = $e->getMessage();
            $errline = $e->getLine();
            $errfile = $e->getFile();
            $errno = $e->getCode();
            $this->session->data['export_import_error'] = array( 'errstr'=>$errstr, 'errno'=>$errno, 'errfile'=>$errfile, 'errline'=>$errline );
            if ($this->config->get('config_error_log')) {
                $this->log->write('PHP ' . get_class($e) . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
            }
            return;
        }
    }

    protected function clearSpreadsheetCache() {
        $files = glob(DIR_CACHE . 'Spreadsheet_Excel_Writer' . '*');

        if ($files) {
            foreach ($files as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                    clearstatcache();
                }
            }
        }
    }

    protected function getLanguages() {
        $query = $this->db->query( "SELECT * FROM `".DB_PREFIX."language` WHERE `status`=1 ORDER BY `code`" );
        return $query->rows;
    }

    protected function getDefaultLanguageId() {
        $code = $this->config->get('config_language');
        $sql = "SELECT language_id FROM `".DB_PREFIX."language` WHERE code = '$code'";
        $result = $this->db->query( $sql );
        $language_id = 1;
        if ($result->rows) {
            foreach ($result->rows as $row) {
                $language_id = $row['language_id'];
                break;
            }
        }
        return $language_id;
    }

    protected function clearCache() {
        $this->cache->delete('*');
    }

    protected function validateWorksheetNames( &$reader ) {
        $allowed_worksheets = array(
            'Categories',
            'CategoryFilters',
            'CategorySEOKeywords',
            'Products',
            'AdditionalImages',
            'Specials',
            'Discounts',
            'Rewards',
            'ProductOptions',
            'ProductOptionValues',
            'ProductAttributes',
            'ProductFilters',
            'ProductSEOKeywords',
            'Options',
            'OptionValues',
            'AttributeGroups',
            'Attributes',
            'FilterGroups',
            'Filters',
            'Customers',
            'Addresses'
        );
        $all_worksheets_ignored = true;
        $worksheets = $reader->getSheetNames();
        foreach ($worksheets as $worksheet) {
            if (in_array($worksheet,$allowed_worksheets)) {
                $all_worksheets_ignored = false;
                break;
            }
        }
        if ($all_worksheets_ignored) {
            return false;
        }
        return true;
    }

    protected function getCell(&$worksheet,$row,$col,$default_val='') {
        $col -= 1; // we use 1-based, PHPExcel uses 0-based column index
        $row += 1; // we use 0-based, PHPExcel uses 1-based row index
        $val = ($worksheet->cellExistsByColumnAndRow($col,$row)) ? $worksheet->getCellByColumnAndRow($col,$row)->getValue() : $default_val;
        if ($val===null) {
            $val = $default_val;
        }
        return $val;
    }

    protected function validateHeading( &$data, &$expected, &$multilingual ) {
        $default_language_code = $this->config->get('config_language');
        $heading = array();
        $k = PHPExcel_Cell::columnIndexFromString( $data->getHighestColumn() );
        $i = 0;

        for ($j=1; $j <= $k; $j+=1) {
            $entry = $this->getCell($data,$i,$j);
            $bracket_start = strripos( $entry, '(', 0 );

            if ($bracket_start === false) {
                if (in_array( $entry, $multilingual )) {
                    return false;
                }
                $heading[] = strtolower($entry);
            } else {
                $name = strtolower(substr( $entry, 0, $bracket_start ));
                if (!in_array( $name, $multilingual )) {
                    return false;
                }
                $bracket_end = strripos( $entry, ')', $bracket_start );
                if ($bracket_end <= $bracket_start) {
                    return false;
                }
                if ($bracket_end+1 != strlen($entry)) {
                    return false;
                }
                $language_code = strtolower(substr( $entry, $bracket_start+1, $bracket_end-$bracket_start-1 ));
                if (count($heading) <= 0) {
                    return false;
                }
                if ($heading[count($heading)-1] != $name) {
                    $heading[] = $name;
                }
            }
        }

        for ($i=0; $i < count($heading); $i+=1) {
            if (!in_array($heading[$i], $expected)) { // $heading[$i] != $expected[$i]
                return false;
            }
        }
        return true;
    }


    protected function validateCategories( &$reader ) {
        $data = $reader->getSheetByName( 'Categories' );
        if ($data==null) {
            return true;
        }

        // Opencart versions from 2.0 onwards also have category_description.meta_title
        $sql = "SHOW COLUMNS FROM `".DB_PREFIX."category_description` LIKE 'meta_title'";
        $query = $this->db->query( $sql );

        $expected_heading = array
            ( "category_id", "parent_id", "name", "description", "meta_title", "meta_description", "meta_keywords" );

        $expected_multilingual = array( "name", "description", "meta_title", "meta_description", "meta_keywords" );

        return $this->validateHeading( $data, $expected_heading, $expected_multilingual );
    }

    protected function validateProducts( &$reader ) {
        $data = $reader->getSheetByName( 'Products' );
        if ($data==null) {
            return true;
        }

        // get list of the field names, some are only available for certain OpenCart versions
        $query = $this->db->query( "DESCRIBE `".DB_PREFIX."product`" );
        $product_fields = array();
        foreach ($query->rows as $row) {
            $product_fields[] = $row['Field'];
        }

        // Opencart versions from 2.0 onwards also have product_description.meta_title
        $sql = "SHOW COLUMNS FROM `".DB_PREFIX."product_description` LIKE 'meta_title'";
        $query = $this->db->query( $sql );

        $expected_heading = array
        ( "product_id", "name", "categories", "main_category", "sku" );

        $expected_heading = array_merge( $expected_heading, array( "location", "quantity", "model", "manufacturer", "shipping", "price", "points", "date_available", "weight", "weight_unit", "length", "width", "height", "length_unit", "status", "description") );

        $expected_heading[] = "meta_title";

        $expected_heading = array_merge( $expected_heading, array( "meta_description", "meta_keywords", "stock_status_id", "tags", "sort_order", "minimum" ) );

        $expected_multilingual = array( "name", "description", "meta_title", "meta_description", "meta_keywords", "tags" );

        return $this->validateHeading( $data, $expected_heading, $expected_multilingual );
    }

    protected function validateProductIdColumns( &$reader ) {
        $data = $reader->getSheetByName( 'Products' );
        if ($data==null) {
            return true;
        }
        $ok = true;

        // only unique numeric product_ids can be used, in ascending order, in worksheet 'Products'
        $previous_product_id = 0;
        $has_missing_product_ids = false;
        $product_ids = array();
        $k = $data->getHighestRow();
        for ($i=1; $i<$k; $i+=1) {
            $product_id = $this->getCell($data,$i,1);
            if ($product_id=="") {
                if (!$has_missing_product_ids) {
                    $msg = str_replace( '%1', 'Products', $this->language->get( 'error_missing_product_id' ) );
                    $this->log->write( $msg );
                    $has_missing_product_ids = true;
                }
                $ok = false;
                continue;
            }
            if (!$this->isInteger($product_id)) {
                $msg = str_replace( '%2', $product_id, str_replace( '%1', 'Products', $this->language->get( 'error_invalid_product_id' ) ) );
                $this->log->write( $msg );
                $ok = false;
                continue;
            }
            if (in_array( $product_id, $product_ids )) {
                $msg = str_replace( '%2', $product_id, str_replace( '%1', 'Products', $this->language->get( 'error_duplicate_product_id' ) ) );
                $this->log->write( $msg );
                $ok = false;
            }
            $product_ids[] = $product_id;
            if ($product_id < $previous_product_id) {
                $msg = str_replace( '%2', $product_id, str_replace( '%1', 'Products', $this->language->get( 'error_wrong_order_product_id' ) ) );
                $this->log->write( $msg );
                $ok = false;
            }
            $previous_product_id = $product_id;
        }

        // make sure product_ids are numeric entries and are also mentioned in worksheet 'Products'
        $worksheets = array( 'Specials', 'Discounts', 'Rewards' );
        foreach ($worksheets as $worksheet) {
            $data = $reader->getSheetByName( $worksheet );
            if ($data==null) {
                continue;
            }
            $previous_product_id = 0;
            $has_missing_product_ids = false;
            $unlisted_product_ids = array();
            $k = $data->getHighestRow();
            for ($i=1; $i<$k; $i+=1) {
                $product_id = $this->getCell($data,$i,1);
                if ($product_id=="") {
                    if (!$has_missing_product_ids) {
                        $msg = str_replace( '%1', $worksheet, $this->language->get( 'error_missing_product_id' ) );
                        $this->log->write( $msg );
                        $has_missing_product_ids = true;
                    }
                    $ok = false;
                    continue;
                }
                if (!$this->isInteger($product_id)) {
                    $msg = str_replace( '%2', $product_id, str_replace( '%1', $worksheet, $this->language->get( 'error_invalid_product_id' ) ) );
                    $this->log->write( $msg );
                    $ok = false;
                    continue;
                }
                if (!in_array( $product_id, $product_ids )) {
                    if (!in_array( $product_id, $unlisted_product_ids )) {
                        $unlisted_product_ids[] = $product_id;
                        $msg = str_replace( '%2', $product_id, str_replace( '%1', $worksheet, $this->language->get( 'error_unlisted_product_id' ) ) );
                        $this->log->write( $msg );
                        $ok = false;
                    }
                }
                if ($product_id < $previous_product_id) {
                    $msg = str_replace( '%2', $product_id, str_replace( '%1', $worksheet, $this->language->get( 'error_wrong_order_product_id' ) ) );
                    $this->log->write( $msg );
                    $ok = false;
                }
                $previous_product_id = $product_id;
            }
        }

        return $ok;
    }

    protected function validateCategoryIdColumns( &$reader ) {
        $data = $reader->getSheetByName( 'Categories' );
        if ($data==null) {
            return true;
        }
        $ok = true;

        // only unique numeric category_ids can be used, in ascending order, in worksheet 'Categories'
        $previous_category_id = 0;
        $has_missing_category_ids = false;
        $category_ids = array();
        $k = $data->getHighestRow();
        for ($i=1; $i<$k; $i+=1) {
            $category_id = $this->getCell($data,$i,1);
            if ($category_id=="") {
                if (!$has_missing_category_ids) {
                    $msg = str_replace( '%1', 'Categories', $this->language->get( 'error_missing_category_id' ) );
                    $this->log->write( $msg );
                    $has_missing_category_ids = true;
                }
                $ok = false;
                continue;
            }
            if (!$this->isInteger($category_id)) {
                $msg = str_replace( '%2', $category_id, str_replace( '%1', 'Categories', $this->language->get( 'error_invalid_category_id' ) ) );
                $this->log->write( $msg );
                $ok = false;
                continue;
            }
            if (in_array( $category_id, $category_ids )) {
                $msg = str_replace( '%2', $category_id, str_replace( '%1', 'Categories', $this->language->get( 'error_duplicate_category_id' ) ) );
                $this->log->write( $msg );
                $ok = false;
            }
            $category_ids[] = $category_id;
            if ($category_id < $previous_category_id) {
                $msg = str_replace( '%2', $category_id, str_replace( '%1', 'Categories', $this->language->get( 'error_wrong_order_category_id' ) ) );
                $this->log->write( $msg );
                $ok = false;
            }
            $previous_category_id = $category_id;
        }

        return $ok;
    }

    protected function validateUpload( &$reader )
    {
        $ok = true;
        $languages = $this->getLanguages();

        // make sure at least one of worksheet names is valid
        if (!$this->validateWorksheetNames( $reader )) {
            $this->log->write( $this->language->get( 'error_worksheets' ) );
            $ok = false;
        }

        // worksheets must have correct heading rows
        if (!$this->validateCategories( $reader )) {
            $this->log->write( $this->language->get('error_categories_header') );
            $ok = false;
        }

        if (!$this->validateProducts( $reader )) {
            $this->log->write( $this->language->get('error_products_header') );
            $ok = false;
        }

//        if (!$this->validateSpecials( $reader )) {
//            $this->log->write( $this->language->get('error_specials_header') );
//            $ok = false;
//        }
//
//        if (!$this->validateDiscounts( $reader )) {
//            $this->log->write( $this->language->get('error_discounts_header') );
//            $ok = false;
//        }
//
//        if (!$this->validateRewards( $reader )) {
//            $this->log->write( $this->language->get('error_rewards_header') );
//            $ok = false;
//        }

        // certain worksheets rely on the existence of other worksheets
        $names = $reader->getSheetNames();
        $exist_categories = false;

        $exist_products = false;

        $exist_specials = false;
        $exist_discounts = false;
        $exist_rewards = false;

        foreach ($names as $name) {
            if ($name == 'Categories') {
                $exist_categories = true;
                continue;
            }

//            if ($name == 'Products') {
//                $exist_products = true;
//                continue;
//            }
//
//            if ($name == 'Specials') {
//                if (!$exist_products) {
//                    // Missing Products worksheet, or Products worksheet not listed before Specials
//                    $this->log->write($this->language->get('error_specials'));
//                    $ok = false;
//                }
//                $exist_specials = true;
//                continue;
//            }
//
//            if ($name == 'Discounts') {
//                if (!$exist_products) {
//                    // Missing Products worksheet, or Products worksheet not listed before Discounts
//                    $this->log->write($this->language->get('error_discounts'));
//                    $ok = false;
//                }
//                $exist_discounts = true;
//                continue;
//            }
//
//            if ($name == 'Rewards') {
//                if (!$exist_products) {
//                    // Missing Products worksheet, or Products worksheet not listed before Rewards
//                    $this->log->write($this->language->get('error_rewards'));
//                    $ok = false;
//                }
//                $exist_rewards = true;
//                continue;
//            }
        }

        if (!$ok) {
            return false;
        }

//        if (!$this->validateProductIdColumns( $reader )) {
//            $ok = false;
//        }

        if (!$this->validateCategoryIdColumns( $reader )) {
            $ok = false;
        }

        return $ok;
    }

    protected function validateIncrementalOnly( &$reader, $incremental ) {
        // certain worksheets can only be imported in incremental mode for the time being
        $ok = true;
        $worksheets = array( 'Customers', 'Addresses' );
        foreach ($worksheets as $worksheet) {
            $data = $reader->getSheetByName( $worksheet );
            if ($data) {
                if (!$incremental) {
                    $msg = $this->language->get( 'error_incremental_only' );
                    $msg = str_replace( '%1', $worksheet, $msg );
                    $this->log->write( $msg );
                    $ok = false;
                }
            }
        }
        return $ok;
    }

    public function upload( $filename, $incremental=false ) {
        // we use our own error handler
        global $registry;
        $registry = $this->registry;
//        set_error_handler('error_handler_for_export_import',E_ALL);
//        register_shutdown_function('fatal_error_shutdown_handler_for_export_import');

        try {
            // we use the PHPExcel package from https://github.com/PHPOffice/PHPExcel
            $cwd = getcwd();
            $dir = version_compare(VERSION,'3.0','>=') ? 'library/export_import' : 'PHPExcel';
            chdir( DIR_SYSTEM.$dir );
            require_once( 'Classes/PHPExcel.php' );
            chdir( $cwd );

            // Memory Optimization
            if ($this->config->get( 'export_import_settings_use_import_cache' )) {
                $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
                $cacheSettings = array( ' memoryCacheSize '  => '16MB'  );
                PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            }

            // parse uploaded spreadsheet file
            $inputFileType = PHPExcel_IOFactory::identify($filename);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objReader->setReadDataOnly(true);
            $reader = $objReader->load($filename);

            // read the various worksheets and load them to the database
//            if (!$this->validateIncrementalOnly( $reader, $incremental )) {
//                return false;
//            }

            if (!$this->validateUpload( $reader )) {
                return false;
            }

            $this->clearCache();
            $available_product_ids = array();
            $available_category_ids = array();
            $available_customer_ids = array();
            $this->uploadCategories( $reader, $incremental, $available_category_ids );

            $this->uploadProducts( $reader, $incremental, $available_product_ids );

//            $this->uploadCategoryFilters( $reader, $incremental, $available_category_ids );
//            $this->uploadCategorySEOKeywords( $reader, $incremental, $available_category_ids );
//            $this->uploadAdditionalImages( $reader, $incremental, $available_product_ids );
            $this->uploadSpecials( $reader, $incremental, $available_product_ids );
            $this->uploadDiscounts( $reader, $incremental, $available_product_ids );
            $this->uploadRewards( $reader, $incremental, $available_product_ids );
//            $this->uploadProductOptions( $reader, $incremental, $available_product_ids );
//            $this->uploadProductOptionValues( $reader, $incremental, $available_product_ids );
//            $this->uploadProductAttributes( $reader, $incremental, $available_product_ids );
//            $this->uploadProductFilters( $reader, $incremental, $available_product_ids );
//            $this->uploadProductSEOKeywords( $reader, $incremental, $available_product_ids );
//            $this->uploadOptions( $reader, $incremental );
//            $this->uploadOptionValues( $reader, $incremental );
//            $this->uploadAttributeGroups( $reader, $incremental );
//            $this->uploadAttributes( $reader, $incremental );
//            $this->uploadFilterGroups( $reader, $incremental );
//            $this->uploadFilters( $reader, $incremental );
//            $this->uploadCustomers( $reader, $incremental, $available_customer_ids );
//            $this->uploadAddresses( $reader, $incremental, $available_customer_ids );
            return true;
        } catch (Exception $e) {
            $errstr = $e->getMessage();
            $errline = $e->getLine();
            $errfile = $e->getFile();
            $errno = $e->getCode();
            $this->session->data['export_import_error'] = array( 'errstr'=>$errstr, 'errno'=>$errno, 'errfile'=>$errfile, 'errline'=>$errline );
            if ($this->config->get('config_error_log')) {
                $this->log->write('PHP ' . get_class($e) . ':  ' . $errstr . ' in ' . $errfile . ' on line ' . $errline);
            }
            return false;
        }
    }

    protected function uploadCategories( &$reader, $incremental, &$available_category_ids=array() ) {
        // get worksheet if there
        $data = $reader->getSheetByName( 'Categories' );
        if ($data==null) {
            return;
        }

        // Opencart versions from 2.0 onwards also have category_description.meta_title
        $sql = "SHOW COLUMNS FROM `".DB_PREFIX."category_description` LIKE 'meta_title'";
        $query = $this->db->query( $sql );
        $exist_meta_title = ($query->num_rows > 0) ? true : false;

        // find the installed languages
        $languages = $this->getLanguages();

        $first_row = array();
        $i = 0;
        $k = $data->getHighestRow();

        for ($i=0; $i<$k; $i+=1) {
            if ($i==0) {
                $max_col = PHPExcel_Cell::columnIndexFromString( $data->getHighestColumn() );
                for ($j=1; $j<=$max_col; $j+=1) {
                    $first_row[] = $this->getCell($data,$i,$j);
                }
                continue;
            }
            $j = 1;
            $category_id = trim($this->getCell($data,$i,$j++));
            if ($category_id=="") {
                continue;
            }
            if($first_row[$j-1] == "parent_id") {
                $parent_id = $this->getCell($data, $i, $j++, '');
            }

            $names = array();
            if (isset($first_row[$j-1]) && $this->startsWith($first_row[$j-1],"name(")) {
                while (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j - 1], "name(")) {
                    $language_code = substr($first_row[$j - 1], strlen("name("), strlen($first_row[$j - 1]) - strlen("name(") - 1);
                    $name = $this->getCell($data, $i, $j++);
                    $name = htmlspecialchars($name);
                    $names[$language_code] = $name;
                }
            }

            $descriptions = array();
            if (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j - 1],"description(")) {
                while (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j - 1], "description(")) {
                    $language_code = substr($first_row[$j - 1], strlen("description("), strlen($first_row[$j - 1]) - strlen("description(") - 1);
                    $description = $this->getCell($data, $i, $j++);
                    $description = htmlspecialchars($description);
                    $descriptions[$language_code] = $description;
                }
            }

            $meta_titles = array();
            if (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j - 1],"meta_title(")) {
                while (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j - 1],"meta_title(")) {
                    $language_code = substr($first_row[$j-1],strlen("meta_title("),strlen($first_row[$j - 1])-strlen("meta_title(")-1);
                    $meta_title = $this->getCell($data,$i,$j++);
                    $meta_title = htmlspecialchars( $meta_title );
                    $meta_titles[$language_code] = $meta_title;
                }
            }

            $meta_descriptions = array();
            if (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j - 1], "meta_description(")) {
                while (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j - 1], "meta_description(")) {
                    $language_code = substr($first_row[$j - 1], strlen("meta_description("), strlen($first_row[$j - 1]) - strlen("meta_description(") - 1);
                    $meta_description = $this->getCell($data, $i, $j++);
                    $meta_description = htmlspecialchars($meta_description);
                    $meta_descriptions[$language_code] = $meta_description;
                }
            }

            $meta_keywords = array();
            if (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j - 1], "meta_keywords(")) {
                while (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j - 1], "meta_keywords(")) {
                    $language_code = substr($first_row[$j - 1], strlen("meta_keywords("), strlen($first_row[$j - 1]) - strlen("meta_keywords(") - 1);
                    $meta_keyword = $this->getCell($data, $i, $j++);
                    $meta_keyword = htmlspecialchars($meta_keyword);
                    $meta_keywords[$language_code] = $meta_keyword;
                }
            }

            $category = array();
            $category['category_id'] = $category_id;
            $category['parent_id'] = isset($parent_id) ? $parent_id : '';
            $category['names'] = $names;
            $category['descriptions'] = $descriptions;
            $category['meta_titles'] = $meta_titles;
            $category['meta_descriptions'] = $meta_descriptions;
            $category['meta_keywords'] = $meta_keywords;

            $available_category_ids[$category_id] = $category_id;
            $this->moreCategoryCells( $i, $j, $data, $category );
            $this->storeCategoryIntoDatabase( $category, $languages, $layout_ids, $available_store_ids, $url_alias_ids );
        }

        // restore category paths for faster lookups on the frontend (only for newer OpenCart versions)
        $this->load->model( 'catalog/category' );
        if (is_callable(array($this->model_catalog_category,'repairCategories'))) {
            $this->model_catalog_category->repairCategories(0);
        }
    }

    protected function storeCategoryIntoDatabase( &$category, &$languages, &$layout_ids, &$available_store_ids, &$url_alias_ids ) {
        // extract the category details
        $category_id = $category['category_id'];

        $names = $category['names'];
        $descriptions = $category['descriptions'];
        $meta_titles = $category['meta_titles'];
        $meta_descriptions = $category['meta_descriptions'];
        $meta_keywords = $category['meta_keywords'];

        $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category WHERE category_id = '" . $category_id . "' LIMIT 1");

        // Category added yet, need update
        if (isset($query->row['category_id'])) {
            if (strlen(trim((string)$category['parent_id']))) {
                $this->db->query("UPDATE " . DB_PREFIX . "category SET parent_id = '" . (int)$category['parent_id'] . "', date_modified = NOW() WHERE category_id = '" . (int)$category_id . "'");
            }

            foreach ($languages as $language) {
                $language_code = $language['code'];
                $language_id = $language['language_id'];
                $name = isset($names[$language_code]) ? $this->db->escape($names[$language_code]) : '';
                $description = isset($descriptions[$language_code]) ? $this->db->escape($descriptions[$language_code]) : '';
                $meta_title = isset($meta_titles[$language_code]) ? $this->db->escape($meta_titles[$language_code]) : '';
                $meta_description = isset($meta_descriptions[$language_code]) ? $this->db->escape($meta_descriptions[$language_code]) : '';
                $meta_keyword = isset($meta_keywords[$language_code]) ? $this->db->escape($meta_keywords[$language_code]) : '';

                $this->db->query("UPDATE " . DB_PREFIX . "category_description SET `name` = '" . $this->db->escape($name) . "', description = '" . $this->db->escape($description) . "', meta_title = '" . $this->db->escape($meta_title) . "', meta_description = '" . $this->db->escape($meta_description) . "', meta_keyword = '" . $this->db->escape($meta_keyword) . "' WHERE category_id = '" . (int)$category_id . "' AND language_id = '" . (int)$language_id . "'");
            }
        } else { // add new category
            $parent_id = !empty($category['parent_id']) ? $category['parent_id'] : 0;

            $this->db->query("INSERT INTO " . DB_PREFIX . "category SET category_id = '" . (int)$category_id . "', parent_id = '" . (int)$parent_id . "', `top` = '" . (int)0 . "', `column` = '" . (int)0 . "', status = '" . (int)1 . "', date_modified = NOW(), date_added = NOW()");

            foreach ($languages as $language) {
                $language_code = $language['code'];
                $language_id = $language['language_id'];
                $name = isset($names[$language_code]) ? $this->db->escape($names[$language_code]) : '';
                $description = isset($descriptions[$language_code]) ? $this->db->escape($descriptions[$language_code]) : '';
                $meta_title = isset($meta_titles[$language_code]) ? $this->db->escape($meta_titles[$language_code]) : '';
                $meta_description = isset($meta_descriptions[$language_code]) ? $this->db->escape($meta_descriptions[$language_code]) : '';
                $meta_keyword = isset($meta_keywords[$language_code]) ? $this->db->escape($meta_keywords[$language_code]) : '';

                $this->db->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$language_id . "', `name` = '" . $this->db->escape($name) . "', description = '" . $this->db->escape($description) . "', meta_title = '" . $this->db->escape($meta_title) . "', meta_description = '" . $this->db->escape($meta_description) . "', meta_keyword = '" . $this->db->escape($meta_keyword) . "'");
            }
        }
    }

    protected function uploadProducts( &$reader, $incremental, &$available_product_ids=array() ) {
        // get worksheet, if not there return immediately
        $data = $reader->getSheetByName( 'Products' );
        if ($data==null) {
            return;
        }

        // some older versions of OpenCart use the 'product_tag' table
        $exist_table_product_tag = false;
        $query = $this->db->query( "SHOW TABLES LIKE '".DB_PREFIX."product_tag'" );
        $exist_table_product_tag = ($query->num_rows > 0);

        // Opencart versions from 2.0 onwards also have product_description.meta_title
        $sql = "SHOW COLUMNS FROM `".DB_PREFIX."product_description` LIKE 'meta_title'";
        $query = $this->db->query( $sql );
        $exist_meta_title = ($query->num_rows > 0) ? true : false;

        // find the installed languages
        $languages = $this->getLanguages();

        // find the default units
        $default_weight_unit = $this->getDefaultWeightUnit();
        $default_length_unit = $this->getDefaultLengthUnit();
        $default_stock_status_id = $this->config->get('config_stock_status_id');

        // get list of the field names, some are only available for certain OpenCart versions
        $query = $this->db->query( "DESCRIBE `".DB_PREFIX."product`" );
        $product_fields = array();
        foreach ($query->rows as $row) {
            $product_fields[] = $row['Field'];
        }

        // load the worksheet cells and store them to the database
        $first_row = array();
        $i = 0;
        $k = $data->getHighestRow();
        for ($i=0; $i<$k; $i+=1) {
            if ($i==0) {
                $max_col = PHPExcel_Cell::columnIndexFromString( $data->getHighestColumn() );
                for ($j=1; $j<=$max_col; $j+=1) {
                    $first_row[] = $this->getCell($data,$i,$j);
                }
                continue;
            }
            $j = 1;
            $product_id = trim($this->getCell($data,$i,$j++));
            if ($product_id=="") {
                continue;
            }
            $names = array();
            while (isset($first_row[$j-1]) && $this->startsWith($first_row[$j-1],"name(")) {
                $language_code = substr($first_row[$j-1],strlen("name("),strlen($first_row[$j-1])-strlen("name(")-1);
                $name = $this->getCell($data,$i,$j++);
                $name = htmlspecialchars( $name );
                $names[$language_code] = $name;
            }
            $descriptions = array();
            while (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j-1],"description(")) {
                $language_code = substr($first_row[$j-1],strlen("description("),strlen($first_row[$j-1])-strlen("description(")-1);
                $description = $this->getCell($data,$i,$j++);
                $description = htmlspecialchars( $description );
                $descriptions[$language_code] = $description;
            }
            $meta_titles = array();
            while (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j-1],"meta_title(")) {
                $language_code = substr($first_row[$j-1],strlen("meta_title("),strlen($first_row[$j-1])-strlen("meta_title(")-1);
                $meta_title = $this->getCell($data,$i,$j++);
                $meta_title = htmlspecialchars( $meta_title );
                $meta_titles[$language_code] = $meta_title;
            }
            $meta_descriptions = array();
            while (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j-1],"meta_description(")) {
                $language_code = substr($first_row[$j-1],strlen("meta_description("),strlen($first_row[$j-1])-strlen("meta_description(")-1);
                $meta_description = $this->getCell($data,$i,$j++);
                $meta_description = htmlspecialchars( $meta_description );
                $meta_descriptions[$language_code] = $meta_description;
            }
            $meta_keywords = array();
            while (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j-1],"meta_keywords(")) {
                $language_code = substr($first_row[$j-1],strlen("meta_keywords("),strlen($first_row[$j-1])-strlen("meta_keywords(")-1);
                $meta_keyword = $this->getCell($data,$i,$j++);
                $meta_keyword = htmlspecialchars( $meta_keyword );
                $meta_keywords[$language_code] = $meta_keyword;
            }
            $tags = array();
            while (isset($first_row[$j - 1]) && $this->startsWith($first_row[$j-1],"tags(")) {
                $language_code = substr($first_row[$j-1],strlen("tags("),strlen($first_row[$j-1])-strlen("tags(")-1);
                $tag = $this->getCell($data,$i,$j++);
                $tag = htmlspecialchars( $tag );
                $tags[$language_code] = $tag;
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "model") {
                $model = $this->getCell($data, $i, $j++, '   ');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "sku") {
                $sku = $this->getCell($data, $i, $j++, '');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "location") {
                $location = $this->getCell($data, $i, $j++, '');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "price") {
                $price = $this->getCell($data, $i, $j++, '0.00');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "quantity") {
                $quantity = $this->getCell($data, $i, $j++, '0');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "minimum") {
                $minimum = $this->getCell($data, $i, $j++, '1');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "stock_status_id") {
                $stock_status_id = $this->getCell($data, $i, $j++, $default_stock_status_id);
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "shipping") {
                $shipping = $this->getCell($data, $i, $j++, '1');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "date_available") {
                $date_available = $this->getCell($data, $i, $j++);
                $date_available = ((is_string($date_available)) && (strlen($date_available) > 0)) ? $date_available : "NOW()";
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "length") {
                $length = $this->getCell($data, $i, $j++, '0');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "width") {
                $width = $this->getCell($data, $i, $j++, '0');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "height") {
                $height = $this->getCell($data, $i, $j++, '0');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "length_unit") {
                $length_unit = $this->getCell($data, $i, $j++, $default_length_unit);
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "weight") {
                $weight = $this->getCell($data, $i, $j++, '0');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "weight_unit") {
                $weight_unit = $this->getCell($data, $i, $j++, $default_weight_unit);
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "status") {
                $status = $this->getCell($data, $i, $j++, '1');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "sort_order") {
                $sort_order = $this->getCell($data, $i, $j++, '0');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "points") {
                $points = $this->getCell($data, $i, $j++, '0');
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "manufacturer") {
                $manufacturer_name = $this->getCell($data, $i, $j++);
            }

            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "categories") {
                $categories = $this->getCell($data, $i, $j++);
            }

            $product = array();
            $product['product_id'] = $product_id;
            $product['names'] = $names;
            if(isset($model)) {
                $product['model'] = $model;
            }
            if(isset($sku)) {
                $product['sku'] = $sku;
            }
            if(isset($location)) {
                $product['location'] = $location;
            }
            if(isset($price)) {
                $product['price'] = $price;
            }
            if(isset($quantity)) {
                $product['quantity'] = $quantity;
            }
            if(isset($minimum)) {
                $product['minimum'] = $minimum;
            }
            if(isset($stock_status_id)) {
                $product['stock_status_id'] = $stock_status_id;
            }
            if(isset($shipping)) {
                $product['shipping'] = $shipping;
            }
            if(isset($points)) {
                $product['points'] = $points;
            }
            if(isset($date_available)) {
                $product['date_available'] = $date_available;
            }
            $product['viewed'] = 0;
            if(isset($length)) {
                $product['length'] = $length;
            }
            if(isset($width)) {
                $product['width'] = $width;
            }
            if(isset($height)) {
                $product['height'] = $height;
            }
            if(isset($length_unit)) {
                $product['length_unit'] = $length_unit;
            }
            if(isset($weight)) {
                $product['weight'] = $weight;
            }
            if(isset($weight_unit)) {
                $product['weight_unit'] = $weight_unit;
            }
            if(isset($status)) {
                $product['status'] = $status;
            }
            if(isset($sort_order)) {
                $product['sort_order'] = $sort_order;
            }
            $product['descriptions'] = $descriptions;
            $product['meta_titles'] = $meta_titles;
            $product['meta_descriptions'] = $meta_descriptions;
            $product['meta_keywords'] = $meta_keywords;
            $product['tags'] = $tags;

            if(isset($manufacturer_name)) {
                $product['manufacturer_name'] = $manufacturer_name;
            }


            if (isset($categories)) {
                $categories = trim($categories);
                $product['categories'] = ($categories == "") ? array() : explode("/", $categories);
                if ($product['categories'] === false) {
                    $product['categories'] = array();
                }
            }

            $available_product_ids[$product_id] = $product_id;
            $this->moreProductCells( $i, $j, $data, $product );
            $this->storeProductIntoDatabase( $product, $languages, $product_fields, $exist_table_product_tag, $exist_meta_title, $layout_ids, $available_store_ids, $manufacturers, $weight_class_ids, $length_class_ids, $url_alias_ids );
        }
    }

    protected function storeProductIntoDatabase( &$product, &$languages, &$product_fields, $exist_table_product_tag, $exist_meta_title, &$layout_ids, &$available_store_ids, &$manufacturers, &$weight_class_ids, &$length_class_ids, &$url_alias_ids )
    {
        // extract the product details
        $product_id = $product['product_id'];
        $names = $product['names'];
        $categories = isset($product['categories']) ? $product['categories'] : array();
        $quantity = isset($product['quantity']) ? $product['quantity'] : '';
        $model = isset($product['model']) ? $product['model'] : '';
        $manufacturer_name = isset($product['manufacturer_name']) ? $product['manufacturer_name'] : '';
        if (isset($product['shipping'])) {
            $shipping = ((strtoupper($product['shipping']) == "YES") || (strtoupper($product['shipping']) == "Y") || (strtoupper($product['shipping']) == "TRUE") || (trim($product['shipping']) == "1")) ? 1 : 0;
        } else {
            $shipping = '';
        }

        $sku = isset($product['sku']) ? $product['sku'] : '';

        $location = isset($product['location']) ? $product['location'] : '';
        $minimum = isset($product['minimum']) ? $product['minimum'] : '';
        $price = isset($product['price']) ? trim($product['price']) : '';
        $points = isset($product['points']) ? $product['points'] : '';
        $date_available = isset($product['date_available']) ? $product['date_available'] : '';
        $weight = isset($product['weight']) ? $product['weight'] : '';
        if (isset($product['weight_unit'])) {
            $weight_class_id = $this->getWeightIdByUnit($product['weight_unit']);
            if (!strlen(trim($weight_class_id))) {
                $weight_class_id = 0;
            }
        }
        if (isset($product['status'])) {
            $status = ((strtoupper($product['status']) == "TRUE") || (strtoupper($product['status']) == "YES") || (strtoupper($product['status']) == "ENABLED") || (trim($product['status']) == "1")) ? 1 : 0;
        }
        $viewed = $product['viewed'];
        $stock_status_id = isset($product['stock_status_id']) ? $product['stock_status_id'] : '';

        $length = isset($product['length']) ? $product['length'] : '';
        $width = isset($product['width']) ? $product['width'] : '';
        $height = isset($product['height']) ? $product['height'] : '';

        if (isset($product['length_unit'])) {
            $length_class_id = $this->getLengthIdByUnit($product['length_unit']);
            if (!strlen(trim($length_class_id))) {
                $length_class_id = 0;
            }
        }

        $descriptions = $product['descriptions'];
        $meta_titles = $product['meta_titles'];
        $meta_descriptions = $product['meta_descriptions'];
        $meta_keywords = $product['meta_keywords'];
        $tags = $product['tags'];
        $sort_order = isset($product['sort_order']) ? $product['sort_order'] : '';
        if (strlen(trim($manufacturer_name))) {
            // get manufacturer_id by manufacturer_name
            $manufacturer_id = $this->getManufacturerIdByName($manufacturer_name);
        }

        $query = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product WHERE product_id = '" . $product_id . "' LIMIT 1");

        // Category added yet, need update
        if (isset($query->row['product_id'])) {
            $sql = "UPDATE " . DB_PREFIX . "product SET upc = '', ean = '', jan = '', isbn = '', mpn = ''";
            if (strlen(trim($model))) {
                $sql .= ", model = '" . $this->db->escape($model) . "'";
            }
            if (strlen(trim($sku))) {
                $sql .= ", sku = '" . $this->db->escape($sku) . "'";
            }
            if (strlen(trim($location))) {
                $sql .= ", location = '" . $this->db->escape($location) . "'";
            }
            if (strlen(trim((string)$quantity))) {
                $sql .= ", quantity = '" . (int)$quantity . "'";
            }
            if (strlen(trim((string)$minimum))) {
                $sql .= ", minimum = '" . (int)$minimum . "'";
            }
            if (strlen(trim((string)$stock_status_id))) {
                $sql .= ", stock_status_id = '" . (int)$stock_status_id . "'";
            }
            if (strlen(trim($date_available))) {
                $sql .= ", date_available = " . $this->db->escape($date_available) . "";
            }
            if (isset($manufacturer_id)) {
                $sql .= ", manufacturer_id = '" . (int)$manufacturer_id . "'";
            }
            if (strlen(trim((string)$shipping))) {
                $sql .= ", shipping = '" . (int)$shipping . "'";
            }
            if (strlen(trim((string)$price))) {
                $sql .= ", price = '" . (float)$price . "'";
            }
            if (strlen(trim((string)$points))) {
                $sql .= ", points = '" . (int)$points . "'";
            }
            if (strlen(trim((string)$weight))) {
                $sql .= ", weight = '" . (float)$weight . "'";
            }
            if (isset($weight_class_id)) {
                $sql .= ", weight_class_id = '" . (int)$weight_class_id . "'";
            }
            if (strlen(trim($length))) {
                $sql .= ", length = '" . (float)$length . "'";
            }
            if (strlen(trim($width))) {
                $sql .= ", width = '" . (float)$width . "'";
            }
            if (strlen(trim($height))) {
                $sql .= ", height = '" . (float)$height . "'";
            }
            if (isset($length_class_id)) {
                $sql .= ", length_class_id = '" . (int)$length_class_id . "'";
            }
            if (isset($status)) {
                $sql .= ", status = '" . (int)$status . "'";
            }
            $sql .= ", tax_class_id = ''";
            if (strlen(trim((string)$sort_order))) {
                $sql .= ", sort_order = '" . (int)$sort_order . "'";
            }
            $sql .=", date_modified = NOW() WHERE product_id = '" . (int)$query->row['product_id'] . "'";

            $this->db->query($sql);

            foreach ($languages as $language) {
                $language_code = $language['code'];
                $language_id = $language['language_id'];
                $name = isset($names[$language_code]) ? $this->db->escape($names[$language_code]) : '';
                $description = isset($descriptions[$language_code]) ? $this->db->escape($descriptions[$language_code]) : '';
                $meta_title = isset($meta_titles[$language_code]) ? $this->db->escape($meta_titles[$language_code]) : '';
                $meta_description = isset($meta_descriptions[$language_code]) ? $this->db->escape($meta_descriptions[$language_code]) : '';
                $meta_keyword = isset($meta_keywords[$language_code]) ? $this->db->escape($meta_keywords[$language_code]) : '';
                $tag = isset($tags[$language_code]) ? $this->db->escape($tags[$language_code]) : '';

                $sql  = "UPDATE " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "'";
                if (strlen(trim($name))) {
                    $sql .= ", `name` = '" . $this->db->escape($name) . "'";
                }
                if (strlen(trim($description))) {
                    $sql .= ", `description` = '" . $this->db->escape($description) . "'";
                }
                if (strlen(trim($tag))) {
                    $sql .= ", `tag` = '" . $this->db->escape($tag) . "'";
                }
                if (strlen(trim($meta_title))) {
                    $sql .= ", `meta_title` = '" . $this->db->escape($meta_title) . "'";
                }
                if (strlen(trim($meta_description))) {
                    $sql .= ", `meta_description` = '" . $this->db->escape($meta_description) . "'";
                }
                if (strlen(trim($meta_keyword))) {
                    $sql .= ", `meta_keyword` = '" . $this->db->escape($meta_keyword) . "'";
                }
                $sql .= " WHERE product_id = '" . (int)$product_id . "' AND language_id = '" . (int)$language_id . "'";

                $this->db->query($sql);
            }

            if (count($categories) > 0) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

                foreach ($categories as $category) {
                    $category_name_arr = explode('>', $category);
                    $category_name = $category_name_arr[count($category_name_arr) - 1];

                    if(strlen($category_name)) {
                        $category_id = $this->getCategoryIdByName($category_name);
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
                    }
                }
            }

        } else { // add new product
            $sql = "INSERT INTO " . DB_PREFIX . "product SET product_id = '" . $product_id . "', upc = '', ean = '', jan = '', isbn = '', mpn = ''";
            if (strlen(trim($model))) {
                $sql .= ", model = '" . $this->db->escape($model) . "'";
            }
            if (strlen(trim($sku))) {
                $sql .= ", sku = '" . $this->db->escape($sku) . "'";
            }
            if (strlen(trim($location))) {
                $sql .= ", location = '" . $this->db->escape($location) . "'";
            }
            if (strlen(trim((string)$quantity))) {
                $sql .= ", quantity = '" . (int)$quantity . "'";
            }
            if (strlen(trim((string)$minimum))) {
                $sql .= ", minimum = '" . (int)$minimum . "'";
            }
            if (strlen(trim((string)$stock_status_id))) {
                $sql .= ", stock_status_id = '" . (int)$stock_status_id . "'";
            }
            if (strlen(trim($date_available))) {
                $sql .= ", date_available = " . $this->db->escape($date_available) . "";
            }
            if (isset($manufacturer_id)) {
                $sql .= ", manufacturer_id = '" . (int)$manufacturer_id . "'";
            }
            if (strlen(trim((string)$shipping))) {
                $sql .= ", shipping = '" . (int)$shipping . "'";
            }
            if (strlen(trim((string)$price))) {
                $sql .= ", price = '" . (float)$price . "'";
            }
            if (strlen(trim((string)$points))) {
                $sql .= ", points = '" . (int)$points . "'";
            }
            if (strlen(trim((string)$weight))) {
                $sql .= ", weight = '" . (float)$weight . "'";
            }
            if (isset($weight_class_id)) {
                $sql .= ", weight_class_id = '" . (int)$weight_class_id . "'";
            }
            if (strlen(trim($length))) {
                $sql .= ", length = '" . (float)$length . "'";
            }
            if (strlen(trim($width))) {
                $sql .= ", width = '" . (float)$width . "'";
            }
            if (strlen(trim($height))) {
                $sql .= ", height = '" . (float)$height . "'";
            }
            if (isset($length_class_id)) {
                $sql .= ", length_class_id = '" . (int)$length_class_id . "'";
            }
            if (isset($status)) {
                $sql .= ", status = '" . (int)$status . "'";
            }
            $sql .= ", tax_class_id = ''";
            if (strlen(trim((string)$sort_order))) {
                $sql .= ", sort_order = '" . (int)$sort_order . "'";
            }
            $sql .=", date_modified = NOW(), date_added = NOW()";

            $this->db->query($sql);

            foreach ($languages as $language) {
                $language_code = $language['code'];
                $language_id = $language['language_id'];
                $name = isset($names[$language_code]) ? $this->db->escape($names[$language_code]) : '';
                $description = isset($descriptions[$language_code]) ? $this->db->escape($descriptions[$language_code]) : '';
                $meta_title = isset($meta_titles[$language_code]) ? $this->db->escape($meta_titles[$language_code]) : '';
                $meta_description = isset($meta_descriptions[$language_code]) ? $this->db->escape($meta_descriptions[$language_code]) : '';
                $meta_keyword = isset($meta_keywords[$language_code]) ? $this->db->escape($meta_keywords[$language_code]) : '';
                $tag = isset($tags[$language_code]) ? $this->db->escape($tags[$language_code]) : '';

                $sql  = "INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "'";
                $sql .= ", `name` = '" . $this->db->escape($name) . "'";
                $sql .= ", `description` = '" . $this->db->escape($description) . "'";
                $sql .= ", `tag` = '" . $this->db->escape($tag) . "'";
                $sql .= ", `meta_title` = '" . $this->db->escape($meta_title) . "'";
                $sql .= ", `meta_description` = '" . $this->db->escape($meta_description) . "'";
                $sql .= ", `meta_keyword` = '" . $this->db->escape($meta_keyword) . "'";

                $this->db->query($sql);
            }

            if (count($categories) > 0) {
                foreach ($categories as $category) {
                    $category_name_arr = explode('>', $category);
                    $category_name = $category_name_arr[count($category_name_arr) - 1];

                    if(strlen($category_name)) {
                        $category_id = $this->getCategoryIdByName($category_name);
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
                    }
                }
            }
        }

    }

    protected function uploadSpecials( &$reader, $incremental, &$available_product_ids ) {
        // get worksheet, if not there return immediately
        $data = $reader->getSheetByName( 'Specials' );
        if ($data==null) {
            return;
        }

        // get existing customer groups
        $customer_group_ids = $this->getCustomerGroupIds();

        $products_ids = array();
        $i = 0;
        $k = $data->getHighestRow();

        for ($i=0; $i<$k; $i+=1) {
            $j = 1;
            if ($i==0) {
                continue;
            }
            $product_id = trim($this->getCell($data,$i,$j++));
            if ($product_id=="") {
                continue;
            }
            $products_ids[] = $product_id;
        }

        foreach ($products_ids as $products_id) {
            $this->deleteSpecialByProductId($products_id);
        }

        // load the worksheet cells and store them to the database
        $i = 0;
        $k = $data->getHighestRow();

        for ($i=0; $i<$k; $i+=1) {
            $j = 1;
            if ($i==0) {
                $max_col = PHPExcel_Cell::columnIndexFromString( $data->getHighestColumn() );
                for ($j=1; $j<=$max_col; $j+=1) {
                    $first_row[] = $this->getCell($data,$i,$j);
                }
                continue;
            }
            $product_id = trim($this->getCell($data,$i,$j++));
            if ($product_id=="") {
                continue;
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "customer_group") {
                $customer_group = trim($this->getCell($data, $i, $j++));
                if ($customer_group == "") {
                    continue;
                }
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "priority") {
                $priority = $this->getCell($data, $i, $j++, '0');
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "price") {
                $price = $this->getCell($data, $i, $j++, '0');
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "date_start") {
                $date_start = $this->getCell($data, $i, $j++, '0000-00-00');
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "date_end") {
                $date_end = $this->getCell($data, $i, $j++, '0000-00-00');
            }

            $special = array();
            $special['product_id'] = $product_id;
            if (isset($customer_group)) {
                $special['customer_group'] = $customer_group;
            }
            if (isset($priority)) {
                $special['priority'] = $priority;
            }
            if (isset($price)) {
                $special['price'] = $price;
            }
            if (isset($date_start)) {
                $special['date_start'] = $date_start;
            }
            if (isset($date_end)) {
                $special['date_end'] = $date_end;
            }

            $this->moreSpecialCells( $i, $j, $data, $special );
            $this->storeSpecialIntoDatabase( $special, $customer_group_ids );
        }
    }

    protected function storeSpecialIntoDatabase( &$special, &$customer_group_ids ) {
        $product_id = $special['product_id'];

        $name = isset($special['customer_group']) ? $special['customer_group'] : '';
        $customer_group_id = isset($customer_group_ids[$name]) ? $customer_group_ids[$name] : $this->config->get('config_customer_group_id');
        $priority = isset($special['priority']) ? $special['priority'] : '';
        $price = isset($special['price']) ? $special['price'] : '';
        $date_start = isset($special['date_start']) ? $special['date_start'] : '';
        $date_end = isset($special['date_end']) ? $special['date_end'] : '';

        $sql = "INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "'";
        $sql .= ", customer_group_id = '" . (int)$customer_group_id . "'";
        if (strlen(trim($priority))) {
            $sql .= ", priority = '" . (int)$priority . "'";
        }
        if (strlen(trim($price))) {
            $sql .= ", price = '" . (float)$price . "'";
        }
        if (strlen(trim($date_start))) {
            $sql .= ", date_start = '" . $this->db->escape($date_start) . "'";
        }
        if (strlen(trim($date_end))) {
            $sql .= ", date_end = '" . $this->db->escape($date_end) . "'";
        }

        $this->db->query($sql);
    }

    protected function uploadDiscounts( &$reader, $incremental, &$available_product_ids ) {
        // get worksheet, if not there return immediately
        $data = $reader->getSheetByName( 'Discounts' );
        if ($data==null) {
            return;
        }

        // get existing customer groups
        $customer_group_ids = $this->getCustomerGroupIds();

        $products_ids = array();
        $i = 0;
        $k = $data->getHighestRow();
        for ($i=0; $i<$k; $i+=1) {
            $j = 1;
            if ($i==0) {
                continue;
            }
            $product_id = trim($this->getCell($data,$i,$j++));
            if ($product_id=="") {
                continue;
            }
            $products_ids[] = $product_id;
        }

        foreach ($products_ids as $products_id) {
            $this->deleteDiscountByProductId($products_id);
        }

        // load the worksheet cells and store them to the database
        $i = 0;
        $k = $data->getHighestRow();
        for ($i=0; $i<$k; $i+=1) {
            $j = 1;
            if ($i==0) {
                $max_col = PHPExcel_Cell::columnIndexFromString( $data->getHighestColumn() );
                for ($j=1; $j<=$max_col; $j+=1) {
                    $first_row[] = $this->getCell($data,$i,$j);
                }
                continue;
            }
            $product_id = trim($this->getCell($data,$i,$j++));
            if ($product_id=="") {
                continue;
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "customer_group") {
                $customer_group = trim($this->getCell($data, $i, $j++));
                if ($customer_group == "") {
                    continue;
                }
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "quantity") {
                $quantity = $this->getCell($data, $i, $j++, '0');
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "priority") {
                $priority = $this->getCell($data, $i, $j++, '0');
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "price") {
                $price = $this->getCell($data, $i, $j++, '0');
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "date_start") {
                $date_start = $this->getCell($data, $i, $j++, '0000-00-00');
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "date_end") {
                $date_end = $this->getCell($data, $i, $j++, '0000-00-00');
            }

            $discount = array();
            $discount['product_id'] = $product_id;
            if (isset($customer_group)) {
                $discount['customer_group'] = $customer_group;
            }
            if (isset($quantity)) {
                $discount['quantity'] = $quantity;
            }
            if (isset($priority)) {
                $discount['priority'] = $priority;
            }
            if (isset($price)) {
                $discount['price'] = $price;
            }
            if (isset($date_start)) {
                $discount['date_start'] = $date_start;
            }
            if (isset($date_end)) {
                $discount['date_end'] = $date_end;
            }

            $this->moreDiscountCells( $i, $j, $data, $discount );
            $this->storeDiscountIntoDatabase( $discount, $customer_group_ids );
        }
    }

    protected function storeDiscountIntoDatabase( &$discount, &$customer_group_ids ) {
        $product_id = $discount['product_id'];
        $name = isset($discount['customer_group']) ? $discount['customer_group'] : '';
        $customer_group_id = isset($customer_group_ids[$name]) ? $customer_group_ids[$name] : $this->config->get('config_customer_group_id');
        $quantity = isset($discount['quantity']) ? $discount['quantity'] : '';
        $priority = isset($discount['priority']) ? $discount['priority'] : '';
        $price = isset($discount['price']) ? $discount['price'] : '';
        $date_start = isset($discount['date_start']) ? $discount['date_start'] : '';
        $date_end = isset($discount['date_end']) ? $discount['date_end'] : '';

        $sql = "INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "'";
        $sql .= ", customer_group_id = '" . (int)$customer_group_id . "'";
        if (strlen(trim($quantity))) {
            $sql .= ", quantity = '" . (int)$quantity . "'";
        }
        if (strlen(trim($priority))) {
            $sql .= ", priority = '" . (int)$priority . "'";
        }
        if (strlen(trim($price))) {
            $sql .= ", price = '" . (float)$price . "'";
        }
        if (strlen(trim($date_start))) {
            $sql .= ", date_start = '" . $this->db->escape($date_start) . "'";
        }
        if (strlen(trim($date_end))) {
            $sql .= ", date_end = '" . $this->db->escape($date_end) . "'";
        }

        $this->db->query($sql);
    }

    protected function uploadRewards( &$reader, $incremental, &$available_product_ids ) {
        // get worksheet, if not there return immediately
        $data = $reader->getSheetByName( 'Rewards' );
        if ($data==null) {
            return;
        }

        // get existing customer groups
        $customer_group_ids = $this->getCustomerGroupIds();

        $products_ids = array();
        $i = 0;
        $k = $data->getHighestRow();
        for ($i=0; $i<$k; $i+=1) {
            $j = 1;
            if ($i==0) {
                continue;
            }
            $product_id = trim($this->getCell($data,$i,$j++));
            if ($product_id=="") {
                continue;
            }
            $products_ids[] = $product_id;
        }

        foreach ($products_ids as $products_id) {
            $this->deleteRewardByProductId($products_id);
        }

//        echo "<pre>";
        // load the worksheet cells and store them to the database
        $i = 0;
        $k = $data->getHighestRow();
        for ($i=0; $i<$k; $i+=1) {
            $j = 1;
            if ($i==0) {
                $max_col = PHPExcel_Cell::columnIndexFromString( $data->getHighestColumn() );
                for ($j=1; $j<=$max_col; $j+=1) {
                    $first_row[] = $this->getCell($data,$i,$j);
                }
                continue;
            }
            $product_id = trim($this->getCell($data,$i,$j++));
            if ($product_id=="") {
                continue;
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "customer_group") {
                $customer_group = trim($this->getCell($data, $i, $j++));
                if ($customer_group == "") {
                    continue;
                }
            }
            if(isset($first_row[$j - 1]) && $first_row[$j-1] == "points") {
                $points = $this->getCell($data, $i, $j++, '0');
            }

            $reward = array();
            $reward['product_id'] = $product_id;
            if (isset($customer_group)) {
                $reward['customer_group'] = $customer_group;
            }
            if (isset($points)) {
                $reward['points'] = $points;
            }

            $this->moreRewardCells( $i, $j, $data, $reward );
//            print_r($reward);
            $this->storeRewardIntoDatabase( $reward, $customer_group_ids );
        }
//        echo "</pre>";
    }

    protected function storeRewardIntoDatabase( &$reward, &$customer_group_ids ) {
        $product_id = $reward['product_id'];
        $name = isset($reward['customer_group']) ? $reward['customer_group'] : '';
        $customer_group_id = isset($customer_group_ids[$name]) ? $customer_group_ids[$name] : $this->config->get('config_customer_group_id');
        $points = isset($reward['points']) ? $reward['points'] : '';

        if ((int)$points > 0) {
            $sql = "INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$points . "'";

            $this->db->query($sql);
        }
    }

    protected function getManufacturerIdByName($manufacturer_name) {
        $query = $this->db->query("SELECT manufacturer_id FROM " . DB_PREFIX . "manufacturer WHERE `name` = '" . $this->db->escape($manufacturer_name) . "'");

        return $query->row['manufacturer_id'];
    }

    protected function getWeightIdByUnit($weight_unit) {
        $query = $this->db->query("SELECT weight_class_id FROM " . DB_PREFIX . "weight_class_description WHERE `unit` = '" . $this->db->escape($weight_unit) . "'");

        return $query->row['weight_class_id'];
    }

    protected function getLengthIdByUnit($length_unit) {
        $query = $this->db->query("SELECT length_class_id FROM " . DB_PREFIX . "length_class_description WHERE `unit` = '" . $this->db->escape($length_unit) . "'");

        return $query->row['length_class_id'];
    }

    protected function getCategoryIdByName($category_name) {
        $query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category_description WHERE `name` = '" . $this->db->escape($category_name) . "'");

        return $query->row['category_id'];
    }

    protected function deleteSpecialByProductId($product_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");
    }

    protected function deleteDiscountByProductId($product_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");
    }

    protected function deleteRewardByProductId($product_id) {
        $this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");
    }

    protected function getCustomerGroupIds() {
        $sql = "SHOW TABLES LIKE \"".DB_PREFIX."customer_group_description\"";
        $query = $this->db->query( $sql );
        if ($query->num_rows) {
            $language_id = $this->getDefaultLanguageId();
            $sql  = "SELECT `customer_group_id`, `name` FROM `".DB_PREFIX."customer_group_description` ";
            $sql .= "WHERE language_id=$language_id ";
            $sql .= "ORDER BY `customer_group_id` ASC";
            $query = $this->db->query( $sql );
        } else {
            $sql  = "SELECT `customer_group_id`, `name` FROM `".DB_PREFIX."customer_group` ";
            $sql .= "ORDER BY `customer_group_id` ASC";
            $query = $this->db->query( $sql );
        }
        $customer_group_ids = array();
        foreach ($query->rows as $row) {
            $customer_group_id = $row['customer_group_id'];
            $name = $row['name'];
            $customer_group_ids[$name] = $customer_group_id;
        }
        return $customer_group_ids;
    }

    protected function getDefaultWeightUnit() {
        $weight_class_id = $this->config->get( 'config_weight_class_id' );
        $language_id = $this->getDefaultLanguageId();
        $sql = "SELECT unit FROM `".DB_PREFIX."weight_class_description` WHERE language_id='".(int)$language_id."'";
        $query = $this->db->query( $sql );
        if ($query->num_rows > 0) {
            return $query->row['unit'];
        }
        $sql = "SELECT language_id FROM `".DB_PREFIX."language` WHERE code = 'en'";
        $query = $this->db->query( $sql );
        if ($query->num_rows > 0) {
            $language_id = $query->row['language_id'];
            $sql = "SELECT unit FROM `".DB_PREFIX."weight_class_description` WHERE language_id='".(int)$language_id."'";
            $query = $this->db->query( $sql );
            if ($query->num_rows > 0) {
                return $query->row['unit'];
            }
        }
        return 'кг';
    }


    protected function getDefaultLengthUnit() {
        $length_class_id = $this->config->get( 'config_length_class_id' );
        $language_id = $this->getDefaultLanguageId();
        $sql = "SELECT unit FROM `".DB_PREFIX."length_class_description` WHERE language_id='".(int)$language_id."'";
        $query = $this->db->query( $sql );
        if ($query->num_rows > 0) {
            return $query->row['unit'];
        }
        $sql = "SELECT language_id FROM `".DB_PREFIX."language` WHERE code = 'en'";
        $query = $this->db->query( $sql );
        if ($query->num_rows > 0) {
            $language_id = $query->row['language_id'];
            $sql = "SELECT unit FROM `".DB_PREFIX."length_class_description` WHERE language_id='".(int)$language_id."'";
            $query = $this->db->query( $sql );
            if ($query->num_rows > 0) {
                return $query->row['unit'];
            }
        }
        return 'см';
    }

    protected function startsWith( $haystack, $needle ) {
        if (strlen( $haystack ) < strlen( $needle )) {
            return false;
        }
        return (substr( $haystack, 0, strlen($needle) ) == $needle);
    }

    protected function endsWith( $haystack, $needle ) {
        if (strlen( $haystack ) < strlen( $needle )) {
            return false;
        }
        return (substr( $haystack, strlen($haystack)-strlen($needle), strlen($needle) ) == $needle);
    }

    protected function clean( &$str, $allowBlanks=false ) {
        $result = "";
        $n = strlen( $str );
        for ($m=0; $m<$n; $m++) {
            $ch = substr( $str, $m, 1 );
            if (($ch==" ") && (!$allowBlanks) || ($ch=="\n") || ($ch=="\r") || ($ch=="\t") || ($ch=="\0") || ($ch=="\x0B")) {
                continue;
            }
            $result .= $ch;
        }
        return $result;
    }

    protected function moreCategoryCells( $i, &$j, &$worksheet, &$category ) {
        return;
    }

    // function for reading additional cells in class extensions
    protected function moreProductCells( $i, &$j, &$worksheet, &$product ) {
        return;
    }

    // function for reading additional cells in class extensions
    protected function moreSpecialCells( $i, &$j, &$worksheet, &$special ) {
        return;
    }

    // function for reading additional cells in class extensions
    protected function moreDiscountCells( $i, &$j, &$worksheet, &$discount ) {
        return;
    }

    // function for reading additional cells in class extensions
    protected function moreRewardCells( $i, &$j, &$worksheet, &$reward ) {
        return;
    }

    protected function isInteger($input){
        return(ctype_digit(strval($input)));
    }
}
