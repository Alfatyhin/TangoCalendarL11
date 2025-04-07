<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::get('v1/get_time_signed', [ApiController::class, 'getServerTimeSignegV1']);
Route::get('/get_time_signed', [ApiController::class, 'getServerTimeSigneg']);

Route::get('/get/events/{id}', [ApiController::class, 'getCalendarEvents']);

Route::get('/v1/get/events/{id}', [ApiController::class, 'getCalendarEventsV1']);

Route::get('/v1/get/event/{id}', [ApiController::class, 'getCalendarEventV1']);

Route::get('/get/calendars', [ApiController::class, 'getCalendars']);

Route::any('/event_add_test', [ApiController::class, 'addEventTest']);

Route::any('/firebase_test', [ApiController::class, 'firebaseTest']);


Route::middleware(['api-signed'])->group(function () {

    Route::any('/get/user_token', [ApiController::class, 'registerTokenUser']);

    Route::any('/v1/event_add', [ApiController::class, 'addEventV1']);

    Route::any('/v1/event_update', [ApiController::class, 'updateEventV1']);

    Route::any('/v1/event_delete', [ApiController::class, 'deleteEventV1']);

    Route::any('/add_calendar', [ApiController::class, 'addCalendar']);

    Route::any('/translate_event', [ApiController::class, 'translateTextEvent']);

    Route::any('/v1/add_cfm', [ApiController::class, 'addCfm']);

    Route::any('/v1/firebase_send_message', [ApiController::class, 'firebaseSendMessage']);

    Route::any('/v1/subscribes/calendars_events', [ApiController::class, 'subscribeCalendarEvents']);

    Route::any('/v1/subscribes/get_user_subscribes', [ApiController::class, 'getUserSubscribes']);

});



//Route::any('/v1/add_cfm_test', [ApiController::class, 'addCfmTest']);

Route::any('/get_calendar_update/{id}', [ApiController::class, 'getCalendarUpdate']);

Route::any('/event_delete_test', [ApiController::class, 'deleteEventTest']);

Route::any('/calendar_webhook', [ApiController::class, 'calendarWebhook'])->name('calendar_webhook');

Route::any('/get_calendar_data_bu_uid/{uid}', [ApiController::class, 'getCalendarDataBuUid']);

