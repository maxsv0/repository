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

    if (empty($key)) {
        echo "Error: key not found";
        return false;
    }

    $result = db_get(TABLE_REPOSITORY_KEYS, " `key` = '".db_escape($key)."'");
    if ($result["ok"] && !empty($result["data"])) {
        $module = $_REQUEST["module"];
        // TODO:
        // clean $module

        // TODO process filelist
        var_dump($_REQUEST["filelist"]);


        $archivePath = msv_store_file($_FILES["file"]["tmp_name"], "zip");
        $previewPath = msv_store_pic($_FILES["preview"]["tmp_name"], "jpg","module_preview_".$module, TABLE_REPOSITORY,"preview" );

        // change $archivePath
        $fileModulePath = UPLOAD_FILES_PATH."/".$archivePath;
        $zipFilename = UPLOAD_FILES_PATH."/repository/".$module."-".$_REQUEST["version"].".zip";
        $archivePath = "repository/".$module."-".$_REQUEST["version"].".zip";
        rename($fileModulePath, $zipFilename);

        $keyRow = $result["data"];

        // add item to repository
        $item = array(
            "published" => 1,
            "rep" => "main",
            "name" => $module,
            "title" => $_REQUEST["title"],
            "version" => $_REQUEST["version"],
            "date" => $_REQUEST["released"],
            "description" => $_REQUEST["description"],
            "source" => $keyRow["name"],
            "author" => $keyRow["email"],
            "archive" => $archivePath,
            "preview" => $previewPath,
            "tags" => $_REQUEST["tags"],
        );

        $result = msv_add_repository_module($item);

        if ($result["ok"]) {
            echo "SUCCESS: ".$result["msg"];
        } else {
            echo "ERROR: ".$result["msg"];
        }

        echo "\n";

        return true;
    }

    echo "ERROR: key not found";
    return false;
}

