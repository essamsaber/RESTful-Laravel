 <?php
 Route::group(['prefix' => 'api/v1'], function(){
 	
 	Route::resource('meeting', 'MeetingController', [
 		'except' => ['edit', 'create']
	]);

 	Route::resource('meeting/registration', 'RegistrationController', [
 		'only' => ['store', 'destroy']
	]);

	Route::post('user', 'AuthController@store');

	Route::post('user/signin', 'AuthController@signin');
 });
 