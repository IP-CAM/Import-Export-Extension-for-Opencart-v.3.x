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

//                case 'p':
//                    // creating the Products worksheet
//                    $workbook->setActiveSheetIndex($worksheet_index++);
//                    $worksheet = $workbook->getActiveSheet();
//                    $worksheet->setTitle( 'Products' );
//                    $this->populateProductsWorksheet( $worksheet, $languages, $default_language_id, $price_format, $box_format, $weight_format, $text_format, $offset, $rows, $min_id, $max_id );
//                    $worksheet->freezePaneByColumnAndRow( 1, 2 );
//
//                    // creating the Specials worksheet
//                    $workbook->createSheet();
//                    $workbook->setActiveSheetIndex($worksheet_index++);
//                    $worksheet = $workbook->getActiveSheet();
//                    $worksheet->setTitle( 'Specials' );
//                    $this->populateSpecialsWorksheet( $worksheet, $default_language_id, $price_format, $box_format, $text_format, $min_id, $max_id );
//                    $worksheet->freezePaneByColumnAndRow( 1, 2 );
//
//                    // creating the Discounts worksheet
//                    $workbook->createSheet();
//                    $workbook->setActiveSheetIndex($worksheet_index++);
//                    $worksheet = $workbook->getActiveSheet();
//                    $worksheet->setTitle( 'Discounts' );
//                    $this->populateDiscountsWorksheet( $worksheet, $default_language_id, $price_format, $box_format, $text_format, $min_id, $max_id );
//                    $worksheet->freezePaneByColumnAndRow( 1, 2 );
//
//                    // creating the Rewards worksheet
//                    $workbook->createSheet();
//                    $workbook->setActiveSheetIndex($worksheet_index++);
//                    $worksheet = $workbook->getActiveSheet();
//                    $worksheet->setTitle( 'Rewards' );
//                    $this->populateRewardsWorksheet( $worksheet, $default_language_id, $box_format, $text_format, $min_id, $max_id );
//                    $worksheet->freezePaneByColumnAndRow( 1, 2 );
//                    break;

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

            print_r(1);

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
