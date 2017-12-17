<?php

function RepositoryImport() {
	$key = $_REQUEST["key"];
	
	if (empty($key)) {
		echo "Error: key not found";
		return false;
	}
	
    $result = API_getDBItem(TABLE_REPOSITORY_KEYS, " `key` = '".MSV_SQLEscape($key)."'");
	if ($result["ok"] && !empty($result["data"])) {
		
		$archivePath = MSV_storeFile($_FILES["file"]["tmp_name"], "zip");
		
		$keyRow = $result["data"];
		
		$moduleName = $_REQUEST["module"];
		$moduleTitle = $_REQUEST["title"];
		$moduleVersion = $_REQUEST["version"];
		$moduleReleased = $_REQUEST["released"];
		$moduleDescription = $_REQUEST["description"];
	  
		
		// check this module in repository before adding
		$result = API_getDBItem(TABLE_REPOSITORY, " `name` = '".MSV_SQLEscape($moduleName)."'");
		if ($result["ok"] && !empty($result["data"])) {
			$moduleRow = $result["data"];
			$versionLocal = $moduleRow["version"];
			
			if (version_compare($versionLocal, $moduleVersion) <= 0) {
				echo "ERROR: local version $versionLocal, can't update to $moduleVersion";
				return false;
			}
			
		}
		
		// add item to repository
		$item = array(
			"published" => 1,
			"name" => $moduleName,
			"title" => $moduleTitle,
			"version" => $moduleVersion,
			"date" => date("Y-m-d H:i:s", strtotime($moduleReleased)),
			"description" => $moduleDescription,
			"date_build" => date("Y-m-d H:i:s"),
			"source" => $keyRow["name"],
			"archive" => $archivePath,
		);
		
		$result = API_itemAdd(TABLE_REPOSITORY, $item, "*");
		if ($result["ok"]) {
			echo "SUCCESS: $moduleName v.$moduleVersion loaded successfully\n";
			echo "File ID: ".$result["insert_id"];
		} else {
			echo "ERROR: ".$result["msg"];
		}
		
		return false;
		
	}
	
	echo "ERROR: key not found";
	return false;
}

function RepositoryLoad() {
	
	
	
	$resultQuery = API_getDBList(TABLE_REPOSITORY, "", "`name` asc");
	if ($resultQuery["ok"]) {
		$listItems = array();
		foreach ($resultQuery["data"] as $item) {
			// skip main archive, not a module
			if ($item["name"] === "msv") continue;
			
			$path = UPLOAD_FILES_PATH."/repository/".$item["name"].".zip";
			if (file_exists($path)) {
				$item["fileurl"] = CONTENT_URL."/repository/".$item["name"].".zip";
				$item["file"] = $item["name"].".zip";
				$item["size"] = filesize($path);
			}
			$listItems[] = $item;
		}
		
		// assign data to template
		MSV_assignData("repository_list", $listItems);
	}
	
	
}


function RepositoryModule($module) {
	
	$repName = $module->website->requestUrlMatch[1];
	$moduleName = $module->website->requestUrlMatch[2];

	if (empty($repName) || empty($moduleName)) {
		$module->website->output404();
	} else {
		
		$item = array(
			"published" => 1,
			"date" => date("Y-m-d H:i:s"),
			"module" => $moduleName,
			"ip" => $_SERVER['REMOTE_ADDR'],
			"ua" => $_SERVER['HTTP_USER_AGENT'],
			"ref" => $_SERVER['HTTP_REFERER']
		);
		$result = API_itemAdd(TABLE_MODULE_DOWNLOADS, $item);
		
		// output zip
		header('Content-Type: application/zip'); 
	    header('Content-Disposition: attachment; filename="'.$moduleName.'.zip"');
	    header('Content-Transfer-Encoding: binary');
	    header('Accept-Ranges: bytes');
	    header('Cache-Control: private');
	    header('Pragma: private');
		
	    $zipFilename = UPLOAD_FILES_PATH."/repository/".$moduleName.".zip";
	    
	    // TODO: check file $zipFilename: if exist, if readble..
	    
		readfile($zipFilename);
		exit;
	}	
	
	$module->website->output404();
}

