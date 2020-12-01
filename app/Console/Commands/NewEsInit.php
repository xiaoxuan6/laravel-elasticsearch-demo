<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class NewEsInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:new-init {--online}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $newIndex, $index, $aliasIndex;

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
        if($this->option("online")) {
            $this->index = "demo_index_20201129";
            $this->newIndex = "demo_index_1000";
            $this->aliasIndex = "alias_demo_index_1000";
        } else {
            $this->index = "demo_index_1000";
            $this->newIndex = "demo_index_20201129";
            $this->aliasIndex = "alias_demo_index_20201129";
        }

        if (ElasticsearchClient::indices()->exists(["index" => $this->newIndex])) {
            $this->info("索引 {$this->newIndex} 已存在，准备删除");
            $this->delete();
            $this->info("删除成功，准备重新创建");
            $this->createIndex();
            $this->info("索引 {$this->newIndex} 创建成功，准备迁移数据");

            try {
                $this->reindex();
            } catch (\Exception $exception) {
                dd($exception->getMessage());
            }

            $this->info("数据迁移成功，准备修改别名");
            $this->updateAlias();
            $this->info("修改成功，准备删除旧索引");
            $this->delete($this->index);
            $this->info("删除成功");
            $this->info("操作完成");
            die;
        } else {
            $this->info("索引 {$this->newIndex} 不已存在，准备创建");
            $this->createIndex();
            $this->info("创建成功，准备迁移数据");

            try {
                $this->reindex();
            } catch (\Exception $exception) {
                dd($exception->getMessage());
            }

            $this->info("数据迁移成功，准备修改别名");
            $this->updateAlias();
            $this->info("修改成功，准备删除旧索引");
            $this->delete($this->index);
            $this->info("删除成功");
            $this->info("索引 {$this->newIndex} 操作成功");
        }

        dd('ok');

    }

    /**
     * Notes: 创建索引
     * Date: 2020/11/29 17:09
     */
    public function createIndex()
    {
        $params = SearchBuilder::setIndex($this->newIndex)
            ->putSettings(Indices::getSettings())
            ->putMapping(Indices::getProperties())
            ->builder();

        $result = ElasticsearchClient::indices()->create($params);

//        dd($result);
    }

    /**
     * Notes: 迁移数据
     * Date: 2020/11/29 17:10
     */
    public function reindex()
    {
        $params = [
            "body" => [
                "max_docs" => "20", // 最大同步文档数据
                "source" => [
                    "index"   => $this->index,
                    // 搜索条件、对满足query条件的数据进行reindex操作
//                    "query"   => [
//                        "match" => [
//                            "name" => "模型dingo"
//                        ],
//                        "size" => 100, //满足条件的100条
//                    ],
                    // 数据迁移，保留的字段
//                    "_source" => ["id", "name", "content"]

                    /**
                     * 更多使用方法：
                     * @see https://blog.csdn.net/ctwy291314/article/details/82734667
                     */
                ],
                "dest"   => [
                    "index" => $this->newIndex
                ],
                "script" => [
                    // 修改字段名称
//                    "source" => "ctx._source.label_name = ctx._source.remove(\"label\")"
                    // 修改字段内容
                    "source" => "if(ctx._source.view < 1000) {ctx._source.view++; ctx._source.views = ctx._source.view}"
                ]
            ]
        ];

        $result = ElasticsearchClient::reindex($params);

//        dd($result);
    }

    /**
     * Notes: 修改别名
     * Date: 2020/11/29 17:36
     */
    public function updateAlias()
    {
        $params = [
            "add" => [
                "index" => $this->newIndex,
                "alias" => $this->aliasIndex,
            ]
        ];

        ElasticsearchClient::indices()->updateAliases(SearchBuilder::updateAliases($params));
    }

    /**
     * Notes: 删除索引
     * Date: 2020/11/29 17:10
     */
    public function delete($index = null)
    {
        $index = ["index" => $index ?? $this->newIndex];

        ElasticsearchClient::ind
