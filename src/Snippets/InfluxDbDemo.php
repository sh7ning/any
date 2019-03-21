<?php

namespace App\Commands;

use InfluxDB\Client as InfluxDBClient;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;
use InfluxDB\Point;
use Jetea\Support\Helper;

class FlushLogs extends Command
{
    public function handle()
    {
//        $this->writePoints();
        $this->query();

        echo "\ndone\n";
    }

    private function getClient()
    {
        return new InfluxDBClient('influxdb', 8086);
    }

    /**
     * @param $db
     * @return \InfluxDB\Database
     * @throws \InfluxDB\Database\Exception
     */
    private function getOrCreateDb($db)
    {
        $client = $this->getClient();
        // fetch the database
        $database = $client->selectDB($db . '_db');

        if (! $database->exists()) {
            echo '新建数据库: ' . $db;
            // create the database with a retention policy
            $database->create(new RetentionPolicy($db, '60d', 1, true));
        } else {
            //为了测试每次写入是新数据
//            $database->drop();
//            echo '重建数据库: ' . $db;
//            $database->create(new RetentionPolicy($db, '60d', 1, true));
        }

        // select the database
        //todo test
        $database = $client->selectDB($db . '_db');

        return $database;
    }

    /**
     * @param $db
     * @return bool
     */
    private function dropDatabase($db)
    {
        $client = $this->getClient();
        // fetch the database
        $database = $client->selectDB($db);
        if ($database->exists()) {
            echo '删除数据库: ' . $db;
            $database->drop();
        }

        return true;
    }

    private function writePoints()
    {
        $database = $this->getOrCreateDb('grafana_logs');
        $db = $this->ctx->Ctx->loadDB();

        $names = [
            'btc',
            'eth',
            'eos',
        ];

        $data = [];
        $data1 = [];

        $time = strtotime('2019-03-01 00:00:00');
        for ($i = 0; $i < 30000; $i++) {
            foreach ($names as $name) {
                $value = rand(1000, 10000);
                $data[] = new Point(
                    'logs',
                    $value,
                    ['type' => 'price', 'name' => $name,],
                    ['raw_name' => $name],
                    $time
                );

                $data1[] = [
                    'name'          => $name,
                    'value'         => $value,
                    'created_at'    => date('Y-m-d H:i:s', $time),
                    'updated_at'    => date('Y-m-d H:i:s', $time),
                    'created_at_time'   => $time,
                ];

                if (count($data) >= 10000) {
                    $db->table('logs')->insert($data1);

                    $database->writePoints($data, Database::PRECISION_SECONDS);
                    $data = [];
                    $data1 = [];
                }
            }
            $time = $time + 60;
        }
    }

    private function query()
    {
        //@see https://docs.influxdata.com/influxdb/v1.7/query_language/data_exploration/#the-where-clause
        // executing a query will yield a resultset object
        $database = $this->getOrCreateDb('grafana_logs');
        $result = $database->query("select * from logs where \"type\" = 'price' and  LIMIT 1");

        print_r($result->getPoints());
    }
}
