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
        $params = SearchBuilder::setIndex("demo_index_20201129")->setParams([
/*            "bool" => [
                "must" => [
//                    "match" => [
//                        "name" => [
//                            "query" => "模型 dingo",
//                            "operator" => "and" // 这个会当成短语搜索，不进行分词和 match_phrase 结果相同
//                        ]
//                    ]
                    // 短语搜索
//                    "match_phrase" => [
//                        "name" => "模型 dingo"
//                    ]
                    // 多字段
                    "multi_match" => [
                        "query"  => "laravel",
                        "fields" => ["name", "label"]
                    ]
                ]
            ]*/
            "match_all" => (object)[]
        ])
            ->paginate(1, 100)// 分页
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    /**
     * Notes: 添加文档
     * Date: 2020/12/1 11:34
     */
    protected function createIndex()
    {
        $params = SearchBuilder::setIndex("demo_index_20201129")->setKey(1000)->builder();
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
        $params = SearchBuilder::setIndex("demo_index_20201129")->setKey(1000)/*->ignore()*/->unsetBody()->builder();

        dd(ElasticsearchClient::get($params));
    }

    /**
     * Notes: 修改文档
     * Date: 2020/12/1 15:46
     */
    protected function updateIndex()
    {
        $params = SearchBuilder::setIndex("demo_index_20201129")
            ->setKey(23)
            ->setBody([
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
        $params = SearchBuilder::setIndex("demo_index_20201129")->setKey(1000)->unsetBody()->builder();

        dd(ElasticsearchClient::delete($params));
    }

    /**
     * Notes: 是否存在 source 字段
     * Date: 2020/12/1 11:25
     */
    protected function existsSource()
    {
        $params = SearchBuilder::setIndex("demo_index_20201129")->setKey(1000)->unsetBody()->builder();

        dd(ElasticsearchClient::existsSource($params));
    }

}
