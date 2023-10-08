<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserWin;

class RedeemController extends Controller
{
    /**
     * Create a new RedeemController instance.
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
        return UserWin::join('users', 'user_wins.user_id', '=', 'users.id')
        ->join('wins', 'user_wins.win_id', '=', 'wins.id')
        ->join('prizes', 'wins.prize_id', '=', 'prizes.id')
        ->where('users.admin_id', $user->id)
        ->get();
    }
    public function my_list(){
        $user = Auth::user();

        return UserWin::join('users', 'user_wins.user_id', '=', 'users.id')
        ->join('wins', 'user_wins.win_id', '=', 'wins.id')
        ->join('prizes', 'wins.prize_id', '=', 'prizes.id')
        ->where('user_wins.user_id', $user->id)
        ->get();
    }

}
