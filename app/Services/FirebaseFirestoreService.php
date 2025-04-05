<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

use function Livewire\store;

class FirebaseFirestoreService
{
    protected $firestore;
    protected $factory;
    protected $messaging;
    private $credentials;

    public function __construct()
    {
        $credentials = Storage::disk('local')->get(config('firebase.projects.app.credentials'));
        $this->factory = (new Factory)->withServiceAccount($credentials);
        $this->credentials = $credentials;
    }

    public function setMessaging()
    {
        $this->messaging = $this->factory->createMessaging();
    }

    public function sendNotification($deviceToken, $title, $body, $data = [])
    {

        $message = CloudMessage::new()
            ->toToken($deviceToken)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        return $this->messaging->send($message);
    }


    public function fireBaseRequest($body)
    {
        $authToken = FirebaseAuth::getAccessToken();
        $credentials= json_decode($this->credentials);
        $url = "https://firestore.googleapis.com/v1/projects/{$credentials->project_id}/databases/(default)/documents:runQuery";

        $response = Http::withToken($authToken)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $body);

        $fields = [];
        if ($response->successful()) {
            $documents = $response->json();
            foreach ($documents as $doc) {
                if (isset($doc['document']['fields'])) {
                    $fields[] = $doc['document']['fields'] ?? [];
                }
            }
        }

        return $fields;
    }

}
