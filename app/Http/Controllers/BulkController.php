<?php

namespace App\Http\Controllers;

use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class BulkController extends Controller
{
    public function indices()
    {
        $params = SearchBuilder::setIndex('indices')
            ->putSettings([
                'number_of_shards' => 1,
                'number_of_replicas' => 0
            ])
            ->putMapping([
                'dynamic' => false,
                'properties' => [
                    'id' => [
                        'type' => 'integer'
                    ],
                    'name' => [
                        'type' => 'text'
                    ]
                ]
            ])
            ->builder();

        dd(ElasticsearchClient::indices()->create($params));
    }

    /**
     * 创建新文档或替换已有文档
     */
    public function index()
    {
        $data = [
            ['id' => 100, 'name' => 'eto', 'age' => 10],
            ['id' => 101, 'name' => 'vinhson', 'age' => 20],
            ['id' => 102, 'name' => 'ruixin', 'age' => 18],
        ];

        $body = [];
        foreach ($data as $datum) {
            $body['body'][] = [
                'index' => [
                    '_id' => $datum['id'],
                    '_index' => 'indices'
                ]
            ];

            $body['body'][] = $datum;
        }

        dd(ElasticsearchClient::bulk($body));
    }

    /**
     * 删除一个或多个文档。
     */
    public function delete()
    {
        $ids = [100, 102];

        $params = [];
        foreach ($ids as $id) {
            $params['body'][] = [
                'delete' => [
                    '_id' => $id,
                    '_index' => 'indices'
                ]
            ];
        }

        dd(ElasticsearchClient::bulk($params));
    }

    /**
     * 局部更新文档
     */
    public function update()
    {
        $ids = [101];

        $params = [];
        foreach ($ids as $id) {
            $params['body'][] = [
                'update' => [
                    '_id' => $id,
                    '_index' => 'indices'
                ]
            ];

            $params['body'][] = [
                'doc' => [
                    'age' => 20,
                    'name' => 'vinhson01'
                ]
            ];
        }

        dd(ElasticsearchClient::bulk($params));
    }
}
