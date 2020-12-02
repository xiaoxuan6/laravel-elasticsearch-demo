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
    /**
     * Notes: 映射
     *
     * dynamic：
     *          动态映射（dynamic：true）：动态添加新的字段（或缺省）。
     *          静态映射（dynamic：false）：忽略新的字段。在原有的映射基础上，当有新的字段时，不会主动的添加新的映射关系，只作为查询结果出现在查询中。（不在映射中设置的字段，不支持搜索）
     *          严格模式（dynamic： strict）：如果遇到新的字段，就抛出异常。
     */
    static public function getProperties()
    {
        return [
            "dynamic" => false,
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
