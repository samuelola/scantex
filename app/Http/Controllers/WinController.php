<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Win;
use App\Models\User;
use App\Models\Prize;

class WinController extends Controller
{
    /**
     * Create a new WinController instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware('auth:api');
    }


    public function index(){
        $user = Auth::user();

        if($user->role !== 'admin'){
            return response()->json(['message'=>"You do not have permission"],400);
        }


        // return Win::join('prizes', 'wins.prize_id', '=', 'prizes.id')->where('wins.admin_id',$user->id)->get();

        $win = Win::where('admin_id',$user->admin_id)->get();

        if(!empty($win)){
          
			foreach($win as $idd){

				$roww = Prize::where('id',$idd->prize_id)->first();

			    $row[] = $roww;
			}

		}

        return $row;
    }

    public function userProfile(){
        return response()->json(['user'=>Auth::user()]);
    }

    public function getVendor() {
        $auth = Auth::user();


        // return User::get();
        $row = User::where('admin_id',$auth->id)->get();
        return response()->json(['vendor' => $row],200);
    }


}
