<?php

function RepositoryLoad() {


    $resultQuery = db_get_list(TABLE_REPOSITORY, "", "`name` asc");
    if ($resultQuery["ok"]) {
        // assign data to template
        msv_assign_data("repository_list", $resultQuery["data"]);
    }


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
            $zipFileUrl = "repository/".$moduleName."-".$versionLocal.".zip";

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

            $item = array(
                "published" => 1,
                "author" => $obj->author,
                "rep" => "main",
                "name" => $moduleName,
                "title" => $obj->title,
                "version" => $obj->version,
                "date" => date("Y-m-d H:i:s", strtotime($obj->date)),
                "description" => $obj->description,
                "date_build" => date("Y-m-d H:i:s"),
                "source" => "local",
                "archive" => $zipFileUrl,
                "preview" => $obj->preview,
                "tags" => $obj->tags,
            );

            $result = msv_add_repository_module($item);
            if ($result["ok"]) {
                msv_message_ok("Module ".$moduleName.": ".$result["msg"]);
            } else {
                msv_message_error("Module ".$moduleName.": ".$result["msg"]);
            }
        }
    }

    msv_log("RepositoryBuild done");
    msv_message_ok("Done!");
}

function RepositoryList($module) {
    $repName = $module->website->requestUrlMatch[1];
    if (empty($repName)) {
        msv_output404();
    } else {
        $result = array(
            "ok" => false,
            "data" => array(),
            "msg" => "",
        );

        if ($repName === "main") {
            $modulesList = $modulesListLocal = array();
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
