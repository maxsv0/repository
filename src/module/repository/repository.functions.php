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

    // check this module in repository before adding
    $resultModule = db_get(TABLE_REPOSITORY, " `rep` = '".db_escape($row["rep"])."' and `name` = '".db_escape($row["name"])."'");
    if ($resultModule["ok"] && !empty($resultModule["data"])) {
        $versionLocal = $resultModule["data"]["version"];

        if (version_compare($versionLocal, $row["version"]) > 0) {
            $result["msg"] = "ERROR: local version $versionLocal, can't update to ".$row["version"];
            return $result;
        }

        db_delete(TABLE_REPOSITORY, " `id` = ".$resultModule["data"]["id"]);
    }

    // add item to repository
    return $result = db_add(TABLE_REPOSITORY, $row, "*");
}