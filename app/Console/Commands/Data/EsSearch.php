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
     * Notes: 过滤桶
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
}
