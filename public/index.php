<?php
session_start();


require '../vendor/autoload.php';


use \LINE\LINEBot\SignatureValidator as SignatureValidator;


// initiate app
$configs =  [
	'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

/* ROUTES */
$app->get('/', function ($request, $response) {
	return $response->withStatus(200, 'Okido');
});

$app->post('/', function ($request, $response)
{
	// get request body and line signature header
	$body 	   = file_get_contents('php://input');
	$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];

	// log body and signature
	file_put_contents('php://stderr', 'Body: '.$body);

	// is LINE_SIGNATURE exists in request header?
	if (empty($signature)){
		return $response->withStatus(400, 'Signature not set');
	}

	// is this request comes from LINE?
	if($_ENV['PASS_SIGNATURE'] == false && ! SignatureValidator::validateSignature($body, $_ENV['CHANNEL_SECRET'], $signature)){
		return $response->withStatus(400, 'Invalid signature');
	}

	// init bot
	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);
	$data = json_decode($body, true);
	foreach ($data['events'] as $event)
	{
		$userMessage = $event['message']['text'];
		if(strtolower($userMessage) == 'hello')
		{
			$message = "Hello Gorilla";
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();
		
		}


	   if(strtolower($userMessage) == 'appointment')
		{
			$message = "Are you available today at 15:00 ?";
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();
		
		}


    	if(strtolower($userMessage) == 'sticker')
		{
	
            $mysticker = new \LINE\LINEBot\MessageBuilder\StickerMessageBuilder("11538", "51626501");
			$result = $bot->replyMessage($event['replyToken'], $mysticker);
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();
		
		}


		if(strtolower($userMessage) == 'test')
		{
			$message = "Your test was successful. Awesome!";
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();
		
		}

		if(strpos($userMessage, "positive") !== false)
		{
			$message = "Positivity detected. Nice.";
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();

		}

		// ---------------------------------------------------------------------------------------- hangman ----------------------------------------------------------------------

		if(strtolower($userMessage) == 'hangman')													// starts hangman, determines answer
		{
			$_SESSION["playingHangman"] = true;														
			$wordOptions = array("aba", "cdec", "fghif");	
			$wordToGuess = array_rand($wordOptions); //, $num = 1
			$_SESSION["wordToGuess"] = $wordOptions[$wordToGuess];

			$_SESSION["underscoreArray"] = array();

			$_SESSION["wordArray"] = str_split($wordOptions[$wordToGuess], 1);


			for ($i=0; $i < strlen($_SESSION["wordToGuess"]); $i++) {								// makes underscored preview of word
				array_push($_SESSION["underscoreArray"], "_");
				$_SESSION["underscoreString"] = implode(" ", $_SESSION["underscoreArray"]);
			}

			$message1 = "Lets play a game of Hangman.";
			$message2 = "I've got a word in mind. Here it is: ".$_SESSION["underscoreString"];
			$message3 = "Take a guess!";
			$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message1, $message2, $message3);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);

		}

		if((strtolower($userMessage) == 'stop') && ($_SESSION["playingHangman"] == true))			// stops hangman
		{
			$_SESSION["playingHangman"] = false;
			$_SESSION["wordToGuess"] = "";
	
			$message = "I've stopped our game of Hangman. Thanks for playing.";
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();

		}

		if((strtolower($userMessage) == "cheat") && ($_SESSION["playingHangman"] == true))			// gives answer. CHEATS!
		{
			$message = "The right answer is ".$_SESSION["wordToGuess"];
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();

		}

		// if($_SESSION["playingHangman"] == true)														// for testing
		// {
		// 	$message = "The output is ".strlen($_SESSION["wordToGuess"]);
        //     $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
		// 	$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
		// 	return $result->getHTTPStatus() . ' ' . $result->getRawBody();

		// }

		if((strlen($userMessage) > 1) && ($_SESSION["playingHangman"] == true))
		{																							// response when given multi-letter input
			$message = "One letter at the time.";
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();

		}

		if((strlen($userMessage) == 1) 
		&& (strpos($_SESSION["wordToGuess"], strtolower($userMessage)) !== false) 
		&& ($_SESSION["playingHangman"] == true)){

			for ($i = 0; $i < strlen($_SESSION["wordToGuess"]); $i++){								// replace entry 'underscoresArray' with entry 'wordArray' of same index
				if ($_SESSION["wordArray"][$i] == strtolower($userMessage)){
					$_SESSION["underscoreArray"][$i] = $_SESSION["wordArray"][$i];		
				}					
			}
				
			if ($_SESSION["underscoreArray"] == $_SESSION["wordArray"]){							// win-check

				$_SESSION["playingHangman"] = false;
				$_SESSION["wordToGuess"] = "";

				$message1 = "You guessed right! The word was ".strtoupper(implode($_SESSION["underscoreArray"])).". Congratulations.";
				$message2 = "That concludes our game of Hangman. Thanks for playing.";
				$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message1, $message2);
				$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
				return $result->getHTTPStatus() . ' ' . $result->getRawBody();
			}
			else {																					// correct guess mesage
				$message1 = "You guessed right!";													
				$message2 = "Here's what you've guessed so far: ".implode(" ", $_SESSION["underscoreArray"]);
				$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message1, $message2);
				$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
				return $result->getHTTPStatus() . ' ' . $result->getRawBody();
			}
			
		}
	

		if((strpos($_SESSION["wordToGuess"], strtolower($userMessage)) == false) && ($_SESSION["playingHangman"] == true))
		{																								// response when given wrong input
			// check for loss, else
			
			$message = "Nope. Guess again!";
			$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();

		}

	}
	

});

// $app->get('/push/{to}/{message}', function ($request, $response, $args)
// {
// 	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
// 	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

// 	$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($args['message']);
// 	$result = $bot->pushMessage($args['to'], $textMessageBuilder);

// 	return $result->getHTTPStatus() . ' ' . $result->getRawBody();
// });

/* JUST RUN IT */
$app->run();