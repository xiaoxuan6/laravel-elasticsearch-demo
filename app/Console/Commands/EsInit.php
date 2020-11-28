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
            $this->createIndex($currentAlias);
            $this->info("创建成功，准备初始化数据");
            Artisan::call("es:import", [
                "index" => $currentAlias
            ]);
            $this->info("操作成功");
            die;
        }

        try {
            $this->info('索引存在，准备更新');
            $this->updateIndex($aliasName);
        } catch (\Exception $exception) {
            $this->warn('更新失败，准备删除');
            $this->delete($aliasName);
        }

        $this->info($currentAlias . ' 操作成功');

    }

    public function createIndex($aliasName)
    {
        $params = SearchBuilder::setIndex($aliasName . "_1000")
            ->putSettings(Indices::getSettings())
            ->putMapping(Indices::getProperties())
            ->setAliases([$aliasName => new \stdClass()])
//            ->setAttribute(["body.aliases" => [$aliasName => new \stdClass()]])
            ->unsetType()// 版本7之后不再支持type
            ->builder();

        try {
            ElasticsearchClient::indices()->create($params);
        } catch (\Exception $exception) {
            $this->error("{$exception->getMessage()}");
        }
    }

    /**
     * Notes: 修改索引
     * Warning：这里不能直接修改原来的字段类型，也不能缺少字段，只能添加新字段
     * Date: 2020/11/28 11:38
     */
    public function updateIndex($aliasName)
    {
        ElasticsearchClient::indices()->close($aliasName);

        $params = SearchBuilder::putMapping(Indices::getProperties(), true)
            ->unsetType()
//            ->setAttribute(["include_type_name" => true]) // 版本7之后不支持type 如何使用设置该值
            ->builder();

        ElasticsearchClient::indices()->putMapping($params);

        ElasticsearchClient::indices()->open($aliasName);
    }

    public function delete($aliasName)
    {
        // 获取索引别名（创建是指定了别名）
        $aliasName = ElasticsearchClient::indices()->getAliases($aliasName);

        // 获取第一个索引名
        $indexName = current(array_keys($aliasName));

        ElasticsearchClient::indices()->delete(["index" => $indexName]);
    }
}
