<?php

namespace App\Console\Commands\Data;

use Illuminate\Console\Command;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class SearchData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:oauth {method}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $this->{$this->argument("method")}();
    }

    protected function update()
    {
        $params = SearchBuilder::connection("oauth")
            ->setKey(3)
            ->setBody([
                "doc" => [
                    "integral" => 1
                ],
            ])
            ->builder();

        dd(ElasticsearchClient::connection('oauth')->update($params));
    }

    /**
     * Notes: 搜索
     * Date: 2020/12/2 17:40
     */
    protected function search()
    {
        $params = SearchBuilder::connection("oauth")
            ->setParams([
                // a 搜索 original 的字段
//                "nested" => [
//                    "path"  => "original",
                    // 这里缺少 query、bool 都报错
//                    "query" => [
//                        "bool" => [
//                            "must" => [
//                                "match" => [
////                                    "original.id" => 20943661 // 下面的 A
//                                    "original.name" => "无言的自语" // 下面的 B
//                                ]
//                            ]
//                        ]
//                    ]

                    // 报错示例、报错示例、报错示例 重要的事情说三遍（缺少 query）
                    /*"bool" => [
                        'filter' => [
                            'term' => [
                                'original.id' => 20943661,
                            ],
                        ],
                    ]*/

//                ]
                // b
                "match_all" => new \stdClass()

                // A 使用 copy_to 字段搜索，和上面 a 方法结果一样
//                "bool" => [
//                    "filter" => [
//                        "term" => [
//                            "original_id" => 20943661
//                        ]
//                    ]
//                ]

                // B
//                "bool" => [
//                    "must" => [
//                        "match" => [
//                            "original_name" => "无言的自语"
//                        ]
//                    ]
//                ]

                /*"bool" => [
                    "must" => [
                         // 测试静态映射
//                        "match" => [
//                            "is_admin" => 0
//                        ]
                        // 测试时间搜索范围
                        "range" => [
                            "created_at" => [
                                "from" => "2019-08-20 23:04:55",
                                "to" => "2019-09-26 15:29:02"
                            ]
                        ]
                    ]
                ]*/

//                "query_string" => [
//                    // default_field 默认字段，如果没有值则是全部
//                    "query" => "(iyangboy OR eachdemo)",
////                    "fields" => ["name", "username"]
//                ]

            ])
//            ->setAggregations([
//                "count" => [
//                    "value_count" => [
//                        "field" => "integral"
//                    ]
//                ]
//            ])
            ->orderBy([
                "_script" => [
                    "type" => "number",
                    "script" => [
                        "lang" => "painless",
                        "source" => "doc['integral'].value * params.num",
                        "params" => [
                            "num" => 10
                        ],
                    ],
                    "order" => "desc"
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::connection("oauth")->search($params));
    }

    /**
     * Notes: 查询所有文档总数
     * Date: 2020/12/2 17:40
     */
    protected function count()
    {
        $params = SearchBuilder::connection("oauth")
//            ->setBody(["aggs" => ["count" => ["value_count" => ["field" => "integral"]]]]) // 获取文档总数
            ->setBody(["aggs" => ["count" => ["terms" => ["field" => "integral"]]]]) // 获取桶
            ->builder();

        dd(ElasticsearchClient::connection("oauth")->search($params));
    }
}
