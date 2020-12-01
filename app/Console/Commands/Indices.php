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
                    // index，可用于设置字段是否被索引，默认为true，false即为不可搜索。
                    "index" => false
                ],
                "created_at" => [
                    "type" => "date",
                    "format" => "yyyy-MM-dd HH:mm:ss"
                    // 主要这里不指定日期格式会报错，不支持这个日期类型，默认:"strict_date_optional_time||epoch_millis"
                    /**
                     * @see https://segmentfault.com/a/1190000016296983
                     */
                ],
                "view" => [
                    "type" => "integer"
                ]
            ]
        ];
    }
}
