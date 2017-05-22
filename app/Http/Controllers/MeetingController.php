<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Meeting;
use Carbon\Carbon;
use JWTAuth;

class MeetingController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt.auth')->only(['update', 'store', 'destroy']);
    }

    public function index()
    {
        $meetings = Meeting::all();

        foreach($meetings as $meeting) {
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/1',
                'method' => 'GET'
            ];
        }

        $response = [
            'msg' => 'List of all Meetings',
            'meetings' => $meetings
        ];

        return response()->json($response, 200);
    }


    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'time' => 'required|date_format:YmdHie',
        ]);

        if(!$user = JWTAuth::parseToken()->authenticate())
        {
            return response()->json(['msg' => 'User not found'], 404);
        }

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $user->id;

        $meeting = new Meeting([
            'title' => $title,
            'time' => Carbon::createFromFormat('YmdHie', $time),
            'description' => $description
        ]);

        if($meeting->save()) {
            $meeting->users()->attach($user_id);
            $meeting->meeting_view = [
                'href' => '/api/v1/meeting/'.$meeting->id,
                'method' => 'GET'
            ];
        }

        $response = [
            'msg' => 'Meeting created',
            'meeting' => $meeting
        ];

        return response()->json($response, 201);
    }

    public function show($id)
    {
        $meeting = Meeting::with('users')->where('id', $id)->firstOrFail();
        $meeting->view_meeting = [
            'href' => 'api/v1/meeting',
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'Meeting information',
            'meeting' => $meeting
        ];

        return response()->json($response, 200);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'time' => 'required|date_format:YmdHie',
        ]);
        
        if(!$user = JWTAuth::parseToken()->authenticate())
        {
            return response()->json(['msg' => 'User not found'], 404);
        }

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $user->id;
        

        $meeting = Meeting::with('users')->findOrFail($id);

        if(!$meeting->users()->where('users.id', $user_id)->first()) {
            return response()->json( ['msg' => 'User not registered for meeting, update not successfull'], 401);
        }

        $meeting-> time = Carbon::createFromFormat('YmdHie', $time);
        $meeting->title = $title;
        $meeting->description = $description;
        if(!$meeting->update()) {
            return response()->json(['msg' => 'Error during updating'], 404);
        }

        $meeting->view_meeting = [
            'href' => 'api/v1/meeting/'.$meeting->id,
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'Meeting information has been updated', 
            'meeting' => $meeting
        ];

        return response()->json($response, 201);

    }

    public function destroy($id)
    {

        $meeting = Meeting::findOrFail($id);

        if(!$user = JWTAuth::parseToken()->authenticate())
        {
            return response()->json(['msg' => 'User not found'], 404);
        }
 
        if(!$meeting->users()->where('users.id', $user->id)->first()) {
            return response()->json( ['msg' => 'User not registered for meeting, update not successfull'], 401);
        }

        $users = $meeting->users;
        $meeting->users()->detach();

        if(!$meeting->delete()) {
            foreach($users as $user) {
                $meeting->users()->attach($user);
            }
            return response()->json(['msg' => 'deletion failed'], 404);
        }

        return response()->json($response, 200);
    }
}
