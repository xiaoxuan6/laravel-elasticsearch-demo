<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class EsSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:search {method}';

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

    protected function search()
    {
        $params = SearchBuilder::connection("elastic")->setParams([
            /*"bool" => [
                "must" => [
                    "match" => [
                        "name" => "模型 dingo"
                        // 短语搜索 不进行分词
//                        "name" => [
//                            "query" => "模型 dingo",
//                            "operator" => "and" // 这个会当成短语搜索，不进行分词和 match_phrase 结果相同
//                        ]
                    ]
                    // 短语搜索
//                    "match_phrase" => [
//                        "name" => "模型 dingo"
//                    ]
                    // 多字段
//                    "multi_match" => [
//                        "query"  => "laravel",
//                        "fields" => ["name", "label"]
//                    ]

//                    "match" => [
//                        "label" => "laravel" // 搜索報錯，因为添加映射的时候设置为不可搜索
//                    ]
                ]
            ]*/
            "match_all" => (object)[]
        ])
            ->setAggregations([
                /**
                 * 聚合类型：
                 *      最大 max、最小 min、平均 avg、总和 sum
                 *      扩展数据 extended_stats (包含最大、最小、平均、总和、总文档数等)
                 *      统计 stats (值包含总和、最大、最小、平均、总文档数)
                 *      文档总数 value_count
                 *      过滤器聚合
                 */
                // 平均聚合
//                "avg_view" => [
                    // 普通
//                    "avg" => [
//                        "field" => "view",
//                        // missing 参数定义应如何处理缺少值的文档。默认情况下，它们将被忽略，但也可以将它们视为具有值。
//                        "missing" => 0,
//                    ],
                    // 使用脚本
//                    "avg" => [
//                        "script" => [
//                            // 这里使用文档中的数据
//                              "lang" => "painless",
//                              "source" => "doc['view'].value",
                              // 值脚本
//                              "lang" => "painless",
//                              "source" => "doc['view'].value * params.num",
//                              "params" => [
//                                  "num" => 2
//                              ]
//                        ]
//                    ]
//                ],

                // 扩展数据聚合(包含最大、最小、平均等)
//                "view_stats" => [
//                    "extended_stats" => [
//                        "field" => "view"
//                    ]
//                ]

                // 过滤器聚合
                "view_stats" => [
                    // 搜索 name 中包含 ajax 的，然后通过聚合求平均值（这可以省略上面的 setParams 方法）
                    // 如何使用下面 aggs 方法，结果中只有筛选中的一个文档，这个默认去不文档，聚合只有符合条件的才计算
                    "filter" => [
                        "match" => [
                            "name" => "ajax"
                        ]
                    ],
                    "aggs"   => [
                        "avg_view" => [
                            "avg" => [
                                "field" => "view"
                            ]
                        ]
                    ]
                ]

            ])
//            ->setSource("view")
            ->ignore([400, 404])
            ->orderBy([
                "_script" => [
                    "type" => "number",
                    "script" => [
                        "lang" => "painless",
                        "source" => "doc['view'].value * 10",
                        "params" => [
                            "num" => 10
                        ],
                    ],
                    "order" => "desc"
                ]
            ])
            ->paginate(1, 100)// 分页
            ->builder();

        $result = ElasticsearchClient::search($params);

        dd($result);
    }

    /**
     * Notes: 过滤器聚合
     * Date: 2020/12/2 11:17
     */
    protected function aggs()
    {
        $params = SearchBuilder::connection("elastic")
            ->setParams([
                "bool" => [
                    "filter" => [
                        "match" => [
                            "name" => "ajax"
                        ]
                    ]
                ]
            ])
            ->setAggregations([
                "view_stats" => [
                    "avg" => [
                        "field" => "view"
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::connection("elastic")->search($params));
    }

    /**
     * Notes: 添加文档
     * Date: 2020/12/1 11:34
     */
    protected function createIndex()
    {
        $params = SearchBuilder::connection("elastic")->setKey(1000)->builder();
        dd(ElasticsearchClient::index($params));
    }

    /**
     * Notes: 获取某个文档
     * Date: 2020/12/1 11:37
     */
    protected function getIndex()
    {
        /**
         * ignore()：状态码，默认404
         *      搜索文档如果没有该文档将报错信息已数组的形式输出
         */
        $params = SearchBuilder::connection("elastic")->setKey(1000)/*->ignore()*/->unsetBody()->builder();

        dd(ElasticsearchClient::get($params));
    }

    /**
     * Notes: 修改文档
     * Date: 2020/12/1 15:46
     */
    protected function updateIndex()
    {
        $params = SearchBuilder::connection("elastic")
            ->setKey(23)
            ->setBody([
                // A、正常更新
                "doc" => [
                    "integral" => 1
                ],
                // 如果doc中定义的部分与现在的文档相同，则默认不会执行任何动作。设置detect_noop=false，就会无视是否修改，强制合并到现有的文档
                "detect_noop" => false,
                
                // B、使用脚本
                "script" => [
                    // 将view 中的值增加1
                    "source" => "ctx._source.view += params.time",
                    "lang" => "painless",
                    "params" => [
                        "time" => 1
                    ]
                ],
                // 文档不存在添加，否则修改
                "upsert" => [
                    "view" => 1
                ]
            ])
            ->unsetType()
            ->builder();

        dd(ElasticsearchClient::update($params));
    }

    /**
     * Notes:删除文档
     * Date: 2020/12/1 11:38
     */
    protected function deleteIndex()
    {
        $params = SearchBuilder::connection("elastic")->setKey(1000)->unsetBody()->builder();

        dd(ElasticsearchClient::delete($params));
    }

    /**
     * Notes: 是否存在 source 字段
     * Date: 2020/12/1 11:25
     */
    protected function existsSource()
    {
        $params = SearchBuilder::connection("elastic")->setKey(1000)->unsetBody()->builder();

        dd(ElasticsearchClient::existsSource($params));
    }

    /**
     * Notes: 获取某个文章中的 source
     * Date: 2020/12/2 17:20
     */
    protected function getSource()
    {
        $params = SearchBuilder::connection("elastic")->setKey(2)->unsetBody()->builder();

        dd(ElasticsearchClient::getSource($params));
    }
    
    /**
     * Notes: 批量搜索修改文档内容
     * Date: 2020/12/1 11:50
     */
    protected function updateByQuery()
    {
        $params = SearchBuilder::connection("elastic")
            ->setBody([
                "query" => [
                    "match_all" => new \stdClass()
                ],
                "script" => [
                    /**
                     * 修改文档内容
                     */
                    // a
//                    "source" => "ctx._source.views++", // 将文档中 views 的值改为0
//                    "source" => "ctx._source['area']='无'", // 中文

                    // b
//                    "source" => "ctx._source.views += params.num",
//                    "params" => [
//                        "num" => 1,
//                    ],

                    /**
                     * 给文档添加字段和内容
                     */
                    // a、直接添加
//                    "lang" => "painless",
//                    "inline" => "ctx._source.create_time = '20201201'", // 新增字段 create_time
                    // b、给数组添加
//                    "source" => "ctx._source.tags.add(params.tag)",
//                    "params" => [
//                        "tag" => "test"
//                    ]

                    /**
                     * 移除字段
                     */
//                    "source" => "ctx._source.remove('views')"

                ]
            ])
            ->ignore(400)
            ->builder();

        dd(ElasticsearchClient::updateByQuery($params));
    }

    /**
     * Notes: 批量删除
     * Date: 2020/12/2 13:40
     */
    protected function deleteByQuery()
    {
        $params = SearchBuilder::connection("elastic")
            ->setParams([
                "bool" => [
                    "filter" => [
                        "term" => [
                            "view" => 1
                        ]
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::connection("elastic")->deleteByQuery($params));
    }
}
