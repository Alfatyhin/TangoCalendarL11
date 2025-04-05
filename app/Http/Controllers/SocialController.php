<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Validator;
use Socialite;
use Exception;
use Auth;

class SocialController extends Controller
{
    public function facebookRedirect()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function loginWithFacebook()
    {
        try {

            $user = Socialite::driver('facebook')->user();
            $isUser = User::where('fb_id', $user->id)->first();

            if($isUser){
                Auth::login($isUser);
                return redirect('/');
            }else{

                if (empty($user->email)) {
                    $email = "{$user->id}@facebook.id";
                } else {
                    $email =  $user->email;
                }
                $createUser = new User();
                $createUser->name = $user->name;
                $createUser->email = $email;
                $createUser->fb_id = $user->id;
                $createUser->password = encrypt('user@$12345asdsfghj');

                $createUser->save();

                Auth::login($createUser);
                return redirect('/');
            }

        } catch (Exception $exception) {
            dd($exception->getMessage());
        }
    }
}
