<?php
/**
 * Created by PhpStorm.
 * User: james.xue
 * Date: 2020/11/28
 * Time: 17:25
 */

namespace App\Console\Commands;


class Indices
{
    static public function getSettings()
    {
        return [
            "number_of_shards"   => 1,
            "number_of_replicas" => 0
        ];
    }

    static public function getProperties()
    {
        return [
            "properties" => [
                "name"    => [
                    "type"            => "text",
                    "analyzer"        => "ik_max_word",
                    "search_analyzer" => "ik_max_word"
                ],
                "content" => [
                    "type"            => "text",
                    "analyzer"        => "ik_max_word",
                    "search_analyzer" => "ik_max_word"
                ],
                "label"   => [
                    "type" => "keyword",
                ],
                "created_at" => [
                    "type" => "date",
                    "format" => "yyyy-MM-dd HH:mm:ss" // 主要这里不指定日期格式会报错，不支持这个日期类型，默认:"strict_date_optional_time||epoch_millis"
                ],
                "view" => [
                    "type" => "integer"
                ]
            ]
        ];
    }
}