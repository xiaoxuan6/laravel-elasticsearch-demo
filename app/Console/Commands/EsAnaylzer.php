<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class EsAnaylzer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:analyzer {method}';

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
     * Notes: 自定义分析器
     * Date: 2020/12/8 14:59
     */
    protected function createAnalyzer()
    {
        $params = SearchBuilder::connection("book")
            ->putSettings([
                "number_of_shards" => 1,
                "number_of_replicas" => 0,
                "analysis" => [
                    // 字符过滤器
                    "char_filter" => [
                        "(_to_[" => [
                            "type" => "mapping",
                            "mappings" => ["&=> and "]
                        ],
                        "扩展包_to_packagist" => [
                            "type" => "mapping",
                            "mappings" => ["扩展包=> packagist "]
                        ],
                        "主从复制_to_copy" => [
                            "type" => "mapping",
                            "mappings" => ["主从复制=> copy "]
                        ]
                    ],
                    // 词单元过滤器
                    "filter" => [
                        "my_filter" => [
                            "type" => "stop",
                            "stopwords" => ["a", "the", "in", "fox"] // 停用词
                        ]
                    ],
                    // 分析器
                    "analyzer" => [
                        "new_analyzer" => [
                            "type" => "custom",
                            // //数组顺序很重要，因为是照顺序执行，先执行htmp_strip，再执行 (_to_[ ，然后才去执行tokenizer
                            "char_filter" => ["html_strip", "(_to_[", "扩展包_to_packagist", "主从复制_to_copy"], // 第一个使用默认的字符过滤器过滤 html 标签，第二个使用上面我们自定义的
                            "tokenizer" => "ik_max_word", // 中文分词器
                            "filter" => [ "lowercase", "my_filter" ] // 第一个使用默认的词过滤器将单词转化为小写，第二个使用上面自定义的
                        ]
                    ]
                ]
            ])
            ->putMapping([
                "dynamic" => false,
                "properties" => [
//                    分析器主要有两种情况会被使用，一种是插入文档时，将text类型的字段做分词然后插入倒排索引，第二种就是在查询时，先对要查询的text类型的输入做分词，再去倒排索引搜索
//                    如果想要让 索引 和 查询 时使用不同的分词器，ElasticSearch也是能支持的，只需要在字段上加上search_analyzer参数
                    "title" => [
                        "type" => "text",
                        "analyzer" => "new_analyzer", // 使用上面自定义的分析器
                        "search_analyzer" => "new_analyzer",
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::connection("book")->indices()->create($params));
    }

}
