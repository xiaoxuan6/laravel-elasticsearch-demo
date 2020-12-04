<?php

namespace App\Console\Commands;

use App\Model\Discuss;
use App\Model\Oauth;
use Illuminate\Console\Command;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class EsRelation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:relation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '父子文档';
    /**
     * @see  https://www.elastic.co/guide/cn/elasticsearch/guide/current/has-child.html
     */

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
        rebuild:
        $index = SearchBuilder::connection("relation")->getIndex();

        if (!ElasticsearchClient::connection("relation")->indices()->exists(["index" => $index])) {
            $this->info("索引 {$index} 不存在，准备创建");
            $this->createIndex($index . '_0');
            $this->info("创建成功，准备添加数据");

            try {
                $this->rebuild($index);
            } catch (\Exception $exception) {
                dd($exception->getMessage());
            }

            $this->info("数据添加成功");
            $this->info("操作成功");
            die;
        }

        $this->warn("索引 {$index} 已存在");

        if ($this->confirm("是否重新创建？")) {
            $index = ElasticsearchClient::connection("relation")->indices()->getAliases(["index" => $index]);

            ElasticsearchClient::connection("relation")->indices()->delete(["index" => array_keys($index)[0]]);
            $this->info("删除成功，准备重建");

            goto rebuild;
        }
    }

    protected function createIndex($indexAlias)
    {
        $params = SearchBuilder::connection("relation")
            ->putSettings([
                "number_of_shards"   => 1,
                "number_of_replicas" => 0
            ])
            ->putMapping([
                "dynamic"    => false,
                "properties" => [
                    // 主表字段
                    "username"   => [
                        "type" => "keyword"
                    ],
                    "github_id"  => [
                        "type" => "long"
                    ],
                    "original"   => [
                        "type"       => "nested",
                        "properties" => [
                            "id"    => [
                                "type"    => "keyword",
                                "copy_to" => "original_id"
                            ],
                            "name"  => [
                                "type"            => "text",
                                "analyzer"        => "ik_max_word",
                                "search_analyzer" => "ik_max_word",
                                "copy_to"         => "original_name"
                            ],
                            "login" => [
                                "type"    => "keyword",
                                "copy_to" => "original_login"
                            ]
                        ]
                    ],
                    "integral"   => [
                        "type" => "integer"
                    ],
                    "created_at" => [
                        "type"   => "date",
                        "format" => "yyyy-MM-dd HH:mm:ss"
                    ],
                    // 关联表字段
                    "oauth_id"   => [
                        "type" => "integer"
                    ],
                    "comment"    => [
                        "type"            => "text",
                        "analyzer"        => "ik_max_word",
                        "search_analyzer" => "ik_max_word",
                    ],
                    // 重点
                    "relation"   => [
                        "type"      => "join",
                        "relations" => [
                            "parent" => "child" // 一父一子
                        ]
                    ]
                ]
            ])
            ->setAliases([$indexAlias => new \stdClass()])
            ->builder();

        ElasticsearchClient::connection()->indices()->create($params);
    }

    protected function rebuild($index)
    {
        // 批量添加 父级文档
        Oauth::query()
            ->chunkById(10, function ($oauths) use ($index) {

                $this->info(sprintf("正在同步用户 ID 范围在 %s 至 %s 的数据", $oauths->first()->id, $oauths->last()->id));

                $params = ["body" => []];
                foreach ($oauths as $oauth) {
                    $params["body"][] = [
                        "create" => [
                            "_index" => $index,
                            "_id"    => $oauth->getKey(),
                        ],
                    ];

                    $params["body"][] = array_merge($oauth->toArray(), ["relation" => ["name" => "parent"]]); // 关键在于 relation, 父级文档和普通文档区别在于添加了标识 relation 是父级
                }

                ElasticsearchClient::connection("relation")->bulk($params);
            });

        /**
         * 批量添加 子集文档
         * @see https://blog.csdn.net/csdn_fan321/article/details/103691585
         */
        Discuss::query()
            ->chunkById(10, function ($discuss) use ($index) {

                $this->info(sprintf("正在同步评论 ID 范围在 %s 至 %s 的数据", $discuss->first()->id, $discuss->last()->id));

                $params = SearchBuilder::connection("relation");

                foreach ($discuss as $item) {
                    // name 指定文档为子文档类型，把值设置成上面定义好的child
                    // parent 指定关联父文档的文档id
                    $data = array_merge($item->toArray(), ["relation" => ["name" => "child", "parent" => $item->oauth_id]]);

                    // 'routing' => 1 指定该文档存储的分片，设置子文档的必须项，必须和父文档存储在同一个分片中
                    $atr_params = $params->setAttribute(['routing' => 1, "id" => $item->getKey()])->setBody($data)->builder();

                    ElasticsearchClient::connection("relation")->index($atr_params);
                }

                // 下面示例报错，原因没找到 routing，貌似不支持批量添加
                // 注意：设置子文档的时候routing是必填项，不传会抛出异常。
//                $params = ["body" => []];
//                foreach ($discuss as $item) {
//                    $params["body"][] = [
//                        "index" => [
//                            "_index"   => $index,
//                            "_id"      => $item->getKey(),
//                        ],
//                        "routing" => 1
//                    ];
//
//                    $params["body"][] = array_merge($item->toArray(), ["relation" => ["name" => "child", "parent" => $item->oauth_id]]); // 关键在于 relation
//                }
//
//                dd(ElasticsearchClient::connection("relation")->bulk($params));

            });
    }
}
