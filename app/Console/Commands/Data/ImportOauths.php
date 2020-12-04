<?php

namespace App\Console\Commands\Data;

use App\Model\Oauth;
use Illuminate\Console\Command;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class ImportOauths extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:oauth {--force}';

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
        $index = SearchBuilder::connection("oauth")->getIndex(true);

        if (!ElasticsearchClient::connection("oauth")->indices()->exists($index)) {
            $currentIndex = current($index);

            $this->info("索引不存在，准备创建");
            $this->indexCreate($currentIndex);
            $this->info("创建成功，准备添加数据");

            try {
                $this->build($currentIndex);
            } catch (\Exception $exception) {
                dd($exception->getMessage());
            }

            $this->info("数据添加成功");
            $this->info("操作完成");
            die;
        }

        if($this->option("force")) {
            $index = ElasticsearchClient::connection("oauth")->indices()->getAliases(SearchBuilder::connection("oauth")->getIndex(true));

            $indexAlias = array_keys($index)[0];

            ElasticsearchClient::connection("oauth")->indices()->delete(["index" => $indexAlias]);

            dd('删除成功');
        } else {
            dd("索引已存在");
        }
    }

    /**
     * Notes: 创建索引
     * Date: 2020/12/2 14:05
     *
     * Warning: 如何添加索引别名，不能和索引名一样
     *
     * dynamic：
     *          动态映射（dynamic：true）：动态添加新的字段（或缺省）。
     *          静态映射（dynamic：false）：忽略新的字段。在原有的映射基础上，当有新的字段时，不会主动的添加新的映射关系，只作为查询结果出现在查询中。
     *          严格模式（dynamic： strict）：如果遇到新的字段，就抛出异常。
     */
    protected function indexCreate($index)
    {
        $params = SearchBuilder::connection("oauth")
            ->setIndex($index . "_0")
            ->putSettings([
                "number_of_shards"   => 1,
                "number_of_replicas" => 0
            ])
            ->putMapping([
                "dynamic" => false, // true 动态映射; false 静态映射; strict 严格模式
                "properties" => [
                    "github_id"  => ["type" => "keyword"],
                    "username"   => ["type" => "text", "analyzer" => "ik_max_word", "search_analyzer" => "ik_max_word"],
                    "email"      => ["type" => "keyword"],
                    "avatar"     => ["type" => "text", "index" => true],
                    "github_url" => ["type" => "text", "index" => true],
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
                        "type" => "date",
                        "format" => "yyyy-MM-dd HH:mm:ss"
                    ]
                ]
            ])
            ->setAliases([$index => new \stdClass()])
            ->builder();

        ElasticsearchClient::connection("oauth")
            ->indices()
            ->create($params);
    }

    /**
     * Notes: 批量添加数据
     * Date: 2020/12/2 14:26
     * @param $index
     *
     * Warning：批量创建数据的时候注意 body 中的 Index 这个特别容易忘记
     */
    protected function build($index)
    {
        Oauth::query()
            ->chunkById(100, function ($oauths) use ($index) {

                $this->info(sprintf("正在同步 ID 范围在 %s 至 %s 的数据", $oauths->first()->id, $oauths->last()->id));

                $params = ["body" => []];
                foreach ($oauths as $oauth) {
                    $params["body"][] = [
                       "index" => [
                           "_index" => $index,
                           "_id"    => $oauth->getKey()
                       ]
                    ];

                    $params["body"][] = $oauth->toArray();
                }

                ElasticsearchClient::connection("oauth")->bulk($params);
            });
    }
}
