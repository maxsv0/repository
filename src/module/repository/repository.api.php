<?php

function RepositoryListModules($module) {
    $request = msv_get('website.requestUrlMatch');
    $repName = $request[2];

    if (!empty($request[3])) {
        $moduleName = $request[3];
    }

    switch ($repName) {
        case "main":

            if (!empty($moduleName)) {

                msv_repository_module($repName, $moduleName);

            } else {
                $resultQuery = db_get_list(TABLE_REPOSITORY, "", "`name` asc");
                if ($resultQuery["ok"]) {
                    $moduleList = array();

                    foreach ($resultQuery["data"] as $module) {
                        $downloadUrl = PROTOCOL.HOST."/api/rep/main/".$module["name"]."/";

                        $moduleList[$module["name"]] = array(
                            "name" => $module["name"],
                            "title" => $module["title"],
                            "description" => $module["description"],
                            "date" => $module["date"],
                            "preview" => $module["preview"],
                            "version" => $module["version"],
                            "download_url" => $downloadUrl,
                            "files" => $module["files"],
                            "tags" => $module["tags"],
                        );
                    }

                    $resultQuery["data"] = $moduleList;
                }
            }
            break;
        default:
            $resultQuery = array(
                "ok" => false,
                "data" => array(),
                "msg" => "Wrong API call",
            );
            break;
    }

    // do not output sql for security reasons
    unset($resultQuery["sql"]);

    // output result as JSON
    return json_encode($resultQuery);
}

function RepositoryImport() {
    $key = $_REQUEST["key"];
    $moduleName = $_REQUEST["module"];

    if (empty($key)) {
        echo "[ERROR]: key not found\n";
        return false;
    }

    if (empty($moduleName)) {
        echo "[ERROR]: Module name not found\n";
        return false;
    }

    $result = db_get(TABLE_REPOSITORY_KEYS, " `key` = '".db_escape($key)."'");
    if ($result["ok"] && !empty($result["data"])) {
        $keyRow = $result["data"];
        echo "[OK]: Key accepted for ".$keyRow["name"]." (".$keyRow["email"].")\n";

        $archivePath = msv_store_file($_FILES["file"]["tmp_name"], "zip", $moduleName.".zip",TABLE_REPOSITORY);
        if (is_numeric($archivePath)) {
            echo "[ERROR]: Module .zip loading error\n";
            return false;
        } else {
            echo "[OK]: Archive upload successful: ".$archivePath.", ".msv_format_size(filesize(UPLOAD_FILES_PATH."/".$archivePath))."\n";
        }

        $configPath = msv_store_file($_FILES["config"]["tmp_name"], "xml", "config.xml",TABLE_REPOSITORY );
        if (is_numeric($configPath)) {
            echo "[ERROR]: Install config loading error\n";
            return false;
        } else {
            echo "[OK]: Config upload successful: ".$configPath.", ".msv_format_size(filesize(UPLOAD_FILES_PATH."/".$configPath))."\n";
        }

        $previewPath = msv_store_pic($_FILES["preview"]["tmp_name"], "jpg","module_preview_".$moduleName, TABLE_REPOSITORY,"preview" );
        if (is_numeric($previewPath)) {
            echo "[ERROR]: Preview loading error\n";
            return false;
        } else {
            echo "[OK]: Preview upload successful: ".$previewPath.", ".msv_format_size(filesize(UPLOAD_FILES_PATH."/".$previewPath))."\n";
        }

        $configXML = simplexml_load_file(UPLOAD_FILES_PATH."/".$configPath);
        $configList = array(
            "param" => array(),
            "dependency" => array(),
            "files" => array(),
        );
        if (property_exists($configXML, "install")) {
            if (property_exists($configXML->install, "param")) {
                foreach ($configXML->install->param as $param) {
                    $attributes = $param->attributes();
                    $name = (string)$attributes["name"];
                    $value = (string)$attributes["value"];

                    $configList["param"][$name] = $value;
                }
            }

            if (property_exists($configXML->install, "dependency")) {
                foreach ($configXML->install->dependency as $param) {
                    $attributes = $param->attributes();
                    $module = (string)$attributes["module"];
                    $version = (string)$attributes["version"];

                    $configList["dependency"][] = array(
                        "module" => $module,
                        "version" => $version,
                    );
                }
            }

            if (property_exists($configXML->install, "file")) {
                $files = array();
                foreach ($configXML->install->file as $param) {
                    $attributes = $param->attributes();
                    $dir = (string)$attributes["dir"];
                    $path = (string)$attributes["path"];

                    $files[] = array(
                        "dir" => $dir,
                        "path" => $path,
                    );
                }
                $configList["files"] = $files;
            }
        }

        if (empty($configList["param"]["version"])) {
            echo "[ERROR]: Module config error: version couldn't be found\n";
            return false;
        } elseif ($configList["param"]["name"] !== $moduleName) {
            echo "[ERROR]: Module config error: wrong module name\n";
            return false;
        } else {
            echo "[OK]: XML install config loaded successfully\n";
        }
        $version = $configList["param"]["version"];

        // move archive to file repository folder
        $fileModulePath = UPLOAD_FILES_PATH."/".$archivePath;
        $zipFilename = UPLOAD_FILES_PATH."/repository/".$moduleName."-".$version.".zip";
        $archivePath = "repository/".$moduleName."-".$version.".zip";
        rename($fileModulePath, $zipFilename);

        // add item to repository
        $item = array(
            "published" => 1,
            "rep" => "main",
            "name" => $moduleName,
            "title" => $configList["param"]["title"],
            "version" => $configList["param"]["version"],
            "date" => $configList["param"]["date"],
            "description" => $configList["param"]["description"],
            "tags" => $configList["param"]["tags"],
            "files" => $configList["files"],
            "source" => $keyRow["name"],
            "author" => $keyRow["email"],
            "archive" => $archivePath,
            "preview" => $previewPath,
        );

        $result = msv_add_repository_module($item);

        if ($result["ok"]) {
            echo "[SUCCESS]: ".$result["msg"]."\n";
            return true;
        } else {
            echo "[ERROR]: ".$result["msg"]."\n";
            return false;
        }
    }

    echo "[ERROR]: key not not recognized\n";
    return false;
}

