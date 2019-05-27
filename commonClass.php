<?php

class common {

    public function sendMessage($message, $mobile) {

        /* $time = date('d-m-YTH:i:s');
          $message = urlencode($message);
          $url2 = "http://alotsolutions.in/API/WebSMS/Http/v1.0a/index.php?username=ShezarWeb&password=^yiIVY!9&sender=TCLCPB&to=$mobile&message=$message&reqid=1";
          $curl2 = curl_init();
          // OPTIONS:
          curl_setopt($curl2, CURLOPT_URL, $url2);
          curl_setopt($curl2, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($curl2, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
          $result = curl_exec($curl2);

          curl_close($curl2); */
    }

    public function distancebetween($userlat, $userlang, $gurulat, $gurulang) {

        $origins = $userlat . ',' . $userlang;
        $destinations = $gurulat . ',' . $gurulang;

        $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=" . $origins . "&destinations=" . $destinations . "&key=" . GOOGLE_KEY;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // Disable SSL verification
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $output = curl_exec($ch);
        curl_close($ch);


        $arr = json_decode($output, TRUE);

        return $arr['rows'][0]['elements'][0]['distance']['value'];
    }

    public function getLatLongByAddress($useraddress) {

        $result = array();
        $useraddress = str_replace(" ", "+", $useraddress);
        $url = "https://maps.google.com/maps/api/geocode/json?address=" . $useraddress . "&sensor=false&key=" . GOOGLE_KEY;


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // Disable SSL verification
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $output = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($output);

        $result['lat'] = $response->results[0]->geometry->location->lat;
        $result['lang'] = $response->results[0]->geometry->location->lng;

        return $result;
    }

    public function sendPushNotification($deviceTokens, $tagmessage) {

        $msg = array
            (
            'body' => $tagmessage,
            'title' => 'Tag',
                // 'icon'  => 'myicon',/*Default Icon*/
                //  'ntype' => 'post',
                // 'sound' => 'mySound',/*Default sound*/
        );
        $fields = array
            (
            'to' => $deviceTokens,
            'notification' => $msg,
            'data' => $msg
        );
        $headers = array
            (
            'Authorization: key=' . $CFG->API_ACCESS_KEY,
            'Content-Type: application/json'
        );
        // Send Reponse To FireBase Server
        $json = json_encode($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_exec($ch);
        curl_close($ch);
    }

}
