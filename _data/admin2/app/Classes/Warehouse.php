<?php
namespace App\Classes;

use App\Extensions\Database\FManager as DB;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\MessageFormatter;
use Monolog\Logger;
use Illuminate\Support\Collection;
use Silex\Application;

/**
 * Class for pushing data to our own data warehouse, the warehouse can then be used
 * by third-parties or internally to query data from multiple brands in one place.
 */
class Warehouse
{
    /** @var Application $app */
    private $app;

    /** @var Client $client */
    private $client;

    public function __construct($app, $timeout = 2.0)
    {
        $this->app = $app;

        $options = [
            'base_uri' => $app['warehouse']['url'],
            'timeout' => $timeout,
        ];
        
        if (getenv('WAREHOUSE_TEST')) {
            $stack = HandlerStack::create();
            // https://docs.aws.amazon.com/aws-sdk-php/v2/api/class-Guzzle.Log.MessageFormatter.html
            $stack->push(
                Middleware::log(
                    new Logger('Logger'),
                    new MessageFormatter('Host: {host}, Resource: {resource}, Url: {url}, Res Body: {res_body}')
                )
            );
            $options['handler'] = $stack;
        }
        
        $this->client = new Client($options);
    }

    /**
     * @param string $table The table to get data from.
     * @param string $where_sql Optional WHERE statement, if omitted we get everything.
     * @param array $fields Optional fields to get, if omitted we get all columns / fields.
     * @param boolean $bonus_filter Optional filter to filter records if the bonus code exists
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function pushData($table, $where_sql = '', $fields = [], $bonus_filter = false, $products = [])
    {
        $data = null;
        $db = DB::table($table);
        if(!empty($where_sql)){
            $db->whereRaw($where_sql);
        }

        if($products){
            $db->whereIn('product', $products);
        }
        

        if($bonus_filter){
            if($table !== "users"){
                $db->selectRaw("$table.*");
                $db->join("users", "users.id", "$table.user_id");
                $db->whereRaw("trim(users.bonus_code) <> ''");
           } else {
            $db->whereRaw("trim(bonus_code) <> ''");
           }

        }

        $data = empty($fields) ? $db->get() : $db->get($fields);
        if(count($data) === 0){
            // Nothing to push.
            if(phive()->getMiscCache('is-warehouse-normal-flow')) {
                //set cache to rerun the warehouse push
                phive()->miscCache("rerun-warehouse-push-{$table}",  true);
                
            }

            return false;
        }

        $headers = [
            'Content-type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer '.getenv('WAREHOUSE_KEY')
        ];

        if (getenv('WAREHOUSE_TEST')) {
            $this->app['monolog']->addError('data', [$where_sql, $fields, $headers]);
        }

        try {
            $res = $this->client->post('input/', [
                'headers' => $headers,
                'json' => ['table' => $table, 'data' => $data]
            ]);
        } catch (\Exception $e) {
            if ($this->app['debug']) {
                return $this->app->abort('408', $e->getMessage());
            } else {
                $this->app['monolog']->addError($e->getMessage());
                return $this->app->abort('408', "Warehouse service error");
            }
        }

        $body = $res->getBody();

        if(empty($body)){
            $this->app['monolog']->addError("The warehouse should return a JSON body but body is empty.");
            return false;
        }

        $arr = json_decode($body, true); 

        if(!$arr){
            $this->app['monolog']->addError("The warehouse should return a JSON body but body is: $body");
        }
        
        if(!isset($arr['success'])){
            $this->app['monolog']->addError("The warehouse should return a JSON body but body with a success field but did not, body: $body");
        }

        if(!$arr['success']){
            $this->app['monolog']->addError("The warehouse returned success false, warehouse error: {$arr['result']}");
        }
        
        return true;
    }
}
