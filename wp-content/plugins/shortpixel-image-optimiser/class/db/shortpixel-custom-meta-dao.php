<?php

class ShortPixelCustomMetaDao {
    const META_VERSION = 1;
    private $db;
    
    private static $fields = array(
        ShortPixelMeta::TABLE_SUFFIX => array(
            "folder_id" => "d",
            "path" => "s",
            "name" => "s",
            "compression_type" => "d",
            "compressed_size" => "d",
            "keep_exif" => "d",
            "cmyk2rgb" => "d",
            "resize" => "d",
            "resize_width" => "d",
            "resize_height" => "d",
            "backup" => "d",
            "status" => "d",
            "retries" => "d",
            "message" => "s",
            "ext_meta_id" => "d" //this is nggPid for now
        ),
        ShortPixelFolder::TABLE_SUFFIX => array(
            "path" => "s",
            "file_count" => "d",
            "status" => "d",
            "ts_updated" => "s",
            "ts_created" => "s",
        )
    );
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public static function getCreateFolderTableSQL($tablePrefix, $charsetCollate) {
       return "CREATE TABLE {$tablePrefix}shortpixel_folders (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            path varchar(512),
            name varchar(64),
            path_md5 char(32),
            file_count int,
            status tinyint DEFAULT 0,
            ts_updated timestamp,
            ts_created timestamp,
            UNIQUE KEY id (id)
          ) $charsetCollate;";
       // UNIQUE INDEX spf_path_md5 (path_md5)
    }
    
    public static function getCreateMetaTableSQL($tablePrefix, $charsetCollate) {
       return "CREATE TABLE {$tablePrefix}shortpixel_meta (
            id mediumint(10) NOT NULL AUTO_INCREMENT,
            folder_id mediumint(9) NOT NULL,
            ext_meta_id int(10),
            path varchar(512),
            name varchar(64),
            path_md5 char(32),
            compressed_size int(10) NOT NULL DEFAULT 0,
            compression_type tinyint,
            keep_exif tinyint,
            cmyk2rgb tinyint,
            resize tinyint,
            resize_width smallint,
            resize_height smallint,
            backup tinyint,
            status tinyint DEFAULT 0,
            retries tinyint DEFAULT 0,
            message varchar(255),
            ts_added timestamp,
            ts_optimized timestamp,
            UNIQUE KEY sp_id (id)
          ) $charsetCollate;";
          //UNIQUE INDEX sp_path_md5 (path_md5),
          //FOREIGN KEY fk_shortpixel_meta_folder(folder_id) REFERENCES {$tablePrefix}shortpixel_folders(id)
    }
    
    private function addIfMissing($type, $table, $key, $field, $fkTable = null, $fkField = null) {
        $hasIndexSql = "select count(*) hasIndex from information_schema.statistics where table_name = '%s' and index_name = '%s' and table_schema = database()";
        $createIndexSql = "ALTER TABLE %s ADD UNIQUE INDEX %s (%s)";
        $createFkSql = "ALTER TABLE %s ADD FOREIGN KEY %s(%s) REFERENCES %s(%s)";
        $hasIndex = $this->db->query(sprintf($hasIndexSql, $table, $key));
        if($hasIndex[0]->hasIndex == 0){
            if($type == "UNIQUE INDEX"){
                $this->db->query(sprintf($createIndexSql, $table, $key, $field));
            } else {
                $this->db->query(sprintf($createFkSql, $table, $key, $field, $fkTable, $fkField));
            }
            return true;
        }
        return false;
    }
    
    public function tablesExist() {
        $hasTablesSql = "SELECT COUNT(1) tableCount FROM information_schema.tables WHERE table_schema='".$this->db->getDbName()."' "
                     . "AND (table_name='".$this->db->getPrefix()."shortpixel_meta' OR table_name='".$this->db->getPrefix()."shortpixel_folders')";
        $hasTables = $this->db->query($hasTablesSql);
        if($hasTables[0]->tableCount == 2){
            return true;
        }
        return false;
    }
    
    public function dropTables() {
        if($this->tablesExist()) {
            $this->db->query("DROP TABLE ".$this->db->getPrefix()."shortpixel_meta");
            $this->db->query("DROP TABLE ".$this->db->getPrefix()."shortpixel_folders");
        }
    }
    
    public function createUpdateShortPixelTables() {
        $res = $this->db->createUpdateSchema(array(
                self::getCreateFolderTableSQL($this->db->getPrefix(), $this->db->getCharsetCollate()),
                self::getCreateMetaTableSQL($this->db->getPrefix(), $this->db->getCharsetCollate()) 
            ));
        // Set up indexes, not handled well by WP DBDelta
        $this->addIfMissing("UNIQUE INDEX", $this->db->getPrefix()."shortpixel_folders", "spf_path_md5", "path_md5");
        $this->addIfMissing("UNIQUE INDEX", $this->db->getPrefix()."shortpixel_meta", "sp_path_md5", "path_md5");
        $this->addIfMissing("FOREIGN KEY", $this->db->getPrefix()."shortpixel_meta", "fk_shortpixel_meta_folder", "folder_id", 
                                           $this->db->getPrefix()."shortpixel_folders", "id");
    }
    
    public function getFolders($deleted = false) {
        $sql = "SELECT * FROM {$this->db->getPrefix()}shortpixel_folders" . ($deleted ? "" : " WHERE status <> -1");
        $rows = $this->db->query($sql);
        $folders = array();
        foreach($rows as $row) {
            $folders[] = new ShortPixelFolder($row);
        }
        return $folders;
    }
    
    public function getFolder($path, $deleted = false) {
        $sql = "SELECT * FROM {$this->db->getPrefix()}shortpixel_folders" . ($deleted ? "" : " WHERE path = %s AND status <> -1");
        $rows = $this->db->query($sql, array($path));
        $folders = array();
        foreach($rows as $row) {
            return new ShortPixelFolder($row);
        }
        return false;
    }
    
    public function hasFoldersTable() {
        global $wpdb;
        $foldersTable = $wpdb->get_results("SELECT COUNT(1) hasFoldersTable FROM information_schema.tables WHERE table_schema='{$wpdb->dbname}' AND table_name='{$wpdb->prefix}shortpixel_folders'");
        if(isset($foldersTable[0]->hasFoldersTable) && $foldersTable[0]->hasFoldersTable > 0) {
            return true;
        }
        return false;                
    }
    
    public function addFolder($folder, $fileCount = 0) {
        //$sql = "INSERT INTO {$this->db->getPrefix()}shortpixel_folders (path, file_count, ts_created) values (%s, %d, now())";
        //$this->db->query($sql, array($folder, $fileCount));
        return $this->db->insert($this->db->getPrefix().'shortpixel_folders', 
                                 array("path" => $folder, "path_md5" => md5($folder), "file_count" => $fileCount, "ts_updated" => date("Y-m-d H:i:s")), 
                                 array("path" => "%s", "path_md5" => "%s", "file_count" => "%d", "ts_updated" => "%s"));
    }

    public function updateFolder($folder, $newPath, $status = 0, $fileCount = 0) {
        $sql = "UPDATE {$this->db->getPrefix()}shortpixel_folders SET path = %s, path_md5 = %s, file_count = %d, ts_updated = %s, status = %d WHERE path = %s";
        $this->db->query($sql, array($newPath, md5($newPath), $fileCount, date("Y-m-d H:i:s"), $status, $folder));
        $sql2 = "SELECT id FROM {$this->db->getPrefix()}shortpixel_folders WHERE path = %s";
        $res = $this->db->query($sql2, array($folder));
        if(is_array($res)) {
            return $res[0]->id;
        }
        else return -1;
    }

    public function removeFolder($folderPath) {
        $sql = "SELECT id FROM {$this->db->getPrefix()}shortpixel_folders WHERE path = %s";
        $row = $this->db->query($sql, array(stripslashes($folderPath)));
        if(!isset($row[0]->id)) return false;
        $id = $row[0]->id;
        $sql = "UPDATE {$this->db->getPrefix()}shortpixel_folders SET status = -1 WHERE id = %d";
        $this->db->query($sql, array($id));

        $this->db->hideErrors();
        $sql = "DELETE FROM {$this->db->getPrefix()}shortpixel_meta WHERE folder_id = %d AND status <> 1 AND status <> 2";
        @$this->db->query($sql, array($id));
        $sql = "DELETE FROM {$this->db->getPrefix()}shortpixel_folders WHERE path = %s";
        @$this->db->query($sql, array($folderPath));
        $this->db->restoreErrors();
    }

    public function newFolderFromPath($path, $uploadPath, $rootPath) {
        WpShortPixelDb::checkCustomTables(); // check if custom tables are created, if not, create them
        $addedFolder = ShortPixelFolder::checkFolder($path, $uploadPath);
        $addedFolder = wp_normalize_path($addedFolder); $rootPath = wp_normalize_path($rootPath);
        if($this->getFolder($addedFolder)) {
            return __('Folder already added.','shortpixel-image-optimiser');
        }
        if(strpos($addedFolder, $rootPath) !== 0) {
            return( sprintf(__('The %s folder cannot be processed as it\'s not inside the root path of your website (%s).','shortpixel-image-optimiser'),$addedFolder, $rootPath));
        }
        $folder = new ShortPixelFolder(array("path" => $addedFolder));
        try {
            $folder->setFileCount($folder->countFiles());
        } catch(SpFileRightsException $ex) {
            return $ex->getMessage();
        }
        if(ShortPixelMetaFacade::isMediaSubfolder($folder->getPath())) {
            return __('This folder contains Media Library images. To optimize Media Library images please go to <a href="upload.php?mode=list">Media Library list view</a> or to <a href="upload.php?page=wp-short-pixel-bulk">SortPixel Bulk page</a>.','shortpixel-image-optimiser');
        }
        $folderMsg = $this->saveFolder($folder);
        if(!$folder->getId()) {
            throw new Exception(__('Inserted folder doesn\'t have an ID!','shortpixel-image-optimiser'));
        }
        //die(var_dump($folder));
        if(!$folderMsg) {
            $fileList = $folder->getFileList();
            $this->batchInsertImages($fileList, $folder->getId());
        }
        return $folderMsg;
        
    }
    /**
     * 
     * @param type $path
     * @return false if saved OK, error message otherwise.
     */
    public function saveFolder(&$folder) {
        $addedPath = $folder->getPath();
        if($addedPath) {
            //first check if it does contain the Backups Folder - we don't allow that
            if(ShortPixelFolder::checkFolderIsSubfolder(SP_BACKUP_FOLDER, $addedPath)) {
                return __('This folder contains the ShortPixel Backups. Please select a different folder.','shortpixel-image-optimiser');
            }
            $customFolderPaths = array_map(array('ShortPixelFolder','path'), $this->getFolders());
            $allFolders = $this->getFolders(true);
            $customAllFolderPaths = array_map(array('ShortPixelFolder','path'), $allFolders);
            $parent = ShortPixelFolder::checkFolderIsSubfolder($addedPath, $customFolderPaths);
            if(!$parent){
                $sub = ShortPixelFolder::checkFolderIsParent($addedPath, $customAllFolderPaths);
                if($sub) {
                    $id = $this->updateFolder($sub, $addedPath, 0, $folder->getFileCount());
                } else {
                    $id = $this->addFolder($addedPath, $folder->getFileCount());
                }
                $folder->setId($id);
                return false;
            } else {
                foreach($allFolders as $fld) {
                    if($fld->getPath() == $parent) {
                        $folder->setPath($parent);
                        $folder->setId($fld->getId());
                    }
                }
                //var_dump($allFolders);
                return sprintf(__('Folder already included in %s.','shortpixel-image-optimiser'),$parent);
            }
        } else {
            return __('Folder does not exist.','shortpixel-image-optimiser');
        }        
    }    

    protected function metaToParams($meta) {
        $params = $types = array();
        foreach(self::$fields[ShortPixelMeta::TABLE_SUFFIX] as $key=>$type) {
            $getter = "get" . ShortPixelTools::snakeToCamel($key);
            if(!method_exists($meta, $getter)) {
                throw new Exception("Bad fields list in ShortPixelCustomMetaDao::metaToParams");
            }
            $val = $meta->$getter();
            if($val !== null) {
            $params[$key] = $val;
            $types[] = "%" . $type;
            }
        }
        return (object)array("params" => $params, "types" => $types);
    }
    public function addImage($meta) {
        $p = $this->metaToParams($meta);
        $id = $this->db->insert($this->db->getPrefix().'shortpixel_meta', $p->params, $p->types);
        return $id;
    }
    
    public function batchInsertImages($pathsFile, $folderId) {
        $pathsFileHandle = fopen($pathsFile, 'r');
            
        $values = ''; $inserted = 0;
        $sql = "INSERT IGNORE INTO {$this->db->getPrefix()}shortpixel_meta(folder_id, path, name, path_md5, status) VALUES ";
        for ($i = 0; ($path = fgets($pathsFileHandle)) !== false; $i++) {
            $pathParts = explode('/', trim($path));
            $namePrep = $this->db->prepare("%s",$pathParts[count($pathParts) - 1]);
            $values .= (strlen($values) ? ", ": "") . "(" . $folderId . ", ". $this->db->prepare("%s", trim($path)) . ", ". $namePrep .", '". md5($path) ."', 0)";
            if($i % 1000 == 999) {
                $id = $this->db->query($sql . $values);
                $values = '';
                $inserted++;
            }
        }
        if($values) {
            $id = $this->db->query($sql . $values);
        }
        fclose($pathsFileHandle);
        unlink($pathsFile);
        return $inserted;
    }
    
    public function getPaginatedMetas($hasNextGen, $count, $page, $orderby = false, $order = false) {
        $sql = "SELECT sm.id, sm.name, sm.path folder, "
                . ($hasNextGen ? "CASE WHEN ng.gid IS NOT NULL THEN 'NextGen' ELSE 'Custom' END media_type, " : "'Custom' media_type, ")
                . "sm.status, sm.compression_type, sm.keep_exif, sm.cmyk2rgb, sm.resize, sm.resize_width, sm.resize_height, sm.message "
                . "FROM {$this->db->getPrefix()}shortpixel_meta sm "
                . "INNER JOIN  {$this->db->getPrefix()}shortpixel_folders sf on sm.folder_id = sf.id "
                . ($hasNextGen ? "LEFT JOIN {$this->db->getPrefix()}ngg_gallery ng on sf.path = ng.path " : " ")
                . "WHERE sf.status <> -1 "
                . ($orderby ? "ORDER BY $orderby $order " : "")
                . "LIMIT $count OFFSET " . ($page - 1) * $count;
                
                //die($sql);
        return $this->db->query($sql);
    }
    
    public function getPendingMetas($count) {
        return $this->db->query("SELECT sm.id from {$this->db->getPrefix()}shortpixel_meta sm "
            . "INNER JOIN  {$this->db->getPrefix()}shortpixel_folders sf on sm.folder_id = sf.id "
            . "WHERE sf.status <> -1 AND sm.status <> 3 AND ( sm.status = 0 OR sm.status = 1 OR (sm.status < 0 AND sm.retries < 3)) "
            . "ORDER BY ts_added DESC LIMIT $count");
    }
    
    public function getFolderOptimizationStatus($folderId) {
        $res = $this->db->query("SELECT SUM(CASE WHEN sm.status = 2 THEN 1 ELSE 0 END) Optimized, SUM(CASE WHEN sm.status = 1 THEN 1 ELSE 0 END) Pending, "
            . "SUM(CASE WHEN sm.status = 0 THEN 1 ELSE 0 END) Waiting, SUM(CASE WHEN sm.status < 0 THEN 1 ELSE 0 END) Failed, count(*) Total "
            . "FROM  {$this->db->getPrefix()}shortpixel_meta sm "
            . "INNER JOIN  {$this->db->getPrefix()}shortpixel_folders sf on sm.folder_id = sf.id "
            . "WHERE sf.id = $folderId");
        return $res[0];
    }
    
    public function getPendingMetaCount() {
        $res = $this->db->query("SELECT COUNT(sm.id) recCount from  {$this->db->getPrefix()}shortpixel_meta sm "
            . "INNER JOIN  {$this->db->getPrefix()}shortpixel_folders sf on sm.folder_id = sf.id "
            . "WHERE sf.status <> -1 AND ( sm.status = 0 OR sm.status = 1 )");
        return isset($res[0]->recCount) ? $res[0]->recCount : null;
    }
    
    public function getCustomMetaCount() {
        $sql = "SELECT COUNT(sm.id) recCount FROM {$this->db->getPrefix()}shortpixel_meta sm "
            . "INNER JOIN  {$this->db->getPrefix()}shortpixel_folders sf on sm.folder_id = sf.id "
            . "WHERE sf.status <> -1";
        $res = $this->db->query($sql);
        return isset($res[0]->recCount) ? $res[0]->recCount : 0;
    }
    
    public function getMeta($id, $deleted = false) {
        $sql = "SELECT * FROM {$this->db->getPrefix()}shortpixel_meta WHERE id = %d " . ($deleted ? "" : " AND status <> -1");
        $rows = $this->db->query($sql, array($id));
        foreach($rows as $row) {
            $meta = new ShortPixelMeta($row);
            if($meta->getPath()) {
                $meta->setWebPath(ShortPixelMetaFacade::filenameToRootRelative($meta->getPath()));
            }
            //die(var_dump($meta)."ZA META");
            return $meta;
        }
        return null;        
    }

    public function getMetaForPath($path, $deleted = false) {
        $sql = "SELECT * FROM {$this->db->getPrefix()}shortpixel_meta WHERE path = %s " . ($deleted ? "" : " AND status <> -1");
        $rows = $this->db->query($sql, array($path));
        foreach($rows as $row) {
            return new ShortPixelMeta($row);
        }
        return null;        
    }
    
    public function update($meta) {
        $metaClass = get_class($meta);
        $tableSuffix = "";
        eval( '$tableSuffix = ' . $metaClass . '::TABLE_SUFFIX;');
        $sql = "UPDATE {$this->db->getPrefix()}shortpixel_" . $tableSuffix . " SET ";
        foreach(self::$fields[$tableSuffix] as $field => $type) {
            $getter = "get" . ShortPixelTools::snakeToCamel($field);
            $val = $meta->$getter();
            if($meta->$getter() !== null) {
                $sql .= " {$field} = %{$type},"; $params[] = $val;
            }
        }
        
        if(substr($sql, -1) != ',') {
            return; //no fields added;
        } 
        
        $sql = rtrim($sql, ",");
        $sql .= " WHERE id = %d";
        $params[] = $meta->getId();
        $this->db->query($sql, $params);
    }
    
    public function delete($meta) {
        $metaClass = get_class($meta);
        $tableSuffix = "";
        eval( '$tableSuffix = ' . $metaClass . '::TABLE_SUFFIX;');
        $sql = "DELETE FROM {$this->db->getPrefix()}shortpixel_" . $tableSuffix . " WHERE id = %d";
        $this->db->query($sql, array($meta->getId()));
    }
    
    public function countAllProcessableFiles() {
        $sql = "SELECT count(*) totalFiles, sum(CASE WHEN status = 2 THEN 1 ELSE 0 END) totalProcessedFiles,"
              ." sum(CASE WHEN status = 2 AND compression_type = 1 THEN 1 ELSE 0 END) totalProcLossyFiles,"
              ." sum(CASE WHEN status = 2 AND compression_type = 0 THEN 1 ELSE 0 END) totalProcLosslessFiles"
              ." FROM {$this->db->getPrefix()}shortpixel_meta WHERE status <> -1";
        $rows = $this->db->query($sql);
        
        $filesWithErrors = array();
        $sql = "SELECT id, name, path, message FROM {$this->db->getPrefix()}shortpixel_meta WHERE status < -1 AND retries >= 3 LIMIT 30";
        $failRows = $this->db->query($sql);
        $filesWithErrors = array();
        foreach($failRows as $failLine) {
            $filesWithErrors['C-' . $failLine->id] = array('Name' => $failLine->name, 'Message' => $failLine->message, 'Path' => $failLine->path);
        }
                
        return array("totalFiles" => $rows[0]->totalFiles, "mainFiles" => $rows[0]->totalFiles, 
                     "totalProcessedFiles" => $rows[0]->totalProcessedFiles, "mainProcessedFiles" => $rows[0]->totalProcessedFiles,
                     "totalProcLossyFiles" => $rows[0]->totalProcLossyFiles, "mainProcLossyFiles" => $rows[0]->totalProcLossyFiles,
                     "totalProcLosslessFiles" => $rows[0]->totalProcLosslessFiles, "mainProcLosslessFiles" => $rows[0]->totalProcLosslessFiles,
                     "totalCustomFiles" => $rows[0]->totalFiles, "mainCustomFiles" => $rows[0]->totalFiles, 
                     "totalProcessedCustomFiles" => $rows[0]->totalProcessedFiles, "mainProcessedCustomFiles" => $rows[0]->totalProcessedFiles,
                     "totalProcLossyCustomFiles" => $rows[0]->totalProcLossyFiles, "mainProcLossyCustomFiles" => $rows[0]->totalProcLossyFiles,
                     "totalProcLosslessCustomFiles" => $rows[0]->totalProcLosslessFiles, "mainProcLosslessCustomFiles" => $rows[0]->totalProcLosslessFiles,
                     "filesWithErrors" => $filesWithErrors
                    );
       
    }
}
