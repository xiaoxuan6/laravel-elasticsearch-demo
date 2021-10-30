<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('info', 'EsController@info');
Route::get('ping', 'EsController@ping');

// 索引
Route::get('exists', 'EsController@indexExists');
Route::get('del', 'EsController@indexDel');
Route::get('create', 'EsController@indexCreate');
Route::get('create-and-aliases', 'EsController@indexCreateAndAliases');
Route::get('open-close', 'EsController@indexCloseOrOpen');
Route::get('stats', 'EsController@stats');
Route::get('get-setting', 'EsController@getSetting');
Route::get('put-setting', 'EsController@putSetting');
Route::get('get-mapping-all', 'EsController@getMapping');
Route::get('get-field-mapping', 'EsController@getFieldMapping');
Route::get('put-mapping', 'EsController@putMapping');
// 索引别名操作
Route::get('index-alias-exists', 'EsController@aliasExists');
Route::get('index-alias', 'EsController@indexAlias');
Route::get('get-index-alias', 'EsController@getAlias');
Route::get('del-index-aliases', 'EsController@delAlias');
Route::get('update-alias', 'EsController@updateAlias');
// 刷新索引
Route::get('refresh-index', 'EsController@refreshIndex');
//Route::get('flush-index', 'EsController@flushIndex');
//Route::get('cache', 'EsController@clearCache');

// 索引模板
Route::get('template', 'EsController@template');
Route::get('get_template', 'EsController@getTemplate');
Route::get('del-template', 'EsController@deleteTemplate');
Route::get('put-index-template', 'EsController@putIndexTemplate');
Route::get('get_index-template', 'EsController@getIndexTempate');
Route::get('del-index-template', 'EsController@deleteIndexTempate');
// 根据模板操作索引
Route::get('index-template', 'EsController@createIndexByTemplate');
Route::get('get-index-template', 'EsController@getIndexByTemplate');

// 文档
Route::get('index', 'EsController@index');
Route::get('bulk', 'EsController@bulk');
Route::get('get', 'EsController@get');
Route::get('mget', 'EsController@mget');
Route::get('delete', 'EsController@del');
Route::get('delByQuery', 'EsController@delByQuery');
Route::get('count', 'EsController@count');
Route::get('search', 'EsController@search');
Route::get('search-paginate', 'EsController@searchForPaginate');
Route::get('search-orderby', 'EsController@searchForOrderBy');
// 批量操作
Route::get('indices', 'BulkController@indices');
Route::get('indices-index', 'BulkController@index');
Route::get('indices-delete', 'BulkController@delete');
Route::get('indices-update', 'BulkController@update');

// 搜索
Route::get('searchWildcard', 'MappingController@searchWildcard');
Route::get('searchFuzzy', 'MappingController@searchFuzzy');
Route::get('searchRegexp', 'MappingController@searchRegexp');
// 搜索---中文
Route::get('searchAnalyzer', 'MappingController@searchAnalyzer');
Route::get('importAnalyzer', 'MappingController@importAnalyzer');
Route::get('searchAnalyzerPrefix', 'MappingController@searchAnalyzerPrefix');
Route::get('searchAnalyzerWildcard', 'MappingController@searchAnalyzerWildcard');

//Route::get('explain', 'EsController@explain');

// 自定义分词器
Route::get('analyze-body', 'AnalyzerController@analyze');
Route::get('index-create', 'AnalyzerController@index');
Route::get('data', 'AnalyzerController@data');
Route::get('data-search', 'AnalyzerController@search');
// 中文分词器
Route::get('index-create-ik', 'AnalyzerController@ikAnalyzer');
Route::get('data-ik', 'AnalyzerController@ikData');
Route::get('data-search-ik', 'AnalyzerController@ikSearch');

// mapping parameters
Route::get('import', 'MappingController@import');
// copy 将字段复制到新的字段中
Route::get('copy', 'MappingController@copy');
Route::get('searchCopy', 'MappingController@searchCopy');
// index 字段不允许搜索
Route::get('index', 'MappingController@index');
Route::get('searchIndex', 'MappingController@searchIndex');
// store 设置搜索结果中可展示字段
Route::get('store', 'MappingController@store');
Route::get('searchStore', 'MappingController@searchStore');
// 正常搜索设置可展示字段
Route::get('searchSource', 'MappingController@search');
Route::get('searchAndStore', 'MappingController@searchAndStore');
// enabled 不能被搜索和store操作,只能在结果中展示
Route::get('enabled', 'MappingController@enabled');
Route::get('importEnabled', 'MappingController@importEnabled');
Route::get('searchEnabled', 'MappingController@searchEnabled');
// ignore-above
Route::get('ignore-above', 'MappingController@ignoreAbove');
Route::get('importIgnoreAbove', 'MappingController@importIgnoreAbove');
Route::get('searchIgnoreAbove', 'MappingController@searchIgnoreAbove');
// index_prefixes
Route::get('index_prefixes', 'MappingController@indexPrefixes');
Route::get('importPrefix', 'MappingController@importPrefix');
Route::get('searchPrefix', 'MappingController@searchPrefix');
// properties
Route::get('properties', 'MappingController@properties');
Route::get('importProperties', 'MappingController@importProperties');
Route::get('searchProperties', 'MappingController@searchProperties');

// mapping parameters 2
// null_value
Route::get('setProTemplate', 'Mapping2Controller@setProTemplate');
Route::get('null_value', 'Mapping2Controller@null');
Route::get('importData', 'Mapping2Controller@index');
Route::get('searchNull', 'Mapping2Controller@search');
// doc-values
Route::get('doc_values', 'Mapping2Controller@doc_values');
