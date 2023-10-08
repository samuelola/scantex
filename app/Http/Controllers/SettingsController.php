<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use App\Models\UserWin;
use App\Models\Win;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Create a new SettingController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['index','getOne']]);
    }


    public function index(){
        return Setting::all()->first();
    }

    public function getOne(Request $request){
        $count = count(Setting::get());
        if($count === 0){
            return response()->json([
                'message' => $request->id,
            ], 400);
        }
        $row = $this->findSettingsByIdAdmin($request->id);
        if(!$row) {
            return response()->json([
                'message' => 'Could not find record ',
            ], 400);
        }
         return response()->json([
            'settings' => $row
        ], 201);
    }
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'brand_name' => 'required|string',
            'admin_id' => 'required|string',
            'brand_background_color' => 'required|string|max:20',
            'brand_theme_color' => 'required|string|max:20',
            'brand_logo' => 'required|image:jpeg,png,jpg,gif',
            'brand_background_image' => 'required|image:jpeg,png,jpg,gif',
            'message' =>"required|string",
            'redeeming_point' => 'required|string',
            'custom_message' => 'required|string',
            'form_message' => 'required|string',
            'show_try_again' => 'required|string',
            'try_again_text' => 'required|string',
            'limit_scan' => 'required|string'
        ]);

        if($validator->fails()){
             return response()->json($validator->errors(), 400);
        }

        $logoPath = $request->file('brand_logo')->store('images', 'public');
        $backgroundPath = $request->file('brand_background_image')->store('images', 'public');

        $setting = Setting::create(array_merge(
                    $validator->validated(),[
                        'brand_logo'=>Storage::disk('images')->url($logoPath),
                        'brand_background_image'=>Storage::disk('images')->url($backgroundPath),
                    ]
                ));

        return response()->json([
            'message' => 'Settings successfully registered',
            'setting' => $setting
        ], 201);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand_name' => 'required|string',
            'admin_id' => 'required|string',
            'brand_background_color' => 'required|string|max:20',
            'brand_theme_color' => 'required|string|max:20',
            'brand_logo' => 'required|image:jpeg,png,jpg,gif',
            'brand_background_image' => 'required|image:jpeg,png,jpg,gif',
            'message' =>"required|string",
            'redeeming_point' => 'required|string',
            'custom_message' => 'required|string',
            'form_message' => 'required|string',
            'show_try_again' => 'required|string',
            'limit_scan' => 'required|string',
            'try_again_text' => 'required|string',
        ]);

        // return response()->json(var_dump($request->all()), 400);

        if($validator->fails()){
             return response()->json($validator->errors(), 400);
        }

        $logoPath = $request->file('brand_logo')->store('images', 'public');
        $backgroundPath = $request->file('brand_background_image')->store('images', 'public');

        $count = count(Setting::get());
        // check if admin setting is created
        if($count === 0){
            $setting = Setting::create(array_merge(
            $validator->validated(),[
                'brand_logo'=>Storage::disk('images')->url($logoPath),
                'admin_id'=>(int)$request->admin_id,
                'brand_background_image'=>Storage::disk('images')->url($backgroundPath),
                ]
            ));

            return response()->json([
                'message' => 'Settings successfully registered',
                'setting' => $setting
            ], 201);
        } else {
             $found = $this->findSettingsByIdAdmin($request->admin_id);
            // check if admin setting already exist
            if(count($found) === 0){
            // if(!$row = $this->findUserById($request->id)){
                // $row = Setting::all()->first();
                $setting = Setting::create(array_merge(
                    $validator->validated(),[
                        'brand_logo'=>Storage::disk('images')->url($logoPath),
                        'admin_id'=>(int)$request->admin_id,
                        'brand_background_image'=>Storage::disk('images')->url($backgroundPath),
                    ]
                ));

                return response()->json([
                    'message' => 'Settings successfully registered',
                    'setting' => $setting
                ], 201);
            }

            $row = Setting::where('admin_id', $request->admin_id)->firstOrFail();

            $row->update(array_merge(
                $validator->validated(),
                [
                    'brand_logo'=>Storage::disk('images')->url($logoPath),
                    'admin_id'=>(int)$request->admin_id,
                    'brand_background_image'=>Storage::disk('images')->url($backgroundPath),
                ]
            ));
            return response()->json([
                'message' => 'Settings successfully registered',
                'setting' => $row
            ], 201);
        }
    }

    public function showSiteKey(){
        $user = Auth::user();

        if($user->role !== 'admin'){
            return response()->json(['user_key'=>env('USER_KEY')],200);
        }
        return response()->json(['admin_key'=>env('ADMIN_KEY'),
            'user_key'=>env('USER_KEY')
        ],200);
    }


    private function getAuthUser(){
        return Auth::user();
    }

    private function canClear(){
        return $this->getAuthUser()->role === 'admin';
    }

    private function clearAdminPrize(){
        if($this->canClear()){
            return  Prize::where("admin_id",$this->getAuthUser()->id)->delete();
        } else {
            return response()->json(['message' => 'You do not have permission to do this.'], 400);
        }
    }

    private function clearAdminVendors(){
        if($this->canClear()){
            return User::where("admin_id",$this->getAuthUser()->id)->delete();
        } else {
            return response()->json(['message' => 'You do not have permission to do this.'], 400);
        }
    }

    private function clearAdminWins(){
        if($this->canClear()){
            return  Win::where("admin_id",$this->getAuthUser()->id)->delete();
        } else {
            return response()->json(['message' => 'You do not have permission to do this.'], 400);
        }
    }

    private function clearAdminUserWins(){
        if($this->canClear()){
            return UserWin::join('users', 'user_wins.user_id', '=', 'users.id')
            ->where("admin_id",$this->getAuthUser()->id)->delete();

        } else {
            return response()->json(['message' => 'You do not have permission to do this.'], 400);
        }
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshAdmin() {
        $this->clearAdminUserWins();
        $this->clearAdminWins();
        $this->clearAdminPrize();
        $this->clearAdminVendors();
    }


	/**
	 * @param $od
	 */
	private function findSettingsByIdAdmin($id)
	{
		return Setting::where('admin_id', $id)->get();
	}
}
