<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Prize;
use App\Models\Win;
use App\Models\User;
use App\Models\UserWin;
use App\Models\Setting;
use Carbon\carbon;

class PrizeController extends Controller
{
    /**
     * Create a new PrizeController instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->max_win = 1;
        $this->middleware('auth:api',['except'=>['random']]);
    }

    public function index(){

        return Prize::where('admin_id',Auth::user()->id)->get();
        
    }

    public function redeem(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
        ]);

        if($validator->fails()){
             return response()->json($validator->errors(), 400);
        }
        if(!$winRow = $this->findWinById($request->id) ){
            return response()->json(['message' => 'Can not find record.'], 401);
        }
        $prizeRow = $this->findPrizeById($winRow[0]->prize_id);

        $userRedeem = $this->findWinByNameAndData($request->name,$request->id);
        $count = $userRedeem->count();

        $user = Auth::user();

        if($prizeRow->admin_id != $user->admin_id){
            return response()->json([
                'message' => 'You do not have access to do this',
            ], 400);
        }

        // check if user already redeemed prize
        $userWins = $this->findUserWinByNameId($request->name, $request->id);
        $userWinsCount = $userWins->count();

        if($count > 0 || $userWinsCount > 0){
            return response()->json(['message'=> 'User already exceeded the win limit'], 400);
        } else{
            // reduce the quantity in prize row by 1
            $previousQuantity = $prizeRow->current_quantity;
            $prizeRow->update(['current_quantity' => $previousQuantity - 1]);

            // create record for user win
            DB::table('user_wins')->insert([
                'user_id'=> $user->id,
                'name'=>$request->name,
                'win_id'=>$request->id]
            );

            // change redeemCount
            $this->findUserById($user->id)->update(['redeem_count'=> $user->redeem_count + 1]);
            return response()->json(['prize'=>$prizeRow,'message'=>'Successfully redeemed'], 200);
        }
    }

    public function create(Request $request){

        $allocate_qty = $request->allocate_qty;
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|between:2,100',
            'quantity' => 'required|integer',
            'current_quantity' => 'required|integer',
            'image' => 'image:jpeg,png,jpg,gif',
            
            
        ]);

        if($validator->fails()){
             return response()->json($validator->errors(), 400);
        }
        $path = $request->file('image')->store('images', 'public');
        $prize = Prize::create(array_merge(
                    $validator->validated(),['image'=>Storage::disk('images')->url($path),
                    'admin_id'=>$user->id,
                    'allocate_qty'=>$allocate_qty
                    
                    ]
                ));

        return response()->json([
            'message' => 'Prize successfully registered',
            'prize' => $prize
        ], 201);
    }

    public function random(Request $request){
        // return 'works';

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'phone' => 'required|string|between:2,100',
        ]);
        if($validator->fails()){
             return response()->json($validator->errors(), 400);
        }
        $count = $this->findWinByNameAndPhoneAndDate($request->name,$request->id, $request->phone)->count();
        // return response()->json(["it"=>$this->findSettingsByIdAdmin($request->id)[0]]);

        if($this->shouldReturnPrize($request->id)){
            if($count < $this->getMaxWin($request->id)){
                $prize = Prize::where('admin_id', $request->id)
                ->where('current_quantity','>',0)->get()->random();
    
            // return [$prize];
                // return $request->name;
                $win = Win::create([
                    'admin_id'=>$request->id,
                    'prize_id'=> $prize->id,
                    'name'=>$request->name,
                    'phone'=>$request->phone,
                    ]
                );
                // return response()->json([]);
                // return $win;
                
    
                return response()->json(['prize'=> $prize,'user'=>$request->name,
                'win'=>$win,'type'=>'prize'
            ], 200);
    
            } else {
                return response()->json(['message'=> 'You already exceeded the win limit'], 400);
            }
        } else {
                return response()->json(['type'=>"try again"
            ], 200);
            
        }
    }

    public function delete(Request $request)
    {
        if(!$row = $this->findPrizeById($request->id)){
            return response()->json(['error' => 'Can not find record.'], 401);
        }
        $row->delete();
    }
	/**
	 * @param $od
	 * @return \App\Models\Prize
	 */
	private function findPrizeById($id)
	{
		return Prize::where('id', $id)->firstOrFail();
	}

	/**
	 * @param $od
	 * @return \App\Models\Win
	 */
	private function getMaxWin($id)
	{ 
	    if($found = $this->findSettingsByIdAdmin($id)){
	        $value = $found[0]->limit_scan;
	        return (int)$value;
	       // return $this->max_win;
	    } else{
	        return $this->max_win;
	    }
	}

	/**
	 * @param $od
	 * @return \App\Models\Win
	 */
	private function findWinById($id)
	{
		return Win::where('id', $id)->get();
	}

	/**
	 * @param $od
	 * @return \App\Models\Win
	 */
	private function findWinByNameAndData($name,$admin)
	{
		return Win::where('name', $name)->where('admin_id',$admin)->whereDate('created_at','>=', now()->subDays(1)->setTime(0,0,0)->toDateTimeString())->get();
	}

	/**
	 * @param $od
	 * @return \App\Models\Win
	 */
	private function findWinByName($name,$admin)
	{
		return Win::where('name', $name)->where('admin_id',$admin)->get();
	}

	/**
	 * @param $od
	 * @return \App\Models\Win
	 */
	private function findWinByNameAndPhoneAndDate($name,$admin,$phone)
	{
		return Win::where('name', $name)->where('phone',$phone)->where('admin_id',$admin)->whereDate('created_at','>=', now()->subDays(1)->setTime(0,0,0)->toDateTimeString())->get();;
	}
    private function getAuthUser(){
        return Auth::user();
    }

	/**
	 * @param $od
	 */
	private function shouldReturnPrize($admin_id)
	{
	    if($found = $this->findSettingsByIdAdmin($admin_id)){
	        if($found[0]->show_try_again == '0') {
	            return true;
	        }
    	    $randomNum = rand(1,10);
    	    if($randomNum < 3){
    	        return true;
    	    }
    	    return false;
	    }
    	return false;
    }

	/**
	 * @param $od
	 * @return \App\Models\User
	 */
	private function findUserById($id)
	{
		return User::where('id', $id)->firstOrFail();
    }

	/**
	 * @param $od
	 * @return \App\Models\User
	 */
	private function findUserWinByNameId($name,$id)
	{
		return UserWin::where('name', $name)->where('win_id',$id)->get();
    }

	/**
	 * @param $od
	 * @return \App\Models\Setting
	 */
	private function findSettingsByIdAdmin($id)
	{
		return Setting::where('admin_id', $id)->get();
	}
}
