<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
//use App\Http\Requests\ReCaptchataRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed'
            //'g-recaptcha-response'=>'required|recaptcha'
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'email' => $data['email'],
            'account_type' => $data['accountType'],
            'password' => Hash::make($data['password'])
        ]);

        if ($data['accountType'] == 1) {
            //account-type is guardian
            echo "hey";
            $request = new Request();
            $request->setMethod('POST');
            $request->request->add(['uid' => $user->id]);
            app('App\Http\Controllers\GuardianController')->store($request);
        } else if ($data['accountType'] == 4) {
            //account-type is provider
            $request = new Request();
            $request->setMethod('POST');
            $request->request->add(['uid' => $user->id]);
            app('App\Http\Controllers\ProviderController')->store($request);
        } else if ($data['accountType'] == 2 || $data['accountType'] == 3) {
            //account-type private or public
            //2: public, 3: private

            $request = new Request();
            $request->setMethod('POST');
            if ($data['accountType'] == 2) {
                $p_kind = 1;
                $coordination = 1;
            } else if ($data['accountType'] == 3) {
                $p_kind = 2;
                $coordination = 0;
            }
            $request->request->add(['uid' => $user->id,
                                   'coordination' => $coordination,
                                   'p_kind' => $p_kind]);

            app('App\Http\Controllers\ProgramController')->store($request);
        } else {
            //error
        }

        return $user;
    }

    //
    public function store(Request $request) {
        $user = User::create([
            'email' => $request->email,
            'account_type' => $request->account_type,
            'password' => Hash::make($request->password)
        ]);
        return $user;
    }

    //https://gist.github.com/tylerhall/521810
    public function generateStrongPassword($length = 9, $add_dashes = false, $available_sets = 'luds') {
        $sets = array();
        if(strpos($available_sets, 'l') !== false)
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        if(strpos($available_sets, 'u') !== false)
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        if(strpos($available_sets, 'd') !== false)
            $sets[] = '23456789';
        if(strpos($available_sets, 's') !== false)
            $sets[] = '!@#$%&*?';
        $all = '';
        $password = '';
        foreach($sets as $set)
        {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }
        $all = str_split($all);
        for($i = 0; $i < $length - count($sets); $i++)
            $password .= $all[array_rand($all)];
        $password = str_shuffle($password);
        if(!$add_dashes)
            return $password;
        $dash_len = floor(sqrt($length));
        $dash_str = '';
        while(strlen($password) > $dash_len)
        {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }
        $dash_str .= $password;
        return $dash_str;
    }
}
