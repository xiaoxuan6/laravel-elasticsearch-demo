<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class AnalyzerController extends Controller
{
    protected $data = [
        [
            'id' => 10001,
            'name' => '一个分析器 必须 有一个唯一的分词器。 分词器把字符串分解成单个词条或者词汇单元。 标准 分析器里使用的 标准 分词器 把一个字符串根据单词边界分解成单个词条，&移除掉大部分的标点符号，然而还有其他不同行为的分词器存在。'
        ],
        [
            'id' => 10002,
            'name' => '词单元过滤器可以修改、添加或者移除词单元。我们已经提到过 lowercase with stop 词过滤器 ，但是在 Elasticsearch 里面还有很多可供选择的词单元过滤器。 词干过滤器 把单词 遏制 为 词干。 ascii_folding 过滤器移除变音符，把一个像 "très" 这样的词转换为 "tres" 。 ngram 和 edge_ngram 词单元过滤器 可以产生 适合用于部分匹配或者自动补全的词单元。'
        ],
        [
            'id' => 10003,
            'name' => "这个分析器现在是没有多大用处的，除非我们告诉 Elasticsearch在哪里用上它。我们可以像下面这样把这个分析器应用在一个 string 字段上："
        ],
        [
            'id' => 10004,
            'name' => '虽然Elasticsearch带有一些eto现成的分析器，然而在分析器上Elasticsearch真正的强大之处在于，你可以通过在一个适合你的特定数据的设置之中组合字符过滤器、分词器、词汇单元过滤器来创建自定义的分析器。'
        ],

    ];

    public function index()
    {
//        $params = SearchBuilder::setIndex('analyzer')
//            ->putSettings([
//                'number_of_shards' => 1,
//                'number_of_replicas' => 0,
//                'analysis' => [
//                    // 字符过滤器
//                    'char_filter' => [
//                        '&_to_and' => [
//                            'type' => 'mapping',
//                            'mappings' => ['& => and']
//                        ],
//                        'with_to_和' => [
//                            'type' => 'mapping',
//                            'mappings' => ['with => 和']
//                        ],
//                    ],
//                    // 词单元过滤器
//                    'filter' => [
//                        // 停用词
//                        'stop_filter' => [
//                            'type' => 'stop',
//                            'stopwords' => ['href', 'eto']
//                        ]
//                    ],
//                    // 分词器
//                    'analyzer' => [
//                        'new_analyzer' => [
//                            'type' => 'custom',
//                            'char_filter' => ['&_to_and', 'with_to_和'],
//                            'tokenizer' => 'standard',
//                            'filter' => ['stop_filter']
//                        ]
//                    ]
//                ]
//            ])
//            ->putMapping([
//                'dynamic' => false,
//                'properties' => [
//                    'name' => [
//                        'type' => 'text',
//                        'analyzer' => 'new_analyzer'
//                    ]
//                ]
//            ])
//            ->setAliases('analyser_aliases')
//            ->builder();

        $params = SearchBuilder::setIndex('analyzer')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ])
            ->charFilter([
                '&_to_and' => [
                    'type' => 'mapping',
                    'mappings' => ['& => and']
                ],
                'with_to_和' => [
                    'type' => 'mapping',
                    'mappings' => ['with => 和']
                ],
            ])
            ->filter([
                'stop_filter' => [
                    'type' => 'stop',
                    'stopwords' => ['href', 'eto']
                ]
            ])
            ->analyzer([
                'new_analyzer' => [
                    'type' => 'custom',
                    'char_filter' => ['&_to_and', 'with_to_和'],
                    'tokenizer' => 'standard',
                    'filter' => ['stop_filter']
                ]
            ])
            ->putMapping([
                'dynamic' => false,
                'properties' => [
                    'name' => [
                        'type' => 'text',
                        'analyzer' => 'new_analyzer'
                    ]
                ]
            ])
            ->setAliases('analyser_aliases')
            ->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }

    public function analyze(Request $request)
    {
        $params = SearchBuilder::unsetAttribute('index')->analyze(['text' => $request->get('name', '你好')]);

        $params = SearchBuilder::setIndex('analyzer')->analyze(['text' => $request->get('name', '你好'), 'analyzer' => 'ik_max_word']);
        // 使用文中分词器
//        $params = SearchBuilder::setIndex('analyzer')->analyze(['text' => $request->get('name', '你好'), 'analyzer' => 'ik_max_word']);

        try {
            $re = ElasticsearchClient::indices()->analyze($params);

//            $re = ElasticsearchClient::indices()->analyze(['index' => 'analyzer', 'body' => ['text' => $request->get('name', '你好')]]);
        } catch (\Exception $exception) {
            $re = json_decode($exception->getMessage(), 1);
        }

        dd($re);
    }

    public function data()
    {
        $item = ['body' => []];
        collect($this->data)->each(function ($data) use (&$item) {

            $item['body'][] = [
                'index' => [
                    '_index' => 'analyzer',
                    '_id' => $data['id'],
                ]
            ];

            $item['body'][] = $data;
        });

        dd(ElasticsearchClient::bulk($item));
    }

    /**
     * @param Request $request
     *
     * @see http://es.com/data-search?name=过滤器
     */
    public function search(Request $request)
    {
        $params = SearchBuilder::setIndex('analyzer')
            ->setParams([
                'match' => [
                    'name' => $request->get('name') ?? '和'
                ]
            ])
            ->builder();

//        $params = SearchBuilder::setIndex('analyzer')
//            ->setParams([
//                'bool' => [
//                    'must' => [
//                        'match' => [
//                            'name' => '过滤'
//                        ]
//                    ],
//                    'must_not' => [
//                        'match' => [
//                            'name' => '和'
//                        ]
//                    ]
//                ]
//            ])
//            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    /*********************************** 使用中文分词器 **************************************/

    public function ikAnalyzer()
    {
        $params = SearchBuilder::setIndex('analyzer_ik')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ])
            ->charFilter([
                '&_to_and' => [
                    'type' => 'mapping',
                    'mappings' => ['& => and']
                ],
                'with_to_和' => [
                    'type' => 'mapping',
                    'mappings' => ['with => 和']
                ],
            ])
            ->filter([
                'stop_filter' => [
                    'type' => 'stop',
                    'stopwords' => ['href', 'eto']
                ]
            ])
            ->analyzer([
                'new_analyzer' => [
                    'type' => 'custom',
                    'char_filter' => ['&_to_and', 'with_to_和'],
                    'tokenizer' => 'ik_max_word',
                    'filter' => ['stop_filter']
                ]
            ])
            ->putMapping([
                'dynamic' => false,
                'properties' => [
                    'name' => [
                        'type' => 'text',
                        'analyzer' => 'new_analyzer'
                    ]
                ]
            ])
            ->setAliases('analyser_aliases')
            ->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }

    public function ikData()
    {
        $item = ['body' => []];
        collect($this->data)->each(function ($data) use (&$item) {

            $item['body'][] = [
                'index' => [
                    '_index' => 'analyzer_ik',
                    '_id' => $data['id'],
                ]
            ];

            $item['body'][] = $data;
        });

        dd(ElasticsearchClient::bulk($item));
    }

    /**
     * @param Request $request
     *
     * @see http://es.com/data-search?name=过滤器
     */
    public function ikSearch(Request $request)
    {
        $params = SearchBuilder::setIndex('analyzer_ik')
            ->setParams([
                'match' => [
                    'name' => $request->get('name') ?? '和'
                ]
            ])
            ->builder();
        dd(ElasticsearchClient::search($params));
    }

    /**
     * 使用中文分词器对比：
     *
     * 这里通过 search 方法可以查出四条数据，通过 analyze 可以看出 过滤器 分词结果为：
     * array:1 [▼
     * "tokens" => array:3 [▼
     * 0 => array:5 [▼
     * "token" => "过"
     * "start_offset" => 0
     * "end_offset" => 1
     * "type" => "<IDEOGRAPHIC>"
     * "position" => 0
     * ]
     * 1 => array:5 [▼
     * "token" => "滤"
     * "start_offset" => 1
     * "end_offset" => 2
     * "type" => "<IDEOGRAPHIC>"
     * "position" => 1
     * ]
     * 2 => array:5 [▼
     * "token" => "器"
     * "start_offset" => 2
     * "end_offset" => 3
     * "type" => "<IDEOGRAPHIC>"
     * "position" => 2
     * ]
     * ]
     * ]
     *
     * 这里通过 ikSearch 方法可以查出四条数据，通过 analyze 可以看出 过滤器 分词结果为：
     *  array:1 [▼
     * "tokens" => array:3 [▼
     * 0 => array:5 [▼
     * "token" => "过滤器"
     * "start_offset" => 0
     * "end_offset" => 3
     * "type" => "CN_WORD"
     * "position" => 0
     * ]
     * 1 => array:5 [▼
     * "token" => "过滤"
     * "start_offset" => 0
     * "end_offset" => 2
     * "type" => "CN_WORD"
     * "position" => 1
     * ]
     * 2 => array:5 [▼
     * "token" => "滤器"
     * "start_offset" => 1
     * "end_offset" => 3
     * "type" => "CN_WORD"
     * "position" => 2
     * ]
     * ]
     * ]
     */
}
