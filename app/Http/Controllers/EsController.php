<?php

namespace App\Http\Controllers;

use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class EsController extends Controller
{
    /**
     * 查看 es 信息
     */
    public function info()
    {
        dd(ElasticsearchClient::info());
    }

    /**
     * 测试 host 是否可访问
     */
    public function ping()
    {
        dd(ElasticsearchClient::ping());
    }

    /**
     * 判断索引是否存在
     */
    public function indexExists()
    {
        dd(ElasticsearchClient::indices()->exists(['index' => 'elk']));
    }

    /**
     * 删除索引
     *
     * array:1 [▼
     * "acknowledged" => true
     * ]
     */
    public function indexDel()
    {
        $params = ['index' => 'elk'];

        if (ElasticsearchClient::indices()->exists($params)) {
            dd(ElasticsearchClient::indices()->delete($params));
        }

        dd('索引不存在');
    }

    /**
     * 创建索引
     *
     * array:3 [▼
     * "acknowledged" => true
     * "shards_acknowledged" => true
     * "index" => "elk"
     * ]
     */
    public function indexCreate()
    {
        // 不使用 config 配置文件中的索引名，单独设置
        $params = SearchBuilder::setIndex('elk')
            ->putSettings([
                'number_of_shards' => 1, // 是数据分片数，默认为5，有时候设置为3
                'number_of_replicas' => 0 // 是数据备份数，如果只有一台机器，设置为0
            ])
            ->putMapping([
                'properties' => [
                    'name' => [
                        'type' => 'text',
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }

    /**
     * 创建索引并添加别名
     */
    public function indexCreateAndAliases()
    {
        $params = SearchBuilder::setIndex('create_index')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
                'refresh_interval' => -1
            ])
            ->putMapping([
                'properties' => [
                    'keyword' => [
                        'type' => 'text'
                    ]
                ]
            ])
            ->setAliases('create_index_1')
            ->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }

    public function indexCloseOrOpen()
    {
        dd(ElasticsearchClient::indices()->open(['index' => 'elk']));
        dd(ElasticsearchClient::indices()->close(['index' => 'elk']));
    }

    public function stats()
    {
        // 默认查看所有的索引，
        $params = [
            // demo1、一个索引
            'index' => 'elk',
            // demo2、多个索引
//            'index' => 'elk,demo'

            /**
             * 设置显示那些字段
             * @see https://www.elastic.co/guide/en/elasticsearch/reference/master/indices-stats.html#index-stats-api-path-params
             */
            'metric' => 'docs,get'
        ];

        dd(ElasticsearchClient::indices()->stats($params));
    }

    /**
     * 获取索引的 setting
     *
     * array:1 [▼
     * "elk" => array:1 [▼
     * "settings" => array:1 [▼
     * "index" => array:7 [▼
     * "routing" => array:1 [▼
     * "allocation" => array:1 [▼
     * "include" => array:1 [▼
     * "_tier_preference" => "data_content"
     * ]
     * ]
     * ]
     * "number_of_shards" => "1"
     * "provided_name" => "elk"
     * "creation_date" => "1634388910308"
     * "number_of_replicas" => "0"
     * "uuid" => "CyZgFSgDT16UX5hkMsSrvA"
     * "version" => array:1 [▼
     * "created" => "7120099"
     * ]
     * ]
     * ]
     * ]
     * ]
     */
    public function getSetting()
    {
        // 支持一个或多个索引
        dd(ElasticsearchClient::indices()->getSettings(['index' => 'elk']));
    }

    /**
     * 设置索引的 setting
     */
    public function putSetting()
    {
        $params = SearchBuilder::setIndex('elk')
            ->putSettings([
                /**
                 * @see https://www.letianbiji.com/elasticsearch/es7-refresh-interval.html
                 *
                 * Notice：
                 *      number_of_shards 分片不支持修改，只能创建
                 */
                'number_of_replicas' => 0,
                'refresh_interval' => '1s' // 当数据添加到索引后并不能马上被查询到，等到索引刷新后才会被查询到。
                // refresh_interval 配置的刷新间隔。refresh_interval 的默认值是 1s。
                // 当 refresh_interval 为 -1 时，意味着不刷新索引。
            ])
            ->builder();

        dd(ElasticsearchClient::indices()->putSettings($params));
    }

    /**
     * 获取索引的 mapping
     *
     * array:1 [▼
     * "elk" => array:1 [▼
     * "mappings" => array:1 [▼
     * "properties" => array:1 [▼
     * "name" => array:1 [▼
     * "type" => "text"
     * ]
     * ]
     * ]
     * ]
     * ]
     */
    public function getMapping()
    {
        dd(ElasticsearchClient::indices()->getMapping(['index' => 'elk']));
    }

    /**
     * 获取指定字段的 mapping
     */
    public function getFieldMapping()
    {
        // 单个字段
//        dd(ElasticsearchClient::indices()->getFieldMapping(['index' => 'elk', 'fields' => 'name']));
        // 多个字段
//        dd(ElasticsearchClient::indices()->getFieldMapping(['index' => 'elk', 'fields' => 'name,age']));
        // 支持正则
        dd(ElasticsearchClient::indices()->getFieldMapping(['index' => 'elk', 'fields' => 'n*']));
    }

    /**
     * 修改索引的 mapping
     *
     * Notice：mappings，修改时 properties 必须放在 body 里面
     * array:1 [▼
     * "acknowledged" => true
     * ]
     */
    public function putMapping()
    {
        $params = SearchBuilder::setIndex('elk')
            ->putMapping([
                'dynamic' => false,
                'properties' => [
                    'id' => [
                        'type' => 'integer'
                    ],
                    'name' => [
                        'type' => 'text'
                    ],
                    'age' => [
                        'type' => 'integer'
                    ]
                ]
            ], true) // 修改 mapping 必须设置为 true, 否则报错 {"error":{"root_cause":[{"type":"mapper_parsing_exception","reason":"Root mapping definition has unsupported parameters:  [mappings : {dynamic=false, properties={name={type=text}, age={type=integer}}}]"}],"type":"mapper_parsing_exception","reason":"Failed to parse mapping [_doc]: Root mapping definition has unsupported parameters:  [mappings : {dynamic=false, properties={name={type=text}, age={type=integer}}}]","caused_by":{"type":"mapper_parsing_exception","reason":"Root mapping definition has unsupported parameters:  [mappings : {dynamic=false, properties={name={type=text}, age={type=integer}}}]"}},"status":400}
            ->builder();

        dd(ElasticsearchClient::indices()->putMapping($params));
    }

    /**
     * 手动刷新索引，如果添加文档索引不刷新搜索不到
     *
     * array:1 [▼
     * "_shards" => array:3 [▼
     * "total" => 1
     * "successful" => 1
     * "failed" => 0
     * ]
     * ]
     */
    public function refreshIndex()
    {
        dd(ElasticsearchClient::indices()->refresh(['index' => 'elk']));
    }

    /**
     * 判断索引别名是否存在
     */
    public function aliasExists()
    {
        $params = SearchBuilder::existsAlias('elk_alias', 'elk');

        dd(ElasticsearchClient::indices()->existsAlias($params));
    }

    /**
     * 给索引添加别名
     *
     * array:1 [▼
     * "acknowledged" => true
     * ]
     */
    public function indexAlias()
    {
        $params = SearchBuilder::putAlias('elk_aliases', 'elk');

        dd(ElasticsearchClient::indices()->putAlias($params));
    }

    /**
     * 获取索引别名
     *
     * array:1 [▼
     * "elk" => array:1 [▼
     * "aliases" => array:1 [▼
     * "elk_alias" => []
     * ]
     * ]
     * ]
     */
    public function getAlias()
    {
        try {

            $res = current(ElasticsearchClient::indices()->getAlias(['index' => 'elk']));

            if (is_array($res)) {
                dd(current(array_keys(current($res))));
            }

            dd($res);

        } catch (\Exception $exception) {
            return response()->json(json_decode($exception->getMessage(), true));
        }
    }

    /**
     * 删除索引别名
     */
    public function delAlias()
    {
        $params = SearchBuilder::deleteAlias('elk_alias', 'elk');

        dd(ElasticsearchClient::indices()->deleteAlias($params));
    }

    /**
     * 移除老的别名，添加新的别名
     *
     * 更多操作：https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-aliases.html#alias-retrieving
     */
    public function updateAlias()
    {
        $params = SearchBuilder::updateAliases([
            'actions' => [
                // 删除索引 elk 的别名 elk_aliases_1
                ['remove' => ['index' => 'elk', 'alias' => 'elk_aliases_1']],
                // 给索引 elk 添加别名 elk_alias
                ['add' => ['index' => 'elk', 'alias' => 'elk_alias']],
                // 删除索引
                ['remove_index' => ['index' => 'demo']]
            ]
        ]);

        dd(ElasticsearchClient::indices()->updateAliases($params));
    }

    /**
     * 清空索引
     */
//    public function flushIndex()
//    {
//        dd(ElasticsearchClient::indices()->flush(['index' => 'elk']));
//    }

    /**
     * 创建文档
     */
    public function index()
    {
        $id = rand(100, 999);

        $params = SearchBuilder::setIndex('elk')
            ->setKey($id)
            ->setBody([
                'id' => $id,
                'name' => 'eto',
                'age' => 18
            ])
            ->builder();

        dd($id, ElasticsearchClient::index($params));
    }

    /**
     * 批量创建文档
     */
    public function bulk()
    {
        $data = collect([
            [
                'id' => 1000,
                'name' => '你好 elasticsearch、golang',
                'age' => 10
            ], [

                'id' => 1001,
                'name' => '我是测试数据',
                'age' => 18
            ], [
                'id' => 1002,
                'name' => '今天是周末，好像出去玩，但是外面下雨了，很遗憾',
                'age' => 10
            ]
        ]);

        $arr = ['body' => []];
        $data->each(function ($item) use (&$arr) {

            $arr['body'][] = [
                'index' => [
                    '_index' => 'elk',
                    '_id' => $item['id']
                ]
            ];
            $arr['body'][] = $item;

        });

        dd(ElasticsearchClient::bulk($arr));
    }

    /**
     * 获取文档
     */
    public function get()
    {
        $params = SearchBuilder::setIndex('elk')->get('102');
        dd(ElasticsearchClient::get($params));

        dd(ElasticsearchClient::get(['index' => 'elk', 'id' => 'eovnjHwBXhVl2PCkjKVd']));
    }

    /**
     * 获取多文档
     */
    public function mget()
    {
        $params = SearchBuilder::setIndex('elk')->mget(['103', '102', '101']);

        dd(collect(current(ElasticsearchClient::mget($params)))->pluck('_source')->filter()->toArray());
    }

    /**
     * 根据文档ID删除文档
     */
    public function del()
    {
//        $params = SearchBuilder::setIndex('elk')
//            ->delete('eovnjHwBXhVl2PCkjKVd')
//            ->unsetBody()
//            ->builder();

        $params = SearchBuilder::setIndex('elk')->delete('eovnjHwBXhVl2PCkjKVd');

        dd(ElasticsearchClient::delete($params));
    }

    /**
     * 根据条件删除文档
     *
     * array:12 [▼
     * "took" => 12
     * "timed_out" => false
     * "total" => 1
     * "deleted" => 1
     * "batches" => 1
     * "version_conflicts" => 0
     * "noops" => 0
     * "retries" => array:2 [▼
     * "bulk" => 0
     * "search" => 0
     * ]
     * "throttled_millis" => 0
     * "requests_per_second" => -1.0
     * "throttled_until_millis" => 0
     * "failures" => []
     * ]
     */
    public function delByQuery()
    {
//        $params = SearchBuilder::setIndex('elk')
//            ->setParams([
//                'match' => [
//                    'name' => '测试'
//                ]
//            ])
//            ->builder();

        $params = SearchBuilder::setIndex('elk')
            ->deleteByQuery([
                'match' => [
                    'name' => '测试'
                ]
            ]);

        dd(ElasticsearchClient::deleteByQuery($params));
    }

    /**
     * 根据查询条件统计文档个数
     *
     * array:2 [▼
     * "count" => 1
     * "_shards" => array:4 [▼
     * "total" => 1
     * "successful" => 1
     * "skipped" => 0
     * "failed" => 0
     * ]
     * ]
     */
    public function count()
    {
//        $params = SearchBuilder::setIndex('elk')
//            ->setParams([
//                'match' => [
//                    'name' => 'golang'
//                ]
//            ])
//            ->builder();

        $params = SearchBuilder::setIndex('elk')
            ->count([
                'match' => [
                    'name' => 'golang'
                ]
            ]);

        dd(ElasticsearchClient::count($params));
    }

    /**
     * 搜索
     *
     * Notice：索引设置里面的 refresh_interval 为 -1 不刷新文档，这里搜索结构为空
     */
    public function search()
    {
        $params = SearchBuilder::setIndex('elk')
            ->setParams([
                'match' => [
                    'name' => 'golang'
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    /**
     * 搜索分页
     */
    public function searchForPaginate()
    {
        $params = SearchBuilder::setIndex('elk')
            ->setParams([
                'match_all' => new \stdClass()
            ])
            ->paginate(2, 2)
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

    /**
     * 搜索排序
     */
    public function searchForOrderBy()
    {
        $params = SearchBuilder::setIndex('elk')
            ->setParams([
                'match_all' => new \stdClass()
            ])
//            ->orderBy(['age' => ['order' => 'asc'], 'id' => ['order' => 'desc']])
            ->orderByAscOrDesc(['age' => 'asc', 'id' => 'desc'])
//            ->orderByAsc(['age', 'id']) // 排序条件的顺序是很重要的。结果首先按第一个条件排序，仅当结果集的第一个 sort 值完全相同时才会按照第二个条件进行排序，以此类推。
            ->builder();

        dd(ElasticsearchClient::search($params));
    }

//    public function explain()
//    {
//        $params = SearchBuilder::setIndex('elk')
//            ->setParams([
//                'match' => [
//                    'name' => 'golang'
//                ]
//            ])
//            ->explain();
//
//        dd(ElasticsearchClient::search($params));
//    }

//    public function clearCache()
//    {
//        $params = ['index' => 'elk', 'query' => true];
//
//        dd(ElasticsearchClient::indices()->clearCache($params));
//    }

    /**
     * 创建模板
     */
    public function template()
    {
        $params = SearchBuilder::template()
            ->order(3)
            ->version(1)
            ->name('template')
            ->indexPatterns('elk*')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
                'refresh_interval' => -1
            ])
            ->putMapping([
                'dynamic' => false,
                'properties' => [
                    'id' => [
                        'type' => 'integer'
                    ],
                    'title' => [
                        'type' => 'keyword'
                    ],
                    'desc' => [
                        'type' => 'text'
                    ],
                    'date' => [
                        'type' => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss'
                    ],
                    'version' => [
                        'type' => 'keyword',
                        'index' => false
                    ]
                ]
            ])
            ->setAliases('template_1')
            ->builder();

        dd(ElasticsearchClient::indices()->putTemplate($params));
    }

    /**
     * 获取模板
     */
    public function getTemplate()
    {
        dd(ElasticsearchClient::indices()->getTemplate(['name' => 't*'])); // 单个
        dd(ElasticsearchClient::indices()->getTemplate(['name' => 'tem*,test*'])); // 多个使用,隔开
    }

    /**
     * 删除模板
     */
    public function deleteTemplate()
    {
        try {
            $re = ElasticsearchClient::indices()->deleteTemplate(['name' => 'test_template_default']);
        } catch (\Exception $exception) {
            $re = json_decode($exception->getMessage(), true);
        }

        dd($re);
    }

    /**
     * 创建索引：这里和上面 indexCreate 有点区别，
     *      这里不需要设置 settings 和 mappings 但是请求成功之后仍然有该配置，
     *      因为当前索引名会匹配上面的模板，自动填充 settings 和 mappings
     */
    public function createIndexByTemplate()
    {
        $params = SearchBuilder::setIndex('elk_demo')->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }

    public function getIndexByTemplate()
    {
        dd(ElasticsearchClient::indices()->getMapping(['index' => 'elk_demo']));
    }

    public function putIndexTemplate()
    {
        $params = SearchBuilder::putIndexTemplate()
            ->version(1)
            ->name('laravel_1')
            ->indexPatterns('laravel*')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
                'refresh_interval' => -1
            ])
            ->putMapping([
                'dynamic' => false,
                'properties' => [
                    'id' => [
                        'type' => 'integer'
                    ],
                    'title' => [
                        'type' => 'keyword'
                    ],
                    'desc' => [
                        'type' => 'text'
                    ],
                    'date' => [
                        'type' => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss'
                    ],
                    'version' => [
                        'type' => 'keyword',
                        'index' => false
                    ]
                ]
            ])
            ->setAliases('laravel_1s')
            ->builder();

        dd(ElasticsearchClient::indices()->putIndexTemplate($params));
    }

    public function getIndexTempate()
    {
        dd(ElasticsearchClient::indices()->getIndexTemplate(['name' => 'larav*']));
    }

    public function deleteIndexTempate()
    {
        dd(ElasticsearchClient::indices()->deleteIndexTemplate(['name' => 'laravel']));
    }

}
