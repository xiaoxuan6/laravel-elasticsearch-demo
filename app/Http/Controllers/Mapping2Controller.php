<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class Mapping2Controller extends Controller
{
    public function setProTemplate()
    {
        $params = SearchBuilder::template()
            ->name('properties')
            ->indexPatterns('pro_*')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0
            ])
            ->builder();

        dd(ElasticsearchClient::indices()->putTemplate($params));
    }

    public function null()
    {
        $params = SearchBuilder::setIndex('pro_null')
            ->putMapping([
                'properties' => [
                    'name' => [
                        'type' => 'keyword',
                        'null_value' => 'null' // 这里把字段值为 null 的转为 'null'， 方便搜索的时候进行索引（空数组无效）
                    ]
                ]
            ])
            ->builder();

        if (ElasticsearchClient::indices()->exists(['index' => 'pro_null'])) {
            ElasticsearchClient::indices()->delete(['index' => 'pro_null']);
        }

        dd(ElasticsearchClient::indices()->create($params));
    }

    protected $names = [
        '空数组不包含显式null，因此不会被替换为null_value。',
        '一个null值不能被索引或搜索。当一个字段设置为null, （或一个空数组或一个null值数组）时，它被视为该字段没有值。',
        '该null_value参数允许您null用指定的值替换显式值，以便可以对其进行索引和搜索',
        '如果我们想要请求"content中带宝马，但是tag中不带宝马"这样类似的需求，就需要用到bool联合查询。',
        'term是代表完全匹配，即不进行分词器分析，文档中必须包含整个搜索的词汇',
        'prefix 查询是一个词级别的底层的查询，它不会在搜索之前分析查询字符串，它假定传入前缀就正是要查找的前缀。',
        'fuzziness：最多纠正的字母个数，默认是2，有限制，设置太大也是无效的，不能无限加大，错误太多了也纠正不了。',
    ];

    public function index(Request $request)
    {
        $id = rand(0, 100);

        $data = [
            [
                'id' => $id,
                'name' => null,
            ],
            [
                'id' => $id,
                'name' => rand(1, 7)
            ]
        ];

        $param = SearchBuilder::setIndex($request->get('index', 'pro_null'))
            ->setKey($id)
            ->setBody($data[rand(0, 1)])
            ->builder();

        dd(ElasticsearchClient::index($param));
    }

    public function search()
    {
//        dd(ElasticsearchClient::search(SearchBuilder::setIndex('pro_null')->size(100)->setParams(['match_all' => new \stdClass()])->builder()));

        dd(ElasticsearchClient::search(SearchBuilder::setIndex('pro_null')->setParams(['term' => ['name' => 'null']])->orderByAsc('id')->paginate(2)->builder()));
    }

    /**
     * 默认情况下，所有支持 doc 值的字段都启用了它们。如果您确定不需要对字段进行排序或聚合，或从脚本访问字段值，则可以禁用 doc 值以节省磁盘空间：
     */
    public function doc_values()
    {
        $params = SearchBuilder::setIndex('pro_doc_values')
            ->putMapping([
                'properties' => [
                    'name' => [
                        'type' => 'keyword',
                        'doc_values' => false,
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }
}
