<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class EsInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '初始化 es';

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
        $aliasName = SearchBuilder::getIndex(true);

        $currentAlias = current($aliasName);
        $this->info('正在处理索引 ' . $currentAlias);
        if (!ElasticsearchClient::indices()->exists($aliasName)) {
            $this->info("索引不存在，准备创建");
            $this->createIndex();
            $this->info("创建成功，准备初始化数据");
            Artisan::call("es:import", [
                "index" => $currentAlias
            ]);
            $this->info("操作成功");
        } else {
            $this->info("索引已存在，准备删除");
            ElasticsearchClient::indices()->delete($aliasName);
            $this->info("删除成功");
        }

    }

    public function createIndex()
    {
        $params = SearchBuilder::putSettings([
            "number_of_shards"   => 1,
            "number_of_replicas" => 0
        ])
            ->putMapping([
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
                    ]
                ]
            ])
            ->unsetType()
            ->builder();

        try {
            ElasticsearchClient::indices()->create($params);
        } catch (\Exception $exception) {
            $this->error("{$exception->getMessage()}");
        }
    }
}
