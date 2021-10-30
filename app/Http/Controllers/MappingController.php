<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class MappingController extends Controller
{
    /**
     * 将字段 copy 到新的字段
     */
    public function copy()
    {
        $params = SearchBuilder::setIndex('copy')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ])
            ->putMapping([
                'dynamic' => false,
                'properties' => [
                    'province' => [
                        'type' => 'keyword',
                        'copy_to' => 'full_name'
                    ],
                    'city' => [
                        'type' => 'keyword',
                        'copy_to' => 'full_name'
                    ],
                    'full_name' => [
                        'type' => 'keyword'
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }

    public function import(Request $request)
    {
        $data = [
            ['id' => 100, 'province' => '山东省', 'city' => '临沂市'],
            ['id' => 101, 'province' => '广东省', 'city' => '广州市'],
            ['id' => 102, 'province' => '广东省', 'city' => '深圳市'],
        ];

        foreach ($data as $arr) {

            $params = SearchBuilder::setIndex($request->get('index', 'index'))
                ->setKey($arr['id'])
                ->setBody([
                    'id' => $arr['id'],
                    'province' => $arr['province'],
                    'city' => $arr['city'],
                ])
                ->builder();

            ElasticsearchClient::index($params);
        }
    }

    public function searchCopy()
    {
        $params = SearchBuilder::setIndex('copy')
            ->setParams([
                'match' => [
//                    'province' => '广东省',
                    // or
                    'full_name' => '广东省',

                    // 暂时无效 操作
//                    'full_name' => '广东省 广州市'
//                    'full_name' => [
//                        'query' => '广东省 广州市', // 查询条件以空格隔开
//                        'operator' => 'or', // 多个条件的查询关系
////                        'minimum_should_match' => 1
//                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    public function index()
    {
        $params = SearchBuilder::setIndex('index')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0
            ])
            ->putMapping([
                'properties' => [
                    'province' => [
                        'type' => 'keyword'
                    ],
                    'city' => [
                        'type' => 'keyword',
                        'index' => false, // 重点
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }

    /**
     * 默认是true。当设置为false，表明该字段不能被查询，如果查询会报错。但是可以被store。当该文档被查出来时，在_source中也会显示出该字段。
     *
     * failed to create query: Cannot search on field [city] since it is not indexed.
     *
     */
    public function searchIndex()
    {
        try {
            $re = ElasticsearchClient::search(SearchBuilder::setIndex('index')->setParams(['match' => ['city' => '山东省']])->builder());
        } catch (\Exception $exception) {
            $re = json_decode($exception->getMessage(), 1);
        }

        dd($re);
    }

    /**
     * 简单的说就是可以搜索字段，控制在搜索结果中是否展示 _source 字段
     *
     * 默认false。store参数的功能和_source有一些相似。我们的数据默认都会在_source中存在。但我们也可以将数据store起来，不过大部分时候这个功能都很鸡肋。
     * 不过有一个例外，当我们使用copy_to参数时，copy_to的目标字段并不会在_source中存储，此时store就派上用场了。
     */
    public function store()
    {
        $params = SearchBuilder::setIndex('store')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ])
            ->putMapping([
                'dynamic' => false,
                'properties' => [
                    'province' => [
                        'type' => 'keyword',
                        'copy_to' => 'full_name'
                    ],
                    'city' => [
                        'type' => 'keyword',
                        'copy_to' => 'full_name'
                    ],
                    // 该字段是通过 copy 虚拟的字段，但是能够搜索，只是在结果中 _source 不展示,这里 store 设置为 true 就可以展示了，
                    'full_name' => [
                        'type' => 'keyword',
                        'store' => true
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }

    public function searchStore()
    {
        try {
            $re = ElasticsearchClient::search(SearchBuilder::setIndex('store')->setParams(['match' => ['full_name' => '山东省']])->stored(['full_name'])->builder());
        } catch (\Exception $exception) {
            $re = json_decode($exception->getMessage(), 1);
        }

        dd($re);
    }

    public function search()
    {
        $params = SearchBuilder::setIndex('elk')
            ->setParams([
                'match' => [
                    'name' => 'golang'
                ]
            ])
            ->setSource(['name', 'age']) // 设置可展示字段
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    public function searchAndStore()
    {
        $params = SearchBuilder::setIndex('store')
            ->setParams([
                'match' => [
                    'full_name' => '山东省'
                ]
            ])
            ->stored(['full_name'])
            ->setSource(['province'])
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    /**
     * 只想存储该字段而不对其进行索引
     * 该enabled设置只能应用于顶级映射定义和object字段
     *
     * 默认是true。只用于mapping中的object字段类型。当设置为false时，其作用是使es不去解析该字段，并且该字段不能被查询和store，
     * 只有在_source中才能看到（即查询结果中会显示的_source数据）。设置enabled为false，可以不设置字段类型，默认为object
     */
    public function enabled()
    {
        $params = SearchBuilder::setIndex('enabled')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ])
            ->putMapping([
                'dynamic' => false,
                'properties' => [
                    'province' => [
                        'type' => 'keyword',
                    ],
                    'city' => [
                        'type' => 'keyword',
                    ],
                    'address' => [
                        'type' => 'object', // 重点
                        'enabled' => false
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }

    public function importEnabled()
    {
        $params = SearchBuilder::setIndex('enabled')
            ->setKey(100)
            ->setBody([
                'id' => 100,
                'province' => '上海市',
                'city' => '上海市',
                'address' => "宝山区"
            ])
            ->builder();

        dd(ElasticsearchClient::index($params));
    }

    /**
     * 该字段设置 enabled 为 false 不能搜索
     */
    public function searchEnabled()
    {
        $params = SearchBuilder::setIndex('enabled')
            ->setParams([
                'match' => [
                    'address' => '宝山区'
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

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
        [
            'id' => 10005,
            'name' => '虽然带有一些eto现成的分析器'
        ],
        [
            'id' => 10006,
            'name' => '虽然一些eto'
        ]
    ];

    /**
     * 超过 ignore_above 的字符会被存储，但不会被索引。
     *
     * 注意，是字符长度，一个英文字母是一个字符，一个汉字也是一个字符
     * text 类型不支持 ignore_above,只有 keyword 支持
     * 报错:  Failed to parse mapping [_doc]: unknown parameter [ignore_above] on mapper [desc] of type [text]
     */
    public function ignoreAbove()
    {
        /* $params = SearchBuilder::setIndex('ignore_above1')
             ->putSettings([
                 'number_of_shards' => 1,
                 'number_of_replicas' => 0
             ])
             ->putMapping([
                 'properties' => [
                     'id' => [
                         'type' => 'integer'
                     ],
                     'name' => [
                         'type' => 'keyword',
                         'ignore_above' => 20 // 超度超过20个字符不走索引
                     ],
 //                    'desc' => [
 //                        'type' => 'text',
 //                        'ignore_above' => 20
 //                    ]
                 ]
             ])
             ->builder();*/

        $params = SearchBuilder::setIndex('my_index')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0
            ])
            ->putMapping([
                'properties' => [
                    'note' => [
                        'type' => 'keyword',
                        'ignore_above' => 4 // 超度超过20个字符不走索引
                    ],
                ]
            ])
            ->builder();

        try {
            $re = ElasticsearchClient::indices()->create($params);
        } catch (\Exception $exception) {
            $re = json_decode($exception->getMessage(), 1);
        }

        dd($re);
    }

    public function importIgnoreAbove()
    {
        $data = [
            ['id' => 1, 'note' => '一二三'],
            ['id' => 2, 'note' => '一二三四'],
            ['id' => 3, 'note' => '一二三四五'],
        ];

        $item = ['body' => []];
        foreach ($data as $arr) {
            $item['body'][] = [
                'index' => [
                    '_id' => $arr['id'],
                    '_index' => 'my_index',
                ]
            ];

            $item['body'][] = $arr;
        }


        dd(ElasticsearchClient::bulk($item));
    }

    /**
     * 该搜索属于 string 全部匹配搜索,不支持 分词搜索
     */
    public function searchIgnoreAbove()
    {
//        dd(ElasticsearchClient::search(SearchBuilder::setIndex('my_index')->setParams(['match' => ['note' => '一二三四']])->builder()));
        dd(ElasticsearchClient::search(SearchBuilder::setIndex('ignore_above1')->setParams(['match' => ['name' => '虽然带有一些eto现成的分析器']])->builder()));
        dd(ElasticsearchClient::search(SearchBuilder::setIndex('ignore_above1')->setParams(['match' => ['id' => 10005]])->builder()));
        dd(ElasticsearchClient::search(SearchBuilder::setIndex('ignore_above1')->setParams(['match_all' => new \stdClass()])->builder()));
    }

    /**
     * 该index_prefixes参数启用对术语前缀的索引以加快前缀搜索。它接受以下可选设置：
     * min_chars:    索引的最小前缀长度。必须大于 0，默认为 2。该值包含在内。
     * max_chars:    索引的最大前缀长度。必须小于 20，默认为 5。该值包含在内。
     */
    public function indexPrefixes()
    {
        $params = SearchBuilder::setIndex('index_prefixes')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ])
            ->putMapping([
                'properties' => [
                    'name' => [
                        'type' => 'text',
                        'index_prefixes' => [
                            'min_chars' => 1,
                            'max_chars' => 10
                        ],
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }

    public function importPrefix()
    {
        $item = ['body' => []];
        foreach ($this->data as $arr) {
            $item['body'][] = [
                'index' => [
                    '_id' => $arr['id'],
                    '_index' => 'index_prefixes',
                ]
            ];

            $item['body'][] = $arr;
        }


        dd(ElasticsearchClient::bulk($item));
    }

    /************************* 英文搜索 ****************************/

    /**
     * 前缀
     * @see https://www.cnblogs.com/huangying2124/p/12544098.html
     */
    public function searchPrefix()
    {
        $params = SearchBuilder::setIndex('index_prefixes')
            ->setParams([
//                'match_all' => new \stdClass()
                // 单个字或词(不能在分词)搜索
                'prefix' => [
//                    'name' => '虽',
                    'name' => '一个' // 这里不支持多个文字搜索，因为 indexPrefixes 方法创建索引的时候中文分词会把每个文字分词成每个汉字，可以使用 searchAnalyzerPrefix 方法进行搜索
                ]
                // 字符串前缀搜索
//                'match_phrase_prefix' => [
//                    'name' => '一个分析器 必须 有一个唯一',
//                ]
                /**
                 * @see https://www.elastic.co/guide/cn/elasticsearch/guide/current/_query_time_search_as_you_type.html
                 */
//                'match_phrase_prefix' => [
//                    'name' => [
//                        'query' => '一个 分析器',
//                        'slop' => 2, // 查询词条相隔多远时仍然能将文档视为匹配
//                        'max_expansions' => 50 // 通过设置 max_expansions 参数来限制前缀扩展的影响，一个合理的值是可能是 50
//                    ]
//                ]
            ])
            ->highlight(['name' => new \stdClass()])
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    /**
     * 通配符支持两种：
     * 1. ? : 支持模糊匹配单个字符。举例：Ma?s 仅能匹配：Mars, Mass, 和 Maps。
     * 2. *: 支持模糊匹配零个或者多个字符。举例：Ma*s 能匹配：Mars, Matches 和 Massachusetts等。
     */
    public function searchWildcard()
    {
        $params = SearchBuilder::setIndex('index_prefixes')
            ->setParams([
                'wildcard' => [
//                    'name' => "elastic*"
                    'name' => "分*" // 暂时不支持多个汉字,多个汉字可能会进行分词
                ]
            ])
            ->highlight(['name' => new \stdClass()])
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    /**
     * 允许搜索有误差的字符
     */
    public function searchFuzzy()
    {
        $params = SearchBuilder::setIndex('index_prefixes')
            ->setParams([
                'fuzzy' => [
                    'name' => 'elasticsaerch'
                ]
            ])
            ->highlight(['name' => new \stdClass()])
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    /**
     * 正则
     */
    public function searchRegexp()
    {
        $params = SearchBuilder::setIndex('index_prefixes')
            ->setParams([
                'regexp' => [
                    'name' => '[a-t]{3}'
                ]
            ])
            ->setSource(['id'])
            ->highlight(['name' => new \stdClass()])
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    /************************* 中文搜索 ****************************/
    public function searchAnalyzer()
    {
        $params = SearchBuilder::setIndex('search_analyzer')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ])
            ->putMapping([
                'dynamic' => false,
                'properties' => [
                    'id' => [
                        'type' => 'integer'
                    ],
                    'name' => [
                        'type' => 'text',
                        'analyzer' => 'ik_max_word', // 索引分词
                        'search_analyzer' => 'standard' // 搜索部分词
                    ]
                ]
            ])
            ->builder();

        try {
            if (ElasticsearchClient::indices()->exists(['index' => 'search_analyzer'])) {
                ElasticsearchClient::indices()->delete(['index' => 'search_analyzer']);

                $re = ElasticsearchClient::indices()->create($params);

                $this->importAnalyzer();

            } else {

                $re = ElasticsearchClient::indices()->create($params);
            }

            dd($re);
        } catch (\Exception $exception) {
            dd(json_decode($exception->getMessage(), true));
        }
    }

    public function importAnalyzer()
    {
        $body = ['body' => []];
        foreach ($this->data as $arr) {
            $body['body'][] = [
                'index' => [
                    '_id' => $arr['id'],
                    '_index' => 'search_analyzer',
                ]
            ];

            $body['body'][] = $arr;
        }

        dd(ElasticsearchClient::bulk($body));
    }

    public function searchAnalyzerPrefix()
    {
        $params = SearchBuilder::setIndex('search_analyzer')
            ->setParams([
                'prefix' => [
                    'name' => '一个'
                ]
            ])
            ->highlight(['name' => new \stdClass()])
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    public function searchAnalyzerWildcard()
    {
        $params = SearchBuilder::setIndex('search_analyzer')
            ->setParams([
                'wildcard' => [
//                    'name' => "分词*"
                    'name' => "分?器"
                ]
            ])
            ->highlight(['name' => new \stdClass()])
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    public function properties()
    {
        $params = SearchBuilder::setIndex('properties')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
            ])
            ->putMapping([
                'properties' => [
                    'id' => [
                        'type' => 'integer',
                        'index' => false,
                    ],
                    'name' => [
                        'type' => 'keyword'
                    ],
                    'manage' => [
                        'properties' => [
                            'title' => [
                                'type' => 'keyword'
                            ],
                            'desc' => [
                                'type' => 'text',
                                'analyzer' => 'ik_max_word'
                            ]
                        ]
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }

    public function importProperties()
    {
        $data = [
            [
                'id' => 100,
                'name' => '托米斯基',
                'manage' => [
                    'title' => '这是标题一',
                    'desc' => '测试 properties 是否可行'
                ]
            ],
            [
                'id' => 101,
                'name' => '布隆过滤器',
                'manage' => [
                    'title' => '这是 redis 布隆过滤器',
                    'desc' => '高性能 redis 之 布隆过滤器，防止 redis 穿透'
                ]
            ],
            [
                'id' => 103,
                'name' => 'Elasticsearch 索引操作 mapping',
                'manage' => [
                    [
                        'title' => '测试 mapping 之 properties',
                        'desc' => '类型映射、object字段和nested字段 包含子字段，称为properties. '
                    ],
                    [
                        'title' => 'Elasticsearch 之 properties 操作',
                        'desc' => '类型映射、object字段和nested字段 包含子字段，称为properties. '
                    ]
                ]
            ],
            [
                'id' => 104,
                'name' => '高性能 Rabbitmq 之 QPS',
                'manage' => [
                    [
                        'title' => 'RabbitMQ功能测试+性能测试简单方法',
                        'desc' => '轮持久化的重要性：持久化的服务器收到消息后就会立刻将消息写入到硬盘，就可以防止突然服务器挂掉，而引起的数据丢失了。但是服务器如果刚收到消息，还没来得及写入到硬盘，就挂掉了，这样还是无法避免消息的丢失'
                    ],
                    [
                        'title' => 'RabbitMQ快速入门',
                        'desc' => 'MQ全称为Message Queue，即消息队列。“消息队列”是在消息的传输过程中保存消息的容器。它是典型的：生产者、消费者模型。生产者不断向消息队列中生产消息，消费者不断的从队列中获取消息。因为消息的生产和消费都是异步的，而且只关心消息的发送和接收，没有业务逻辑的侵入，这样就实现了生产者和消费者的解耦。'
                    ]
                ]
            ]
        ];

        $body = ['body' => []];
        foreach ($data as $arr) {
            $body['body'][] = [
                'index' => [
                    '_id' => $arr['id'],
                    '_index' => 'properties'
                ]
            ];

            $body['body'][] = $arr;
        }

        dd(ElasticsearchClient::bulk($body));
    }

    /**
     * text类型:
     *
     * 1:支持分词，全文检索,支持模糊、精确查询,不支持聚合,排序操作;
     * 2:test类型的最大支持的字符长度无限制,适合大字段存储；
     * 使用场景：
     *    存储全文搜索数据, 例如: 邮箱内容、地址、代码块、博客文章内容等。
     *
     * keyword:
     *
     * 1:不进行分词，直接索引,支持模糊、支持精确匹配，支持聚合、排序操作。
     * 2:keyword类型的最大支持的长度为——32766个UTF-8类型的字符,可以通过设置ignore_above指定自持字符长度，超过给定长度后的数据将不被索引，无法通过term精确匹配检索返回结果。
     * 使用场景：
     *    存储邮箱号码、url、name、title，手机号码、主机名、状态码、邮政编码、标签、年龄、性别等数据。
     */
    public function searchProperties()
    {
        $params = SearchBuilder::setIndex('properties')
            ->setParams([
                'term' => [
                    'manage.title' => '这是 redis 布隆过滤器' // 不分词
                ],
                /*'match' => [
//                    'manage.desc' => 'redis'
                    'manage.desc' => '映射'
                ]*/
            ])
            ->builder();

        dd(ElasticsearchClient::search($params));
    }
}
