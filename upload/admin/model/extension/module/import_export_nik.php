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
                $data[$j++] = 'name (' . $language['code'] . ')';
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
        $sql  = "SELECT ";
        $sql .= "  p.product_id,";
        $sql .= "  GROUP_CONCAT( DISTINCT CAST(pc.category_id AS CHAR(11)) SEPARATOR \",\" ) AS categories,";
        $sql .= "  p.sku,";

        $sql .= "  p.location,";
        $sql .= "  p.quantity,";
        $sql .= "  p.model,";
        $sql .= "  m.name AS manufacturer,";
        $sql .= "  p.shipping,";
        $sql .= "  p.price,";
        $sql .= "  p.points,";
        $sql .= "  p.date_available,";
        $sql .= "  p.weight,";
        $sql .= "  wc.unit AS weight_unit,";
        $sql .= "  p.length,";
        $sql .= "  p.width,";
        $sql .= "  p.height,";
        $sql .= "  p.status,";
        $sql .= "  p.sort_order,";
        if (!$exist_seo_url_table) {
            $sql .= "  su.keyword,";
        }
        $sql .= "  p.stock_status_id, ";
        $sql .= "  mc.unit AS length_unit, ";
        $sql .= "  p.minimum ";
        $sql .= "FROM `".DB_PREFIX."product` p ";
        $sql .= "LEFT JOIN `".DB_PREFIX."product_to_category` pc ON p.product_id=pc.product_id ";
        if (!$exist_seo_url_table) {
            $sql .= "LEFT JOIN `".DB_PREFIX."seo_url` su ON su.query=CONCAT('product_id=',p.product_id) ";
        }
        $sql .= "LEFT JOIN `".DB_PREFIX."manufacturer` m ON m.manufacturer_id = p.manufacturer_id ";
        $sql .= "LEFT JOIN `".DB_PREFIX."weight_class_description` wc ON wc.weight_class_id = p.weight_class_id ";
        $sql .= "  AND wc.language_id=$default_language_id ";
        $sql .= "LEFT JOIN `".DB_PREFIX."length_class_description` mc ON mc.length_class_id=p.length_class_id ";
        $sql .= "  AND mc.language_id=$default_language_id ";
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
            $query = $this->db->query("SELECT GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name FROM " . DB_PREFIX . "category_path cp LEFT JOIN " . DB_PREFIX . "category c1 ON (cp.category_id = c1.category_id) LEFT JOIN " . DB_PREFIX . "category c2 ON (cp.path_id = c2.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd1 ON (cp.path_id = cd1.category_id) LEFT JOIN " . DB_PREFIX . "category_description cd2 ON (cp.category_id = cd2.category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c1.`category_id` = '" . (int)$category_id . "'");
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

