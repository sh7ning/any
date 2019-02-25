<?php

namespace Tree6bee\Any\XML;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Jetea\Support\Helper;

/**
 * composer require "elasticsearch/elasticsearch": "~6.0"
 *
 * 模块接口声明文件
 * 备注：文件命名跟模块中的其他类不同，因为要防止模块声明类只能被实例化一次
 * 也就是只能用ctx->模块 来实例化，不能用loadC来实例化更多
 */
class Ctx extends \Ctx\Basic\Ctx
{
    private $index = 'users';
    private $type = 'profile';

    public function init()
    {
    }

    /**
     * @var Client
     */
    private $es;

    private function es()
    {
        if (! $this->es) {
            $this->es = ClientBuilder::create()->setHosts([
                Helper::env('ES_HOST'),
            ])->setRetries(0)->build();
        }

        return $this->es;
    }

    public function getIndex()
    {
        //获取索引
        $response = $this->es()->indices()->get([
            'index' => $this->index,
        ]);
        Helper::dd($response);
    }

    public function createIndex()
    {
        $response = $this->es()->indices()->exists([
            'index' => $this->index,
        ]);

        if ($response) {
            Helper::dd('已经存在索引 ' . $this->index);
        }

        $params = [
            'index' => $this->index,
            'body' => [
                'settings' => [
                    'number_of_shards'      => 5,
                    'number_of_replicas'    => 0,
                ],
                'mappings'  => [
                    $this->type   => [
                        '_source'       => [
                            'enabled' => true
                        ],
                        'dynamic'       => 'strict', //throw an exception if an unknown field is encountered
                        'properties'    => [
                            'name'  => [
                                'type'      => 'text',
                                'analyzer'  => 'ik_smart',
                                'search_analyzer' => 'ik_smart',
                            ],
                            'is_disable'    => [
                                'type'      => 'keyword',
                            ],
                            'gender'    => [
                                'type'      => 'integer',
                            ],
                            'birthday'  => [
                                'type'      => 'date',
                                'format'    => "yyyy-MM-dd HH:mm:ss"
                            ],
                            'memo'      => [
                                'type'      => 'text',
                                'analyzer'  => 'ik_smart',
                                'search_analyzer' => 'ik_smart',
                            ],
                            'location'  => [
                                'type'      => 'geo_point',
                            ],
                        ]
                    ],
                ],
            ]
        ];

        $response = $this->es()->indices()->create($params);

        Helper::dd($response);
//        array(3) {
//            ["acknowledged"]=>
//            bool(true)
//            ["shards_acknowledged"]=>
//            bool(true)
//            ["index"]=>
//            string(5) $this->index
//        }
    }

    public function deleteIndex()
    {
        //获取索引
        $response = $this->es()->indices()->delete([
            'index' => $this->index,
        ]);
        Helper::dd($response);
    }

    public function insert()
    {
        $id = $this->ctx->Ctx->loadDB()->table('profile')->insertGetId([
            'name'      => '中文名字',
            'is_disable' => 'NO',
            'gender'    => 0,
            'birthday'  => date('Y-m-d H:i:s', time() - rand(60 * 60 *24 * 360, 60 * 60 *24 * 360 * 30)),
            'memo'      => '一段中文的简单介绍文字',
            'lat'       => '30.54916000',
            'lng'       => '66.06761000'
        ]);

        Helper::dd('创建成功' . $id);
    }