function RepositoryBuild() {
	
	MSV_Log("Start RepositoryBuild");
	MSV_MessageOK("Start RepositoryBuild");
	
	$modules = MSV_get("website.modules");
	$i = 1;
	foreach ($modules as $moduleName) {
		
		$zipFilename = UPLOAD_FILES_PATH."/repository/".$moduleName.".zip";
		$zipFileUrl = CONTENT_URL."/repository/".$moduleName.".zip";
		
		$obj = MSV_get("website.".$moduleName);
		if (!empty($obj)) {
			
			
			// check this module in repository before adding
			$result = API_getDBItem(TABLE_REPOSITORY, " `name` = '".MSV_SQLEscape($moduleName)."'");
			if ($result["ok"] && !empty($result["data"])) {
				$moduleRow = $result["data"];
				$moduleVersion = $moduleRow["version"];
				$versionLocal = $obj->version;
				
				if (version_compare($versionLocal, $moduleVersion) <= 0) {
					continue;
				}
				
			}
			
			
			
			
			
			
			
			
			
			$zipFile = tmpfile();
			$zipArchive = new ZipArchive();
			
			$metaDatas = stream_get_meta_data($zipFile);
			$tmpFilename = $metaDatas['uri'];
			
			if (!$zipArchive->open($tmpFilename, ZIPARCHIVE::OVERWRITE)) {
				return false;
			}
			foreach ($obj->files as $fileInfo) {
				$filePath = $fileInfo["dir"]."/".$fileInfo["path"];
				
				if (!file_exists($fileInfo["abs_path"])) {
					MSV_Error("File not found: $filePath ({$fileInfo["abs_path"]})");
				}
				
				$zipArchive->addFile($fileInfo["abs_path"], $filePath);
			}
			$zipArchive->close();
			
			$cont = file_get_contents($tmpFilename);
			fclose($zipFile);
			
			file_put_contents($zipFilename, $cont);
			
			MSV_Log(($i++).". $moduleName successfully writen to $zipFilename");
			
			MSV_MessageOK("$moduleName OK");
			
			//$r = API_deleteDBItem(TABLE_REPOSITORY, "`name` = '".MSV_SQLEscape($moduleName)."'");

			$item = array(
				"published" => 1,
				"name" => $moduleName,
				"title" => $obj->title,
				"version" => $obj->version,
				"date" => date("Y-m-d H:i:s", strtotime($obj->date)),
				"description" => $obj->description,
				"date_build" => date("Y-m-d H:i:s"),
				"source" => "local",
				"archive" => $zipFileUrl,
			);
			
			$result = API_itemAdd(TABLE_REPOSITORY, $item);
		}
	}
	
	MSV_Log("RepositoryBuild done");
	MSV_MessageOK("Done!");
}

function RepositoryList($module) {
	$repName = $module->website->requestUrlMatch[1];
	if (empty($repName)) {
		$module->website->output404();
	} else {
		$result = array(
			"ok" => false,
			"data" => array(),
			"msg" => "",
		);
		
		if ($repName === "main") {
			$modulesList = $modulesListLocal = array();
			
			// TODO:
			// load modules from different folder
			// get modules list
//			if ($handle = opendir(ABS_ALLMODULES)) {
//			    while (false !== ($entry = readdir($handle))) {
//			    	if (strpos($entry, ".") === 0) {
//			    		continue;
//			    	}
//			    	$modulePath = ABS_ALLMODULES."/".$entry;
//			    	if (!is_dir($modulePath)) {
//			    		continue;
//			    	}
//			    	if (strpos($entry, "-") === 0) {
//			    		$entry = substr($entry, 1);
//
//			    		continue;
//			    	}
//			        // add module to list of avaliable modules
//			       $modulesListLocal[] = $entry;
//			    }
//				closedir($handle);
//			}

			
			$modulesListLocal = MSV_get("website.modules");
			
			foreach ($modulesListLocal as $moduleName) {
				if ($moduleName === "repository") continue;

				if (substr($moduleName, 0, 1) === "-") {
					$moduleName = substr($moduleName, 1);
				}
				
				$obj = MSV_get("website.".$moduleName);

				$downloadUrl = $module->website->protocol.$module->website->masterhost."/rep/main/".$moduleName."/";
				
				$strDep = "";
				foreach ($obj->dependency as $item) {
					$strDep .= $item["module"].",";
				}
				$strDep = substr($strDep, 0, -1);
				
				$resultCount = API_getDBCount("module_downloads", "`module` = '".$moduleName."'");
				
				$filesList = array();
				foreach ($obj->files as $fileInfo) {
					$filesList[] = array(
						"dir" => $fileInfo["dir"],
						"path" => $fileInfo["path"]
					);
				}
				
				$modulesList[$moduleName] = array(
					"name" => $obj->name,
					"title" => $obj->title,
					"description" => $obj->description,
					"dependency" => $strDep,
					"date" => $obj->date,
					"version" => $obj->version,
					"download_url" => $downloadUrl,
					"files" => $filesList,
					"downloads" => ($resultCount["ok"] ? $resultCount["data"] : 0),
				);
			}
			
			$result["ok"] = true;
			$result["data"] = $modulesList;
		} else {
			$result["msg"] = "Repository not found";
		}
		$responseJSON = json_encode($result);
		
		
		$module->website->output($responseJSON);
	}
	
}