//        echo "<pre>";
//        print_r($options);
//        echo "</pre>";

        // Set the column widths
        $j = 0;
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('product_id'),4)+1);

        if (isset($options['product_name']) && !empty($options['product_name'])) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('name') + 4, 30) + 1);
            }
        }

        if (isset($options['product_description']) && !empty($options['product_description'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('categories'), 30) + 1);
        }

        if (isset($options['product_sku']) && !empty($options['product_sku'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('sku'), 10) + 1);
        }

        if (isset($options['product_location']) && !empty($options['product_location'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('location'), 10) + 1);
        }

        if (isset($options['product_quantity']) && !empty($options['product_quantity'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('quantity'), 4) + 1);
        }

        if (isset($options['product_model']) && !empty($options['product_model'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('model'), 12) + 1);
        }

        if (isset($options['product_manufacturer']) && !empty($options['product_manufacturer'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('manufacturer'), 15) + 1);
        }

        if (isset($options['product_shipping']) && !empty($options['product_shipping'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('shipping'), 5) + 1);
        }

        if (isset($options['product_price']) && !empty($options['product_price'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('price'), 10) + 1);
        }

        if (isset($options['product_price']) && !empty($options['product_price'])) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('points'), 5) + 1);
        }

        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('date_available'),10)+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('weight'),6)+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('weight_unit'),3)+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('length'),8)+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('width'),8)+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('height'),8)+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('length_unit'),3)+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('status'),5)+1);
        if (!$exist_seo_url_table) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('seo_keyword'),16)+1);
        }
        foreach ($languages as $language) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('description')+4,32)+1);
        }
        if ($exist_meta_title) {
            foreach ($languages as $language) {
                $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_title')+4,20)+1);
            }
        }
        foreach ($languages as $language) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_description')+4,32)+1);
        }
        foreach ($languages as $language) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('meta_keywords')+4,32)+1);
        }
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('stock_status_id'),3)+1);

        foreach ($languages as $language) {
            $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('tags')+4,32)+1);
        }
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('sort_order'),8)+1);
        $worksheet->getColumnDimensionByColumn($j++)->setWidth(max(strlen('minimum'),8)+1);

        // The product headings row and column styles
        $styles = array();
        $data = array();
        $i = 1;
        $j = 0;
        $data[$j++] = 'product_id';
        foreach ($languages as $language) {
            $styles[$j] = &$text_format;
            $data[$j++] = 'name('.$language['code'].')';
        }
        $styles[$j] = &$text_format;
        $data[$j++] = 'categories';
        $styles[$j] = &$text_format;
        $data[$j++] = 'sku';

        $styles[$j] = &$text_format;
        $data[$j++] = 'location';
        $data[$j++] = 'quantity';
        $styles[$j] = &$text_format;
        $data[$j++] = 'model';
        $styles[$j] = &$text_format;
        $data[$j++] = 'manufacturer';
        $data[$j++] = 'shipping';
        $styles[$j] = &$price_format;
        $data[$j++] = 'price';
        $data[$j++] = 'points';
        $data[$j++] = 'date_available';
        $styles[$j] = &$weight_format;
        $data[$j++] = 'weight';
        $data[$j++] = 'weight_unit';
        $data[$j++] = 'length';
        $data[$j++] = 'width';
        $data[$j++] = 'height';
        $data[$j++] = 'length_unit';
        $data[$j++] = 'status';
        if (!$exist_seo_url_table) {
            $styles[$j] = &$text_format;
            $data[$j++] = 'seo_keyword';
        }
        foreach ($languages as $language) {
            $styles[$j] = &$text_format;
            $data[$j++] = 'description('.$language['code'].')';
        }
        if ($exist_meta_title) {
            foreach ($languages as $language) {
                $styles[$j] = &$text_format;
                $data[$j++] = 'meta_title('.$language['code'].')';
            }
        }
        foreach ($languages as $language) {
            $styles[$j] = &$text_format;
            $data[$j++] = 'meta_description('.$language['code'].')';
        }
        foreach ($languages as $language) {
            $styles[$j] = &$text_format;
            $data[$j++] = 'meta_keywords('.$language['code'].')';
        }
        $data[$j++] = 'stock_status_id';
        foreach ($languages as $language) {
            $styles[$j] = &$text_format;
            $data[$j++] = 'tags('.$language['code'].')';
        }
        $data[$j++] = 'sort_order';
        $data[$j++] = 'minimum';
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
            foreach ($languages as $language) {
                $data[$j++] = html_entity_decode($row['name'][$language['code']],ENT_QUOTES,'UTF-8');
            }

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
            $data[$j++] = $row['sku'];
            $data[$j++] = $row['location'];
            $data[$j++] = $row['quantity'];
            $data[$j++] = $row['model'];
            $data[$j++] = $row['manufacturer'];
            $data[$j++] = ($row['shipping']==0) ? '0' : '1';
            $data[$j++] = $row['price'];
            $data[$j++] = $row['points'];
            $data[$j++] = $row['date_available'];
            $data[$j++] = $row['weight'];
            $data[$j++] = $row['weight_unit'];
            $data[$j++] = $row['length'];
            $data[$j++] = $row['width'];
            $data[$j++] = $row['height'];
            $data[$j++] = $row['length_unit'];
            $data[$j++] = ($row['status']==0) ? '0' : '1';
            if (!$exist_seo_url_table) {
                $data[$j++] = ($row['keyword']) ? $row['keyword'] : '';
            }
            foreach ($languages as $language) {
                $data[$j++] = html_entity_decode($row['description'][$language['code']],ENT_QUOTES,'UTF-8');
            }
            if ($exist_meta_title) {
                foreach ($languages as $language) {
                    $data[$j++] = html_entity_decode($row['meta_title'][$language['code']],ENT_QUOTES,'UTF-8');
                }
            }
            foreach ($languages as $language) {
                $data[$j++] = html_entity_decode($row['meta_description'][$language['code']],ENT_QUOTES,'UTF-8');
            }
            foreach ($languages as $language) {
                $data[$j++] = html_entity_decode($row['meta_keyword'][$language['code']],ENT_QUOTES,'UTF-8');
            }
            $data[$j++] = $row['stock_status_id'];
            foreach ($languages as $language) {
                $data[$j++] = html_entity_decode($row['tag'][$language['code']],ENT_QUOTES,'UTF-8');
            }
            $data[$j++] = $row['sort_order'];
            $data[$j++] = $row['minimum'];
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

    protected function getRewards( $language_id, $object_ids=null ) {
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

                    // creating the Specials worksheet
                    $workbook->createSheet();
                    $workbook->setActiveSheetIndex($worksheet_index++);
                    $worksheet = $workbook->getActiveSheet();
                    $worksheet->setTitle( 'Specials' );
                    $this->populateSpecialsWorksheet( $worksheet, $default_language_id, $price_format, $box_format, $text_format, $objects_ids, $options );
                    $worksheet->freezePaneByColumnAndRow( 1, 2 );

                    // creating the Discounts worksheet
                    $workbook->createSheet();
                    $workbook->setActiveSheetIndex($worksheet_index++);
                    $worksheet = $workbook->getActiveSheet();
                    $worksheet->setTitle( 'Discounts' );
                    $this->populateDiscountsWorksheet( $worksheet, $default_language_id, $price_format, $box_format, $text_format, $objects_ids, $options );
                    $worksheet->freezePaneByColumnAndRow( 1, 2 );

                    // creating the Rewards worksheet
                    $workbook->createSheet();
                    $workbook->setActiveSheetIndex($worksheet_index++);
                    $worksheet = $workbook->getActiveSheet();
                    $worksheet->setTitle( 'Rewards' );
                    $this->populateRewardsWorksheet( $worksheet, $default_language_id, $box_format, $text_format, $objects_ids, $options );
                    $worksheet->freezePaneByColumnAndRow( 1, 2 );
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
}
