<?php

function msv_add_repository_module($row, $options = array()) {
    $result = array(
        "ok" => false,
        "data" => array(),
        "msg" => "",
    );

    // check required fields
    if (empty($row["rep"])) {
        $result["msg"] = _t("msg.repository.norep");
        return $result;
    }
    if (empty($row["name"])) {
        $result["msg"] = _t("msg.repository.noname");
        return $result;
    }
    if (empty($row["title"])) {
        $result["msg"] = _t("msg.repository.notitle");
        return $result;
    }
    if (empty($row["version"])) {
        $result["msg"] = _t("msg.repository.noversion");
        return $result;
    }
    if (empty($row["date"])) {
        $result["msg"] = _t("msg.repository.nodate");
        return $result;
    }
    if (empty($row["source"])) {
        $result["msg"] = _t("msg.repository.nosource");
        return $result;
    }
    if (empty($row["archive"])) {
        $result["msg"] = _t("msg.repository.noarchive");
        return $result;
    }
    if (empty($row["files"]) || !is_array($row["files"])) {
        $result["msg"] = _t("msg.repository.nofiles");
        return $result;
    }
    if (empty($row["preview"])) {
        $result["msg"] = _t("msg.repository.nopreview");
        return $result;
    }

    // set defaults
    if (empty($row["published"])) {
        $row["published"] = 1;
    } else {
        $row["published"] = (int)$row["published"];
    }
    if (empty($row["date_build"])) {
        $row["date_build"] = date("Y-m-d H:i:s");
    }

    // set empty fields
    if (empty($row["description"])) $row["description"] = "";
    if (empty($row["tags"])) $row["tags"] = "";

    // check this module in repository before adding
    $resultModule = db_get(TABLE_REPOSITORY, " `rep` = '".db_escape($row["rep"])."' and `name` = '".db_escape($row["name"])."'");
    if ($resultModule["ok"] && !empty($resultModule["data"])) {
        $versionLocal = $resultModule["data"]["version"];

        if (version_compare($versionLocal, $row["version"]) >= 0) {
            $result["msg"] = "Local version $versionLocal, can't update to ".$row["version"];
            return $result;
        }

        db_delete(TABLE_REPOSITORY, " `id` = ".$resultModule["data"]["id"]);
    }

    // add item to repository
    $result = db_add(TABLE_REPOSITORY, $row, "*");
    if ($result["ok"]) {
        $result["msg"] = $row["name"]." v.".$row["version"]." "._t("msg.repository.saved").". ID: ".$result["insert_id"];

        // add blog articles
        $articleText = "<p>".$row["title"]." v.".$row["version"]." was uploaded by <b>".$row["author"]."</b></p>";
        $articleText .= "<h4>List of the:</h4>";
        $articleText .= "<div class='well'>";
        foreach ($row["files"] as $fileInfo) {
            if ($fileInfo["dir"] === "abs") {
                $local_path = $fileInfo["path"];
            } elseif ($fileInfo["dir"] === "include") {
                $local_path = LOCAL_INCLUDE."/".$fileInfo["path"];
            } elseif ($fileInfo["dir"] === "module") {
                $local_path = LOCAL_MODULE."/".$fileInfo["path"];
            } elseif ($fileInfo["dir"] === "template") {
                $local_path = LOCAL_TEMPLATE."/".$fileInfo["path"];
            } elseif ($fileInfo["dir"] === "content") {
                $local_path = CONTENT_URL."/".$fileInfo["path"];
            }
            if (substr($local_path, 0, 1) === "/") {
                $local_path = substr($local_path, 1);
            }

            $articleText .= "<p>".$local_path."</p>";
        }
        $articleText .= "</div>";

        $itemBlog = array(
            "sticked" => 0,
            "email" => "support@sitograph.com",
            "url" => $row["name"]."-v-".str_replace(".", "-", $row["version"])."-released",
            "title" => $row["title"]." v.".$row["version"]." released",
            "description" => $row["description"],
            "text" => $articleText,
            "pic" => $row["preview"],
            "pic_preview" => $row["preview"],
        );
        api_blog_add($itemBlog, array("LoadPictures"));

    }

    return $result;
}


function msv_repository_module($repName, $moduleName) {

    if (empty($repName) || empty($moduleName)) {
        msv_output404();
    } else {
        $resultModule = db_get(TABLE_REPOSITORY,"`rep` = '".db_escape($repName)."' and `name` = '".db_escape($moduleName)."'");
        if (!$resultModule["ok"]) {
            msv_output404();
        }

        $fileModule = $resultModule["data"]["archive"];
        $fileModulePath = UPLOAD_FILES_PATH."/".$fileModule;
        if (!file_exists($fileModulePath)) {
            msv_output404();
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
        readfile($fileModulePath);
        exit;
    }

    msv_output404();
}
