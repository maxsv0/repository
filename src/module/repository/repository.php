<?php

function RepositoryImport() {
    $key = $_REQUEST["key"];

    if (empty($key)) {
        echo "Error: key not found";
        return false;
    }

    $result = db_get(TABLE_REPOSITORY_KEYS, " `key` = '".db_escape($key)."'");
    if ($result["ok"] && !empty($result["data"])) {

        $archivePath = msv_store_file($_FILES["file"]["tmp_name"], "zip");

        $fileModulePath = UPLOAD_FILES_PATH."/".$archivePath;
        $zipFilename = UPLOAD_FILES_PATH."/repository/".$_REQUEST["module"]."-".$_REQUEST["version"].".zip";
        $archivePath = "repository/".$_REQUEST["module"]."-".$_REQUEST["version"].".zip";

        rename($fileModulePath, $zipFilename);

        $keyRow = $result["data"];

        // add item to repository
        $item = array(
            "published" => 1,
            "rep" => "main",
            "name" => $_REQUEST["module"],
            "title" => $_REQUEST["title"],
            "version" => $_REQUEST["version"],
            "date" => $_REQUEST["released"],
            "description" => $_REQUEST["description"],
            "source" => $keyRow["name"],
            "archive" => $archivePath,
        );
        $result = msv_add_repository_module($item);

        if ($result["ok"]) {
            echo "SUCCESS: {$_REQUEST["module"]} v.{$_REQUEST["version"]} loaded successfully\n";
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


    $resultQuery = db_get_list(TABLE_REPOSITORY, "", "`name` asc");
    if ($resultQuery["ok"]) {
        // assign data to template
        msv_assign_data("repository_list", $resultQuery["data"]);
    }


}


function RepositoryModule($module) {

    $repName = $module->website->requestUrlMatch[1];
    $moduleName = $module->website->requestUrlMatch[2];

    if (empty($repName) || empty($moduleName)) {
        $module->website->output404();
    } else {
        $resultModule = db_get(TABLE_REPOSITORY,"`rep` = '".db_escape($repName)."' and `name` = '".db_escape($moduleName)."'");
        if (!$resultModule["ok"]) {
            $module->website->output404();
        }

        $fileModule = $resultModule["data"]["archive"];
        $fileModulePath = UPLOAD_FILES_PATH."/".$fileModule;
        if (!file_exists($fileModulePath)) {
            $module->website->output404();
        }

        $item = array(
            "published" => 1,
            "date" => date("Y-m-d H:i:s"),
            "module" => $moduleName,
            "ip" => msv_get_ip(),
            "ua" => $_SERVER['HTTP_USER_AGENT'],
            "ref" => $_SERVER['HTTP_REFERER']
        );
        $resultView = db_add(TABLE_MODULE_DOWNLOADS, $item);

        // output zip
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.basename($fileModule).'"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        header('Cache-Control: private');
        header('Pragma: private');
        readfile(UPLOAD_FILES_PATH.$fileModulePath);
        exit;
    }

    $module->website->output404();
}

function RepositoryBuild() {

    msv_log("Start RepositoryBuild");
    msv_message_ok("Start RepositoryBuild");

    $modules = msv_get("website.modules");
    $i = 1;
    foreach ($modules as $moduleName) {

        $obj = msv_get("website.".$moduleName);
        if (!empty($obj)) {
            $versionLocal = $obj->version;
            $zipFilename = UPLOAD_FILES_PATH."/repository/".$moduleName."-".$versionLocal.".zip";
            $zipFileUrl = CONTENT_URL."/repository/".$moduleName."-".$versionLocal.".zip";

            // check this module in repository before adding
            $result = db_get(TABLE_REPOSITORY, "`rep` = 'main' and  `name` = '".db_escape($moduleName)."'");
            if ($result["ok"] && !empty($result["data"])) {
                $moduleRow = $result["data"];
                $moduleVersion = $moduleRow["version"];

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
                    msv_message_error("File not found: $filePath ({$fileInfo["abs_path"]})");
                }

                $zipArchive->addFile($fileInfo["abs_path"], $filePath);
            }
            $zipArchive->close();

            $cont = file_get_contents($tmpFilename);
            fclose($zipFile);

            file_put_contents($zipFilename, $cont);

            msv_log(($i++).". $moduleName successfully writen to $zipFilename");

            msv_message_ok("$moduleName OK");

            $item = array(
                "published" => 1,
                "rep" => "main",
                "name" => $moduleName,
                "title" => $obj->title,
                "version" => $obj->version,
                "date" => date("Y-m-d H:i:s", strtotime($obj->date)),
                "description" => $obj->description,
                "date_build" => date("Y-m-d H:i:s"),
                "source" => "local",
                "archive" => $zipFileUrl,
            );

            $result = msv_add_repository_module($item);
            if ($result["ok"]) {
                msv_message_ok($result["msg"]);
            } else {
                msv_message_error($result["msg"]);
            }
        }
    }

    msv_log("RepositoryBuild done");
    msv_message_ok("Done!");
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


            $modulesListLocal = msv_get("website.modules");

            foreach ($modulesListLocal as $moduleName) {
                if ($moduleName === "repository") continue;

                if (substr($moduleName, 0, 1) === "-") {
                    $moduleName = substr($moduleName, 1);
                }

                $obj = msv_get("website.".$moduleName);

                $downloadUrl = $module->website->protocol.$module->website->masterhost."/rep/main/".$moduleName."/";

                $strDep = "";
                foreach ($obj->dependency as $item) {
                    $strDep .= $item["module"].",";
                }
                $strDep = substr($strDep, 0, -1);

                $resultCount = db_get_count("module_downloads", "`module` = '".$moduleName."'");

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
