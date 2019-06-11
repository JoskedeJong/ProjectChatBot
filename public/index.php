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
			$message1 = "Lets play a game of Hangman.";
			$message2 = "I've got a word in mind. Take a guess!";
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message1, $message2);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			
			$_SESSION["playingHangman"] = true;
			$letterOptions = array("ab", "cd", "ef");	
			$letterToGuess = array_rand($letterOptions); //, $num = 1
			$definiteLetter = $letterOptions[$letterToGuess];
			$_SESSION["letterToGuess"] = $definiteLetter;

		}

		if((strtolower($userMessage) == 'stop') && ($_SESSION["playingHangman"] == true))			// stops hangman
		{
			$_SESSION["playingHangman"] = false;
			$_SESSION["letterToGuess"] = "";
	
			$message = "I've stopped our game of Hangman. Thanks for playing.";
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();

		}

		if((strtolower($userMessage) == "cheat") && ($_SESSION["playingHangman"] == true))			// gives answer. CHEATS!
		{
			$message = "The right answer is ".$_SESSION["letterToGuess"];
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();

		}

		// if($_SESSION["playingHangman"] == true)
		// {																							// response when given multi-letter input
		// 	$message = "your response is ".(strlen($userMessage))." letters long";
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

		if((strtolower($userMessage) !== $_SESSION["letterToGuess"]) && ($_SESSION["playingHangman"] == true))
		{																							// response when given wrong input
			$message = "Nope. Guess again!";
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message);
			$result = $bot->replyMessage($event['replyToken'], $textMessageBuilder);
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();

		}

		if((strtolower($userMessage) == $_SESSION["letterToGuess"]) && ($_SESSION["playingHangman"] == true))
		{																							// responste when given right input. ends game
			$_SESSION["playingHangman"] = false;
			$_SESSION["letterToGuess"] = "";
			
			$message1 = "You guessed right! Congratulations";
			$message2 = "That concludes our game of Hangman. Thanks for playing.";
            $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($message1, $message2);
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