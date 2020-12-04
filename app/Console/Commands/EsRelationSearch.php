<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class EsRelationSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:relation-search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'PHP 操作 Elasticsearch 父子关系索引创建、搜索';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        /**----------------------------------------------------
         * 嵌套对象 v.s 父子文档
         *----------------------------------------------------
         *
         *          Nested Object 	Parent / Child
         *
         * 优点 	文档存储在一起，读取性能高 	父子文档可以独立更新
         * 缺点 	更新嵌套的子文档时，需要更新整个文档 	需要额外的内存去维护关系。读取性能相对差
         * 适用场景 	子文档偶尔更新，以查询为主 	子文档更新频繁
         *
         */

        $params = SearchBuilder::connection("relation")
            ->setParams([
                "match_all" => new \stdClass()
            ])
            ->paginate(1, 100)
            ->builder();

        /**
         * 通过父文档查询子文档
         * 注意：通过父文档查询子文档的时候不会返回父文档的数据
         */
        $params = SearchBuilder::connection("relation")
            ->setBody([
                "query" => [
                    "has_parent" => [ // 连接父文档查询
                        "parent_type" => "parent",  // 指定查询的父文档
                        "query" => [
                            "match" => [
                                "username" => "我的意中人是个盖世英雄"
                            ]
                        ]
                    ]
                ]
            ])
            ->builder();

        /**
         * 通过子文档查询父文档
         * 注意：通过子文档查询父文档的时候不会返回子文档的数据
         */
        $params = SearchBuilder::connection("relation")
            ->setBody([
                "query" => [
                    "has_child" => [ // 连接父文档查询
                        "type" => "child",  // 指定查询的父文档  注意：这里是 type 和父级文档搜索有区别
                        "query" => [
                            "match" => [
                                "comment" => "666"
                            ]
                        ]
                    ]
                ]
            ])
            ->builder();

        /**
         * 根据父级 ID 获取所有子集的文档
         */
        $params = SearchBuilder::connection("relation")
            ->setBody([
                "query" => [
                    "parent_id" => [
                        "type" => "child",
                        "id" => 3
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::connection("relation")->search($params));
    }

}
