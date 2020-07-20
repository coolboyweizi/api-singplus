<?php

namespace Tests\Unit;

use Carbon\Carbon;
use FeatureTest\SingPlus\MongodbClearTrait;
use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\Integer;
use SingPlus\Contracts\DailyTask\Constants\DailyTask;
use FeatureTest\SingPlus\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use SingPlus\Domains\Boomcoin\Models\Order;
use SingPlus\Domains\Boomcoin\Repositories\OrderRepository;
use SingPlus\Domains\Boomcoin\Repositories\ProductRepository;
use SingPlus\Domains\Boomcoin\Services\BoomcoinService;
use SingPlus\Domains\Works\Models\WorkTag;
use SingPlus\Domains\Works\Repositories\WorkRepository;
use SingPlus\Domains\Works\Repositories\WorkTagRepository;

class ExampleTest extends TestCase
{
    use MongodbClearTrait;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertTrue(true);
    }

    public function testCombine(){

        $old = [
            [
                'id' => "1",
                "name" => "111"
            ],
            [
                'id' => "2",
                "name" => "222"
            ]
        ];

        $new = [
            [
                'id' => '2',
                "name" => "212"
            ],
            [
                'id' => '3',
                "name" => "333"
            ]
        ];

        $oldJsonStr = json_encode($old);
        $newJsonStr = json_encode($new);
        print $oldJsonStr;

        print $newJsonStr;
        print $this->combineAccompanimentData($oldJsonStr, $newJsonStr);
    }

    public function testRemove(){
        $oldJsonStr = "[{\"id\":\"1\"},{\"id\":\"2\"}, {\"id\":\"3\"}]";
        $newJsonStr = $this->removeAccompanimentItem($oldJsonStr, 'id', '2');
        print $newJsonStr;
    }

    public function testPreg(){
        $string = '11111 2222#dsadas #1111 #11222 sadsada#1123 #abcsd#dsada 22233 #dsad1a 2222#dsada33###qqqqqq #wwww uuuu #uuuu1';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);
        $string = '11111';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);
        $string = '11111#';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);

        $string = ' 11111# ';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);

        $string = '11111 # #11100 ';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);

        $string = '#11111 # #11100 ';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);

        $string = '#11111###11100 ';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);

        $string = '#boe[憨笑]ggff';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);

        $string = '#11111###11111《》 ';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);

        $string = '#11111###11111_asd, ';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);

        $string = 'Come to join the #Collab I started#Abc,love#Ok-i#op!o#ops`#但是，11#so。事实上#的当时[dd#倒四颠三]ss#iiio*love
        #isdsd.love#opkds[love#jony]love#possd{l#pol}#io90~lo#plokk+lo#small=lo#end$lo#splictsd|love#ovps\ds#C’estlave#okai：love###11111_asd';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);

        $string = '#C’estlave#okai：love';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);

        $string = '#lovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelovelove';
        $tags = $this->getTagsFromDesc($string);
        var_dump($tags);
    }

    public function testSearchTag(){
        $words = 'love';

        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'lovee',
            'source' => 'user'
        ]);

        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'love',
            'join_count' => 1
        ]);

        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'lovelys',
            'source' => 'official'
        ]);

        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'lovsssss'
        ]);

        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'Love'
        ]);

        factory(\SingPlus\Domains\Works\Models\WorkTag::class)->create([
            'title' => 'Loveass',
            'source' => 'official'
        ]);

        $tag = WorkTag::firstOrNew(['title' => 'aaaa']);
        $tag->join_count = $tag->join_count + 1;
        $tag->save();
        $this->assertDatabaseHas('work_tags',[
            'title' => 'aaaa',
            'join_count' => 1
        ]);

        $tag = WorkTag::where('title', 'aaaa')->first();
        var_dump($tag);
        $regexs = $this->buildWorkTagSearchRegexs($words);
        $result = $this->searchWorkTag($regexs);
        var_dump($result);

    }

    public function testGetWorksByTag(){
        $workRepo = new WorkRepository();
        factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'work_tags' => [
                'love',
                'world'
            ],
            'display_order' => 100,
        ]);

        factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'work_tags' => [
                'love'
            ],
            'display_order' => 200
        ]);

        factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'work_tags' => [
                'world'
            ],
            'display_order' => 300
        ]);

        factory(\SingPlus\Domains\Works\Models\Work::class)->create([
            'work_tags' => [
                'worldii'
            ],
            'display_order' => 300
        ]);

        print sprintf("\ntestGetWorksByTag\n");
        $result = $workRepo->findAllByWorkTagForPagination('woRLd',10000, true, 10);
        $values = $result->map(function($work, $__){
           return (object)[
               'tags' => $work->work_tags,
           ];
        });
        var_dump($values);
        print sprintf("\ntestGetWorksByTag\n");

    }

    private function searchWorkTag($regexs):Collection{
        $workTagRepo = new WorkTagRepository();
        $defaultSize = 3;
        $result = $workTagRepo->searchWorkTagByRegex($regexs->fullMatch, $defaultSize, null);
        if ($result->count() < $defaultSize){
            $normalResult = $workTagRepo->searchWorkTagByRegex($regexs->normalMatch,
                $defaultSize - $result->count(),
                WorkTag::SOURCE_OFFICIAL);
            $result = collect(['fullMatch' => $result->all(), 'normalMatch' => $normalResult->all()])->flatten();
        }
        // 排序的时候不区分大小写
        $sorted = $result->sort(function($a, $b){
            $upperA = strtoupper($a->title);
            $upperB = strtoupper($b->title);
            if ($upperA == $upperB){
                return 0;
            }

            return ($upperA < $upperB) ? -1 : 1;
        });
        $objs = $sorted->map(function($item, $__){
            return (object)[
                'title' => $item->title
            ];
        });
        return $objs;
    }

    private function buildWorkTagSearchRegexs(string $str):\stdClass {

        // 完全匹配str，并且不区分大小写
        $fullMatch = '/^'.$str.'$/i';

        // 匹配str开头不包括str在内，并且不区分大小写
        $regexNormal = '/^'.$str.'.+/i';
        return (object)[
            'fullMatch' => $fullMatch,
            'normalMatch'  => $regexNormal
        ];
    }

    private function removeAccompanimentItem(?string $savedData, string $key, $value) :?string{

        if (!$savedData){
            return null;
        }
        $savedDataJson = json_decode($savedData);
        $newDataJson = [];
        foreach ($savedDataJson as $data){
            if ($data->$key != $value){
                array_push($newDataJson, $data);
            }
        }
        return json_encode($newDataJson);

    }

    private function combineAccompanimentData(?string $savedData, string $newData): string{
        if (!$savedData){
            return $newData;
        }

        $savedDataJson = json_decode($savedData);
        $newDataJson = json_decode($newData);
        $savedCollect = collect($savedDataJson);
        foreach ($newDataJson as $data){
            if (!$savedCollect->contains('id', $data->id)){
                array_push($savedDataJson, $data);
            }
        }
        return json_encode($savedDataJson);
    }

    private function getTagsFromDesc(string $str) : array {
        var_dump($str);
        $arr = array();
//        preg_match_all('/#[^\s\\\$\[\]\^\|¥@%`~&<>(){}#=+\-*\/:;,\.?!\"：；，。？！“”‘’]{1,100}/u', $str, $arr);
//        preg_match_all('/#[^<>#:;@=&~¥,。？！：；%“”~，`\?\!\(\)\s\-\^\.\/\*\+\|\[\]\{\}\\\"\$]+((?!\s)|(?!#)|$)/', $str, $arr);
        preg_match_all('/#([^\p{P}\p{Z}\p{S}]|_){1,100}/u', $str, $arr);
        $arr = array_unique($arr[0]);
        $result = [];
        foreach ($arr as $item){
            $item = str_replace('#', '', $item);
            $result[] = $item;
        }
        return $result;
    }

    public function testArrUnique(){
        $arr = array();

// 创建100000个随机元素的数组
        for($i=0; $i<100; $i++){
            $arr[] = mt_rand(1,99);
        }

// 记录开始时间
        $starttime = $this->getMicrotime();

// 去重
        $arr = array_unique($arr);

// 记录结束时间
        $endtime = $this->getMicrotime();

        $arr = array_values($arr);

        echo 'unique count:'.count($arr).'<br>';
        echo 'run time:'.(float)(($endtime-$starttime)*1000).'ms<br>';
        echo 'use memory:'.$this->getUseMemory();

    }

    public function testQuickUnique(){
        $arr = array();

// 创建100000个随机元素的数组
        for($i=0; $i<10; $i++){
            $arr[] = mt_rand(1,5);
        }
        print_r($arr);
// 记录开始时间
        $starttime = $this->getMicrotime();

// 使用键值互换去重
        $arr = array_flip($arr);
        $arr = array_flip($arr);
        print_r($arr);
// 记录结束时间
        $endtime = $this->getMicrotime();

        $arr = array_values($arr);
        print sprintf("\n------array count :--%d-------\n", count($arr));
        print_r($arr);

        echo 'unique count:'.count($arr).'<br>';
        echo 'run time:'.(float)(($endtime-$starttime)*1000).'ms<br>';
        echo 'use memory:'.$this->getUseMemory();

        $string = "dsadas/dsada";
        print_r(explode('/', $string));

        $string = "dsadas";
        print_r(explode('/', $string));
        $string = "";
        print_r(explode('/', $string));
    }

    /**
     * 获取使用内存
     * @return float
     */
    function getUseMemory(){
        $use_memory = round(memory_get_usage(true)/1024,2).'kb';
        return $use_memory;
    }

    /**
     * 获取microtime
     * @return float
     */
    function getMicrotime(){
        list($usec, $sec) = explode(' ', microtime());
        return (float)$usec + (float)$sec;
    }

    function testGetProductLists(){
        $abbr = 'CN';
        $orderRepo = new OrderRepository();
        $prodRepo = new ProductRepository();
        $service = new BoomcoinService($orderRepo, $prodRepo);
        $products = $service->getProductList($abbr);
        print_r($products->toArray());
        $this->assertDatabaseHas('boomcoin_product', [
            'dollars' => 1,
            'coins' => 1000
        ]);

        $order = new Order();
        $order->user_id = 'dsadsasadsaasd';
        $order->save();
        print sprintf('order id is %s', $order->id);
    }

}
