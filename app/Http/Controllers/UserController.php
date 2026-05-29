<?php

namespace App\Http\Controllers;

use App\Models\AppCalendar;
use App\Models\ImportCalendars;
use App\Models\User;
use App\Models\UserCalendar;
use App\Services\ICal;
use App\Services\iCalReader;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{

    public function allUsers(Request $request)
    {
        $users = DB::table('users')->paginate(15);

//        echo "<div class='pre'>";
////        $data = $users->toArray();
////        print_r($data['data'][0]);
//
//        echo "</div>";
        return view('user.all_users', [
            'users' => $users
        ]);
    }


    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $user_id = $user->id;

//        Artisan::call('migrate');
        $user_calendars = $user->importCalendars()->get();


        return view('admin.index');
    }

    public function addUserCalendar(Request $recuest)
    {
        $user = Auth::user();
        $user_id = $user->id;

        $userCalendarData['userId'] = $user_id;

        if (!empty($recuest->post('fb_cal_link'))) {
            $fb_cal_link = $recuest->post('fb_cal_link');

            $userCalendar = UserCalendar::firstOrCreate(
                ['calendarId' => $fb_cal_link]
            );
            $userCalendar->userId = $user_id;
            $userCalendar->source = $fb_cal_link;
            $userCalendar->type_events = 'facebook';
            $res = $userCalendar->save();


        }

        if ($res) {
            $message = 'calendar save';
        } else {
            $message = 'error save calendar';
        }

        return view('messages', [
            'message' => $message
        ]);
    }
}
