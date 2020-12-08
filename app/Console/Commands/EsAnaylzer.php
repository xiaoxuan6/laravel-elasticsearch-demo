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
                            "mappings" => ["(=>{", ")=>]"]
                        ]
                    ],
                    // 词单元过滤器
                    "filter" => [
                        "my_filter" => [
                            "type" => "stop",
                            "stopwords" => ["a", "the", "in"]
                        ]
                    ],
                    // 分析器
                    "analyzer" => [
                        "new_analyzer" => [
                            "type" => "custom",
                            "tokenizer" => "standard",
                            "char_filter" => ["html_strip", "(_to_["], // 第一个使用默认的字符过滤器过滤 html 标签，第二个使用上面我们自定义的
                            "filter" => [ "lowercase", "my_filter" ] // 第一个使用默认的词过滤器将单词转化为小写，第二个使用上面自定义的
                        ]
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::connection("book")->indices()->create($params));
    }
}
