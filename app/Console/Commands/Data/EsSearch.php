<?php

namespace App\Console\Commands\Data;

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
    protected $signature = 'es:data {method}';

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

    /**
     * Notes: 过滤桶（查询所有文档，过滤积分大于 100，然后求平均值）
     * Date: 2020/12/7 16:48
     */
    public function filter()
    {
        $params = SearchBuilder::connection("oauth")
            ->setParams([
                "match_all" => new \stdClass()
            ])
            ->setAggregations([
                "integral" => [
                    "filter" => [ //使用 过滤 桶在 查询 范围基础上应用过滤器。
                        "range" => [
                            "integral" => [
                                "gte" => 100
                            ]
                        ]
                    ],
                    "aggs" => [
                        "avg_integral" => [
                            "avg" => [
                                "field" => "integral"
                            ]
                        ]
                    ]
                ],
            ])
            ->builder();

        dd(ElasticsearchClient::connection("oauth")->search($params));
    }

    /****************************************************************************
    |
    | 警告：性能考量
    | 只有当你需要对搜索结果和聚合使用不同的过滤方式时才考虑使用post_filter。有时一些用户会直接在常规搜索中使用post_filter。
    | 不要这样做！post_filter会在查询之后才会被执行，因此会失去过滤在性能上帮助(比如缓存)。
    | post_filter应该只和聚合一起使用，并且仅当你使用了不同的过滤条件时。
    |
    ****************************************************************************/
    
    /**
     * Notes: 后过滤器
     *      这一步发生在执行查询之后，因此聚合是不会被影响的，只会改变查询结果中的 hits
     *
     * Date: 2020/12/7 16:58
     */
    public function postFilter()
    {
        $params = SearchBuilder::connection("oauth")
            ->setParams([
                "match_all" => new \stdClass()
            ])
            // 通过聚合得到所有的 积分
            ->setAggregations([
                "integral_all" => [
                    "terms" => [
                        "field" => "integral"
                    ]
                ]
            ])
            // 然后对搜索结果进行处理
            ->setAttribute([
                "body.post_filter" => [
//                    "term" => [
//                        "integral" => 110
//                    ]
                    "range" => [
                        "integral" => [
                            "gte" => 100
                        ]
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::connection("oauth")->search($params));
    }
}
