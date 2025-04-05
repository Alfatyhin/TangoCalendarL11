<?php

namespace App\Services\Commands;

use App\Models\FcmToken;
use App\Models\UserToken;
use App\Services\FirebaseFirestoreService;
use App\Services\StructuredQuery;
use Illuminate\Support\Facades\Log;

class FirebaseCheckSubscribes
{
    public function __invoke()
    {
        $queryBuilder = new StructuredQuery('statements');
        $queryBuilder->addFieldFilter('status', 'EQUAL', 'new');
        $query = $queryBuilder->getStructuredQuery();

        $firebase = new FirebaseFirestoreService();
        $collection = $firebase->fireBaseRequest($query);

        Log::channel('cron_commands')->info("FirebaseCheckSubscribes",  $collection);

        $firebase = new FirebaseFirestoreService();
        $firebase->setMessaging();

        $user_uids = UserToken::whereIn('userRole', ['su_admin', 'admin' ])->select('userUid')->get();

        if ($user_uids) {
            foreach ($user_uids as $uid_data) {
                $uid = $uid_data->userUid;
                $fcm_tokens_data = FcmToken::where('user_uid', $uid)->first();
                $fcm_tokens = $fcm_tokens_data->fcm_tokens ?? [];

                if (sizeof($fcm_tokens) > 0) {
                    foreach ($fcm_tokens as $token) {
                        foreach ($collection as $item) {
                            $title = '🚀 Statement';
                            $type = $item['type']['stringValue'];
                            $status = $item['status']['stringValue'];
                            $body = "$type - $status";
                            $result[] = $firebase->sendNotification($token, $title, $body);
                        }
                    }
                }

            }
        }

        Log::channel('cron_commands')->info("FirebaseCheckSubscribes result messages",  $result);
    }
}
