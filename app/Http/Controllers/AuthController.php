<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Prize;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    // public function __construct() {
    //     $this->middleware('auth:api', ['except' => ['login', 'register']]);
    // }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
    	$validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (! $token = Auth::attempt($validator->validated())) {
            return response()->json(['error' => 'Either email or password is wrong.'], 401);
        }

        // return  Auth::guard('web')->user();
        $accessToken = Auth::user()->createToken('authToken')->accessToken;

        return $this->createNewToken($accessToken);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
     public function importUser(Request $request){
        $validator = Validator::make($request->all(), [
            'file' => 'required',
            'admin_id' => 'required',
        ]);
        if($validator->fails()){
             return response()->json($validator->errors(), 400);
        }
        $file = $request->file('file');
        $adminId  = $request->file('admin_id');
        $csvData = file_get_contents($file);
        $csv = explode("\n", $csvData);
        // remove invisible characters
        $csv[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', "", $csv[0]);
        $rows = array_map('str_getcsv',$csv);
        $header = array_shift($rows);
    
        foreach ($rows as $row){
            $row = array_combine($header, $row);
            
        $user = User::create([
                    'admin_id' =>  $request->admin_id,
                    'fullname' => $row['fullname'],
                    'email' => $row['email'],
                    'password' => bcrypt($row['password']),
                    'role'=> 'user',
                     'redeem_count'=> 0
                ]);
        }
        
        return response()->json([
            'message' => 'Users successfully registered',
            'users' => $rows,
        ], 201);
         
     }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'site_key' => 'string',
            'admin_id' => 'integer',
        ]);

        if($validator->fails()){
             return response()->json($validator->errors(), 400);
        }

        if($request->role == 'admin'){
            if($request->site_key !== env('ADMIN_KEY')){
                return response()->json(['message'=>'Wrong Admin key used'], 400);
            }
        }

        $user = User::create(array_merge(
                    $validator->validated(),
                    ['password' => bcrypt($request->password),
                    'role'=> $request->role ?? 'admin',
                     'redeem_count'=> 0]
                ));

        if (! $token = Auth::attempt(['email'=>$request->email, 'password'=>$request->password])) {
            return response()->json(['error' => 'Either email or password is wrong.'], 401);
        }


        if($request->role == 'admin'){
            // return  Auth::guard('web')->user();
            $accessToken = Auth::user()->createToken('authToken')->accessToken;

            return response()->json([
                'message' => 'User successfully registered',
                'user' => $user,
                'token'=> $accessToken
            ], 201);
        } else {

            return response()->json([
                'message' => 'User successfully registered',
                'user' => $user,
            ], 201);
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out'],200);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        return response()->json(['user'=>Auth::user()]);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            // 'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => auth()->user()
        ]);
    }


    public function AdminVendor(Request $request){


        if(empty($request->admin_id)){

            return response()->json(['message'=>"admin_id cannot be empty!"], 400);
        }
        elseif(empty($request->vendor_id)){
           
            return response()->json(['message'=>"vendor_id cannot be empty!"], 400);
        }

        $admin_id = $request->admin_id;
        $vendor_id = $request->vendor_id;
        $prize_id = $request->prize_id;

        $user = User::where('id', $admin_id)->first();

        if($user->role !== 'admin'){
            return response()->json(['message'=>"You do not have permission"],400);
        }

        if(empty($request->allocate_prize)){

            return response()->json(['message'=>"allocate_prize cannot be empty!"], 400);
        }

        if(empty($prize_id)){

            return response()->json(['message'=>"prize_id cannot be empty!"], 400);
        }

        //get one admin first

        $getAdmin_vendor = User::where('admin_id', $admin_id)->where('id',$vendor_id)->first();

        // get allocated prize input
        $allocate_prize = $request->allocate_prize;


        $update_vendor = User::where('admin_id', $getAdmin_vendor->admin_id)->where('id',$getAdmin_vendor->id)->update(['allocate_prize'=>$allocate_prize,'prize_id'=>$prize_id]);

        //old quantity
         
        $oldPrizeinfo = Prize::where('admin_id',$getAdmin_vendor->admin_id)->where('id',$prize_id)->first();

        $select_all_admin_redeemcount = User::where('admin_id',$admin_id)->where('prize_id',$prize_id)->sum('redeem_count');
        

        $select_all_admin = User::where('admin_id',$admin_id)->where('prize_id',$prize_id)->sum('allocate_prize');

        $totwat = $select_all_admin+$select_all_admin_redeemcount;

        $oldbalance = $oldPrizeinfo->quantity-$totwat;

        $update_price = Prize::where('admin_id',$getAdmin_vendor->admin_id)->where('id',$prize_id)->update(['current_quantity'=>$oldbalance]);

    
        if($update_vendor){

            return response()->json(['message'=>'Updated Successfully']);

        }else{
            return response()->json(['message'=>'Not Updated'],400);
        }

        



    }


    // public function AdminVendor(Request $request){


    //     if(empty($request->admin_id)){

    //         return response()->json(['message'=>"admin_id cannot be empty!"], 400);
    //     }
    //     elseif(empty($request->vendor_id)){
           
    //         return response()->json(['message'=>"vendor_id cannot be empty!"], 400);
    //     }

    //     $admin_id = $request->admin_id;
    //     $vendor_id = $request->vendor_id;
    //     $prize_id = $request->prize_id;

    //     $user = User::where('id', $admin_id)->first();

    //     if($user->role !== 'admin'){
    //         return response()->json(['message'=>"You do not have permission"],400);
    //     }

    //     if(empty($request->allocate_prize)){

    //         return response()->json(['message'=>"allocate_prize cannot be empty!"], 400);
    //     }

    //     if(empty($prize_id)){

    //         return response()->json(['message'=>"prize_id cannot be empty!"], 400);
    //     }

    //     //get one admin first

    //     $getAdmin_vendor = User::where('admin_id', $admin_id)->where('id',$vendor_id)->first();

    //     // get allocated prize input
    //     $allocate_prize = $request->allocate_prize;


    //     $update_vendor = User::where('admin_id', $getAdmin_vendor->admin_id)->where('id',$getAdmin_vendor->id)->update(['allocate_prize'=>$allocate_prize,'prize_id'=>$prize_id]);

    //     //old quantity
         
    //     $oldPrizeinfo = Prize::where('admin_id',$getAdmin_vendor->admin_id)->where('id',$prize_id)->first();
        

    //     $select_all_admin = User::where('admin_id',$admin_id)->where('prize_id',$prize_id)->sum('allocate_prize');

    //     $oldbalance = $oldPrizeinfo->quantity-$select_all_admin;

    //     $update_price = Prize::where('admin_id',$getAdmin_vendor->admin_id)->where('id',$prize_id)->update(['current_quantity'=>$oldbalance]);

    
    //     if($update_vendor){

    //         return response()->json(['message'=>'Updated Successfully']);

    //     }else{
    //         return response()->json(['message'=>'Not Updated'],400);
    //     }

        



    // }


    public function deactivateAdminVendor(Request $request){

        if(empty($request->admin_id)){

            return response()->json(['message'=>"admin_id cannot be empty!"], 400);
        }
        elseif(empty($request->vendor_id)){
           
            return response()->json(['message'=>"vendor_id cannot be empty!"], 400);
        }

        $admin_id = $request->admin_id;
        $vendor_id = $request->vendor_id;
       
        $user = User::where('id', $admin_id)->first();

        if($user->role !== 'admin'){
            return response()->json(['message'=>"You do not have permission"],400);
        }
        
        $getAdmin_vendor = User::where('admin_id', $admin_id)->where('id',$vendor_id)->first();

        $update_vendor = User::where('admin_id', $getAdmin_vendor->admin_id)->where('id',$getAdmin_vendor->id)->update(['status'=>0]);

        if($update_vendor){

            return response()->json(['message'=>'Deactivated Successfully']);

        }else{
            return response()->json(['message'=>'Error']);
        }
            
    }

    public function editPrizeAdminVendorPrize(Request $request){

        if(empty($request->admin_id)){

            return response()->json(['message'=>"admin_id cannot be empty!"], 400);
        }
        elseif(empty($request->prize_id)){
           
            return response()->json(['message'=>"vendor_id cannot be empty!"], 400);
        }

        $admin_id = $request->admin_id;
        $prize_id = $request->prize_id;

        $user = User::where('id', $admin_id)->first();

        if($user->role !== 'admin'){
            return response()->json(['message'=>"You do not have permission"],400);
        }


        if(empty($request->current_quantity)){

            return response()->json(['message'=>"current_quantity cannot be empty!"], 400);
        }


        if(empty($request->allocate_qty)){

            return response()->json(['message'=>"allocate_qty cannot be empty!"], 400);
        }


        $getAdminPrizeInfo = Prize::where('admin_id',$admin_id)->where('id',$prize_id)->first();

        $update_prize_admin_vendor = Prize::where('id', $getAdminPrizeInfo->id)->where('admin_id',$getAdminPrizeInfo->admin_id)->update(['current_quantity'=>$request->current_quantity,'allocate_qty'=>$request->allocate_qty]);

        $prizeInfo = Prize::where('admin_id', $admin_id)->first();

        return response()->json(['message'=>'Prize Updated successfully','info'=>$prizeInfo]);


         
    }


    public function adminSeeAllVendors(Request $request){
      
        $admin_id = $request->admin_id;
        $user_id =  $request->user_id;

        $user = User::where('id', $admin_id)->first();

        if($user->role !== 'admin'){
            return response()->json(['message'=>"You do not have permission"],400);
        }
        else{

            if(!empty($user_id)){

                if($user->role == 'admin' || $user->role == 'user'){

                    $userId = $user_id;
                    Auth::loginUsingId($userId, true);
                    return response()->json(['user'=>Auth::user()]);
    
                }

            }else{

                return response()->json(['message'=>"user_id cannot be empty!"],400);
            }

            

             

        }

        

    }


    public function vendorRedemtionLeft(Request $request){

        $admin_id = $request->admin_id;
        $vendor_id =  $request->vendor_id;
        
        if(empty($admin_id)){

            return response()->json(['message'=>"admin_id cannot be empty!"],400);

        }elseif(empty($vendor_id)){

            return response()->json(['message'=>"vendor_id cannot be empty!"],400);
        }

        $user = User::where('id', $admin_id)->first();

        if($user->role !== 'admin'){
            return response()->json(['message'=>"You do not have permission"],400);
        }

        $vendor_info = User::where('id',$vendor_id)->first();

        //get admin_info 
        
        $get_admin_id = $vendor_info->admin_id;
        
        if(empty($get_admin_id)){
            
            return response()->json(['message'=>'Error admin_id not found with vendor!'],400);
            
        }else{
            
        $get_remaining_price = Prize::where('admin_id',$get_admin_id)->first();
        
        return response()->json([
            
        'Gifts Redeemed'=>$vendor_info->redeem_count,
        
        'Redemptions Left'=>$get_remaining_price->current_quantity],200);
            
            
        }

        


    }


    public function allAdminPrize(Request $request,$admin_id){


        if(empty($admin_id)){

            return response()->json(['message'=>"admin_id cannot be empty!"],400);

        }

        $all_admin_prize_info = Prize::where('admin_id',$admin_id)->get();

        return response()->json([
            
            'data'=>$all_admin_prize_info],200);



    }


    

}
