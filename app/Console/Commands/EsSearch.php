<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Vinhson\Elasticsearch\Facades\ElasticsearchClient;
use Vinhson\Elasticsearch\Facades\SearchBuilder;

class EsSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:search';

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
        $params = SearchBuilder::setParams([
            "bool" => [
                "must" => [
                    "match" => [
                        "name" => "模型dingo"
                    ]
                ]
            ]
//            "match_all" => (object)[]
        ])
            ->builder();

        dd(ElasticsearchClient::search($params));
    }
}
