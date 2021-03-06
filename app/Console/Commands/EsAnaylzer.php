<?php

namespace App\Console\Commands;

use App\Model\Book;
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
//                        "扩展包_to_packagist" => [
//                            "type" => "mapping",
//                            "mappings" => ["扩展包=> packagist "]
//                        ],
//                        "主从复制_to_copy" => [
//                            "type" => "mapping",
//                            "mappings" => ["主从复制=> copy "]
//                        ]
                    ],
                    // 词单元过滤器
                    "filter" => [
                        // 停用词
//                        "my_filter" => [
//                            "type" => "stop",
//                            "stopwords" => ["a", "the", "in", "fox"]
//                        ]
                        // 同义词 synonyms
                        "synonym_filter" => [
                            "type" => "synonym",
                            // 方法一、
//                            "synonyms" => [
//                                "扩展包,packagist",
//                                "主从复制,copy,database_copy"
//                            ]
                            // 方法二、为了防止这里定义过多的数据，我们可以把所有的同义词放到一个文档中。
                            // 官网 https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-synonym-graph-tokenfilter.html
                            // 首先，我们在 Elasticsearch 的 config 目录中，创建一个叫做 analysis 的子目录，然后创建一个叫做 synonyms.txt 的文档
                            "synonyms_path" => "analysis/synonyms.txt"
                        ]
                    ],
                    // 分析器
                    "analyzer" => [
                        "new_analyzer" => [
                            "type" => "custom",
                            // //数组顺序很重要，因为是照顺序执行，先执行htmp_strip，再执行 (_to_[ ，然后才去执行tokenizer
//                            "char_filter" => ["html_strip", "(_to_[", "扩展包_to_packagist", "主从复制_to_copy"], // 第一个使用默认的字符过滤器过滤 html 标签，第二个使用上面我们自定义的
                            "char_filter" => ["html_strip", "(_to_["], // 第一个使用默认的字符过滤器过滤 html 标签，第二个使用上面我们自定义的
                            "tokenizer" => "ik_max_word", // 中文分词器
//                            "filter" => [ "lowercase", "my_filter" ] // 第一个使用默认的词过滤器将单词转化为小写，第二个使用上面自定义的
                            "filter" => [ "lowercase", "synonym_filter" ] // 第一个使用默认的词过滤器将单词转化为小写，第二个使用上面自定义的
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
                        "analyzer" => "new_analyzer", // 创建索引时使用中文分词器
//                        "search_analyzer" => "new_analyzer", // 使用上面自定义的分析器
                        /**
                         * 注意：如何 analyzer 创建索引的时候使用自定义分析器，就不需要定义 search_analyzer，否则必须定义，不然搜索时用不上自定义的分析器
                         *
                         * ex：1
                         *      "analyzer" => "new_analyzer"
                         *
                         * ex：2
                         *      "analyzer" => "ik_max_word" // 分文分析器
                         *      "search_analyzer" => "new_analyzer" // 这里必须定义
                         */
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::connection("book")->indices()->create($params));
    }

    /**
     * Notes: 插入数据
     * Date: 2020/12/8 15:44
     */
    public function insert()
    {
        Book::query()
            ->chunkById(10, function($books){

                $params = ["body" => []];
                foreach ($books as $book) {
                    $params["body"][] = [
                        "index" => [
                            "_index" => "book",
                            "_id" => $book->getKey()
                        ]
                    ];

                    $params["body"][] = $book->toArray();
                }

                ElasticsearchClient::connection("book")->bulk($params);
            });
    }

    /**
     * Notes: 测试自定义分析器
     * Date: 2020/12/8 17:07
     */
    public function select()
    {
        $params = SearchBuilder::connection("book")
            ->setParams([
               "bool" => [
                    "filter" => [
                        "match" => [
                            // 自定义分析器
//                            "title" => "elasticsearch 超详细packagist copy"
                            // 同义词 synonyms
                            "title" => "database_copy"
                        ]
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::connection("book")->search($params));
    }
}