    public function indexDocument()
    {
        //单个索引
//        $params = [
//            'index'     => $this->index,
//            'type'      => $this->type,
//            'id'        => 0,
//            'body'      => [
//                'name'      => 'first name yo~',
//                'gender'    => 1,
//                'birthday'  => '2019-01-01 01:00:01',
//                'memo'      => 'this is a xx memo',
//                'location'  => [
//                    'lat'       => '30.54916000',
//                    'lon'       => '104.06761000'
//                ],
//            ]
//        ];
//
//        $response = $this->es()->index($params);
//        Helper::dd($response);

        //批量索引
        $collections = $this->ctx->Ctx->loadDB()->select('select * from profile');
        $responses = [];
        $params = ['body' => []];
        foreach ($collections as $i => $row) {
            $params['body'][] = [
                'index' => [
                    '_index'    => $this->index,
                    '_type'     => $this->type,
                    '_id'       => $row['id'],
                ]
            ];

            $params['body'][] = [
                'name'      => $row['name'],
                'is_disable' => $row['is_disable'],
                'gender'    => $row['gender'],
                'birthday'  => $row['birthday'],
                'memo'      => $row['memo'],
                'location'  => [
                    'lat'       => $row['lat'],
                    'lon'       => $row['lng'],
                ],
            ];

            if (($i + 1) % 1000 == 0) { //$i 从 0 开始的
                $responses[] = $this->es()->bulk($params);

                // erase the old bulk request
                $params = ['body' => []];
            }
        }

        // Send the last batch if it exists
        if (!empty($params['body'])) {
            $responses[] = $this->es()->bulk($params);
        }

        Helper::dd($params, $responses);

        return true;
    }

    public function updateDocument()
    {
        $params = [
            'index'     => $this->index,
            'type'      => $this->type,
            'id'        => 0,
            'body'      => [
                'doc' => [
                    'gender'    => 1,
                    'birthday'  => '2019-01-01 01:00:01',
                    'memo'      => 'this is a boy memo',
                    'location'  => [
                        'lat'       => '30.54916000',
                        'lon'       => '104.06761000'
                    ],
                ],
                'doc_as_upsert' => true, //如果不存在 id，则新增
            ]
        ];

        $response = $this->es()->update($params);
        Helper::dd($response);
    }

    public function getDocument($id)
    {
        $response = $this->es()->get([
            'index'     => $this->index,
            'type'      => $this->type,
            'id'        => $id,
        ]);
        Helper::dd($response);
    }

    public function searchDocument($keyword)
    {
//        $params = [
//            'index' => $this->index,
//            'type'  => $this->type,
//            'body'  => [
//                'query' => [
//                    'match' => [ //match:匹配, match_phrase:相邻一致的短语匹配
//                        'memo' => $keyword,
//                    ]
//                ]
//            ]
//        ];

        //todo 其他非搜索字段过滤，stop word
        $params = [
            'index' => $this->index,
            'type'  => $this->type,
            'body'  => [
//                '_source'   => false,
//                '_source'   => ['name', 'memo'],
                'query'     => [
                    'bool'  => [
                        'must'      => [
                            'multi_match'   => [ //多字段搜索
                                'query'         => $keyword,
                                'fields'        => ['name^2', 'memo'], //name 权重是2
                            ]
                        ],
                        'filter'    => [
                            [
                                'geo_distance'  => [
                                    'distance'      => '20km',
                                    'location'      => [
                                        'lat'           => '30.54916000',
                                        'lon'           => '104.06761000',
                                    ],
                                ],
                            ],
                            [
                                'term'  => [
                                    'gender'        => 0,
                                ]
                            ],
                            [
                                'term'  => [
                                    'is_disable'    => 'NO',
                                ]
                            ],
                            [
                                'range' => [
                                    'birthday'      => [
                                        'gte'   => '1993-11-06 10:31:01',
                                        'lte'   => '2017-01-02 00:00:00',
                                        'format'    => "yyyy-MM-dd HH:mm:ss"
                                    ],
                                ],
                            ]
                        ],
                    ],
                ],
                'highlight' => [
                    'fields'    => [
                        'name'      => new \stdClass(), //高亮的字段
//                        'memo'      => new \stdClass(),
                    ],
                ],
                'from'      => 0,
                'size'      => 10,
            ]
        ];

        $response = $this->es()->search($params);
        Helper::dd($response);
    }

    public function deleteDocument()
    {
        $response = $this->es()->delete([
            'index'     => $this->index,
            'type'      => $this->type,
            'id'        => 0
        ]);
        Helper::dd($response);
    }
}
