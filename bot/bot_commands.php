<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/database/database.php');
require_once('config.php');

function start_command($first_name,$chat_id)
{
    $answer = "Здравствуйте, ".$first_name.".%0a%0a".get_commands();
    on_start($chat_id);
    send_message($chat_id,$answer);
}

function buy_command($chat_id,$message = null,$flag = null)
{

    if($flag == null){
        update_flag($chat_id,'buy-1');
        new_portfolio_position($chat_id);
        send_message($chat_id,'Введите тикер (напр. BTC).');
    }

    switch($flag){
        case 'buy-1':
            $url = 'https://api.nomics.com/v1/currencies/ticker?key=a88dee59ea21cb13c8f828b3f1871399&ids='.$message;
            $response = file_get_contents($url);
            $response = json_decode($response);
            if(empty($response)){
                $answer = "Такого тикера не существует.%0a%0a".get_commands();
                update_flag($chat_id,null); 
                send_message($chat_id,$answer);break;
            }
            else{
                send_message($chat_id,$response[0]->name."%0a"
                ."Текущая цена: ".$response[0]->price."%0a"
                ."Введите цену покупки в USD.");
                update_position($chat_id,"ticker",$message);
                update_flag($chat_id,'buy-2'); break;
            }
            break;
        case 'buy-2':
            if(!is_numeric($message)){
                update_flag($chat_id,null);
                send_message($chat_id,'Нужно было ввести число.');
                return;
            }
            update_position($chat_id,'price',$message);
            send_message($chat_id,'Введите количество.'); 
            update_flag($chat_id,'buy-3'); 
            break;
        case 'buy-3':
            if(!is_numeric($message)){
                update_flag($chat_id,null);
                send_message($chat_id,'Нужно было ввести число.');
                return;
            }
            update_position($chat_id,'amount',$message);
            finalize_position($chat_id);
            send_message($chat_id,'Позиция добавлена в портфолио.'); 
            update_flag($chat_id,null); 
            break;
    }
}

function sell_command($chat_id,$message = null,$flag = null)
{
    
    $data = select_portfolio($chat_id);
    if(empty($data)){
        send_message($chat_id,'В вашем портфолио нет открытых позиций.%0aИспользуйте команду /buy для того, чтобы добавить позицию.');
        return;
    }
    if($flag == null)
    {
        update_flag($chat_id,'sell-1');
        $i = 1;
        $answer = "";
        foreach($data as $currency)
        {
            $answer .= $i++.'. '.$currency['ticker'].' '.$currency['amount'].' $'.number_format($currency['price'],2,'.',' ').'%0a';
        }
        $answer .= '%0aВведите номер позиции, которую вы хотите убрать из портфолио.';
        send_message($chat_id,$answer);
        return;
    }

    switch($flag){
        case 'sell-1':
            if(!is_numeric($message)){
                update_flag($chat_id,null);
                send_message($chat_id,'Нужно было ввести число.');
                return;
            }
            if($message > count($data) || $message < 1)
            {
                update_flag($chat_id,null);
                send_message($chat_id,'Позиции с таким номером нет.');
            }
            else
            {
                $id = $data[$message - 1]['id'];
                delete_position($id);
                send_message($chat_id,'Позиция успешно удалена.');
            }
            update_flag($chat_id,null);
            break;
    }
}

function portfolio_command($chat_id)
{
    $data = select_portfolio($chat_id);

    if(empty($data))
    {
        send_message($chat_id,'В вашем портфолио нет открытых позиций.%0aИспользуйте команду /buy для того, чтобы добавить позицию.');
        return;
    }

    $tickers = "";
    foreach($data as $currency){
        $tickers .= $currency['ticker'].',';
    }
    
    $url = 'https://api.nomics.com/v1/currencies/ticker?key=a88dee59ea21cb13c8f828b3f1871399&ids=';
    $url .= $tickers;
    $response = file_get_contents($url);
    $response = json_decode($response);
    
    if(empty($response)){
        return;
    }

    $price_dictionary = [];

    foreach($response as $currency)
    {
        $price_dictionary[$currency->id] = $currency->price;
    }

    $answer = "";
    $sum = 0;
    $aquisition_sum = 0;
    foreach($data as $currency){
        $answer .= $currency['ticker'].' -  '.$currency['amount'].'  - $'.number_format(($currency['amount'] * $price_dictionary[$currency['ticker']]),2,',',' ').'%0a';
        $sum += $currency['amount'] * $price_dictionary[$currency['ticker']];
        $aquisition_sum += $currency['amount'] * $currency['price'];
    }

    $answer .= '%0aИтого: $'.number_format($sum,2,',',' ');
    $answer .= '%0aИзначально: $'.number_format($aquisition_sum,2,',',' ');
    $difference = $sum - $aquisition_sum;
    $answer .= '%0aРазница: ';
    if($difference >= 0)
    {
        $answer .= '📈';
    }
    else
    {
        $answer .= '📉';
    }
    $answer .= '$'.number_format($difference,2,',',' ');
    send_message($chat_id,$answer);
}

function send_message($chat_id,$message)
{
    global $path;
    $url = $path."/sendMessage?chat_id=".$chat_id."&text=".$message;
    return file_get_contents($url);
}

function handle_commands($update)
{
    $chat_id = $update["message"]["chat"]["id"];
    $message = $update["message"]["text"];
    $fname = $update["message"]["chat"]['first_name'];
    if(str_contains($message,'/')){
        update_flag($chat_id,null);
        switch($message){
            case '/start': start_command($fname,$chat_id); break;
            case '/buy': buy_command($chat_id); break;
            case '/sell': sell_command($chat_id); break;
            case '/portfolio': portfolio_command($chat_id); break;
            case '/about': send_message($chat_id,
            'Бот позволяет вам отслеживать ваше криптовалютное портфолио в Telegram.%0a%0aБот использует nomics API для запросов о текущих ценах.'); break;
            case '/help':
                $answer = "Список команд:%0a%0a"
                .get_commands();
                send_message($chat_id,$answer);
                break;
        }
    }
    else
    {
        $flag = select_flag($chat_id)[0][0];
        if($flag != null){
            if(str_contains($flag,'buy')){
                buy_command($chat_id,$message,$flag);
            }
            else if(str_contains($flag,'sell'))
            {
                sell_command($chat_id,$message,$flag);
            }
        }
    }
}

function get_commands(){
    return "/portfolio - посмотреть портфолио%0a"
    ."/buy - добавить позицию%0a"
    ."/sell - убрать позицию%0a%0a"
    ."/about - о боте";
}