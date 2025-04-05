<?php

namespace App\Http\Controllers;

use App\Models\Postback;
use App\Models\PostbackCounts;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{



    public function index(Request $request)
    {

        $postbacks_count = PostbackCounts::orderBy('id', 'desc')->paginate(50);

//        Schema::table('postback_counts', function (Blueprint $table) {
//            $table->string('last_postback')->nullable();
//        });


        return view('almabet.index', [
            'postbacks_count' => $postbacks_count
        ]);

    }

    public function postbacks(Request $request)
    {
        $postbacks = Postback::orderBy('id', 'desc')->paginate(50);


        dd($postbacks);
    }

    public function newPostback(Request $request)
    {

        $post = $request->post();

        if ($post) {

//            Schema::rename('postback', 'postbacks');
            $postback = new Postback();
            $postback->name = $post['name'];
            if(!empty($post['reg'])) {
                $data = json_decode($post['reg'], true);
                $postback->reg = json_encode($data);

            }
            if(!empty($post['dep'])){
                $data = json_decode($post['dep'], true);
                $postback->dep = json_encode($data);

            }

            $postback->save();
            dd($postback->toArray());
        }


        return view('almabet.postback.new', [

        ]);

    }

    public function editPostback(Request $request, Postback $postback)
    {

        $post = $request->post();

        if ($post) {


            $postback->name = $post['name'];
            if(!empty($post['reg'])) {
                $data = json_decode($post['reg'], true);
                $postback->reg = json_encode($data);

            }
            if(!empty($post['dep'])){
                $data = json_decode($post['dep'], true);
                $postback->dep = json_encode($data);

            }

            $postback->save();
        }



        return view('almabet.postback.edit', [
            'postback' => $postback
        ]);

    }


    public function log(Request $request)
    {
        $test = '{"psId":"1","goal":"reg","pid":"2147478594","cur":"USD","btag":"1678289","type":"registration","clickid":"7619"}';

//        $data = json_decode($test, true);

        if (isset($data['psId']) && !empty($data['psId'])) {

//            Schema::table('postback_counts', function (Blueprint $table) {
//                $table->mediumInteger('dep_summ')->default(0);
//            });

            $pid = $data['pid'];
            $btag = $data['btag'];
            $goal = $data['goal'];
            $goal_count = $goal.'_count';

            $postback_count = PostbackCounts::where('pid', $pid)->where('btag', $btag)->first();

            if (!$postback_count) {
                $postback_count = new PostbackCounts();
                $postback_count->btag = $btag;
                $postback_count->pid = $pid;
            }
            $postback_count->$goal_count = $postback_count->$goal_count + 1;
            $postback_count->save();

            $postback = Postback::find($data['psId']);

            $goal_postback = $postback->$goal;
            foreach ($data as $k => $v) {
                $goal_postback = str_replace("{".$k."}", $v, $goal_postback);
            }
            $postback_data = json_decode($goal_postback, true);

            foreach ($postback_data as $item) {
                $url = $item['url'];

                Log::channel('almabet_postback')->info("re postback - <br>" . $url);
                $responce = Http::get($url);

                $res = [
                    'code' => $responce->status(),
                    'responce' => $responce->body()
                ];

                Log::channel('almabet_postback')->info("result {$postback->id} - <br>" . json_encode($res));
            }

            dd($postback_data, $data);
        }



//        print_r('migrate - ');
//        Artisan::call('migrate');
//        echo 'done';
//
//        dd('done');

        $date = $request->get('date');
        if ($date) {
            $date_nau = new Carbon($date);
        } else {
            $date_nau = new Carbon();
        }


        $date_str = $date_nau->format("Y-m-d");
        $date_pre = $date_nau->addDays(-1);

        if (Storage::disk('logs')->exists("almabet-postback-$date_str.log")) {
            $monolog = Storage::disk('logs')->get("almabet-postback-$date_str.log");
        } else {
            $monolog = 'not file';
        }

//        $monolog = htmlspecialchars($monolog);
        $monolog = str_replace('['.$date_nau->format('Y'), '<hr><b>['.$date_nau->format('Y'), $monolog);
        $monolog = str_replace('] ', ']</b> ', $monolog);

        $monolog = utf8_decode($monolog);

        return view('api.log', [
            'route' => 'almabet_log',
            'log' => $monolog,
            'date_str' => $date_str,
            'date_pre' => $date_pre
        ]);
    }
}
