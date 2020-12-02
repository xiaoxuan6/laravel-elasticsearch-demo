<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class EsIndices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:indices {method}';

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
     * Notes: 刪除索引別名
     * Date: 2020/12/1 13:41
     */
    protected function updateAliases()
    {
        $params = SearchBuilder::updateAliases([
            "remove" => [
                "index" => "demo_index_20201129",
                "alias" => "alias_demo_index_20201129"
            ]
        ]);

        dd(ElasticsearchClient::indices()->updateAliases($params));
    }
   
   /**
     * Notes: 获取某个字段的映射类型
     * Date: 2020/12/2 11:54
     */
    protected function getFieldMapping()
    {
        $params = [
            "index" => "demo_index_1000",
            // 单个字段
            "fields" => "view",
            // 多个字段
//            "fields" => ["view", "label"]
        ];

        dd(ElasticsearchClient::connection("elastic")->indices()->getFieldMapping($params));
    }
}
