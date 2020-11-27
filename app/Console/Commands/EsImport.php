<?php

namespace App\Console\Commands;

use App\Model\Article;
use Illuminate\Console\Command;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;

class EsImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:import {index}';

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
        Article::query()
            ->chunkById(10, function ($articles) {

                $this->info(sprintf("正在同步 ID 范围为 %s 至 %s 的数据", $articles->first()->id, $articles->last()->id));

                $params = ["body" => []];
                foreach ($articles as $article) {

                    $data = $article->toESArray();
                    $params["body"][] = [
                        "index" => [
                            "_index" => $this->argument("index"),
                            "_id" => $article->getKey(),
                        ]
                    ];

                    $params["body"][] = $data;
                }

                try{
                    ElasticsearchClient::bulk($params);
                }catch (\Exception $exception) {
                    $this->error("批量添加失败");
                }
            });
    }
}
