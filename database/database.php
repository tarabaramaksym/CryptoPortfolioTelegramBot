<?php

require_once('config.php');

function new_portfolio_position($telegram_id)
{
    $link = mysqli_connect(HOST,USER, PASS,DB);
    $sql = "INSERT INTO temp_positions (user_id)
            VALUES 
            ((SELECT id FROM users WHERE telegram_id = '".$telegram_id."'))";
    mysqli_query($link, $sql);
    mysqli_close($link);
}

function update_position($telegram_id,$field,$value)
{
    $link = mysqli_connect(HOST,USER, PASS,DB);
    $sql = "UPDATE temp_positions SET ".$field." = '".$value
    ."' WHERE user_id = (SELECT id FROM users WHERE telegram_id = '".$telegram_id."')";
    mysqli_query($link, $sql);
    mysqli_close($link);
}

function delete_position($id)
{
    $link = mysqli_connect(HOST,USER, PASS,DB);
    $sql = "DELETE FROM portfolio_positions WHERE id = '".$id."'";
    mysqli_query($link, $sql);
    mysqli_close($link);
}

function finalize_position($telegram_id)
{
    $link = mysqli_connect(HOST,USER, PASS,DB);
    $sql = "SELECT * FROM temp_positions WHERE user_id = (SELECT id FROM users WHERE telegram_id = '".$telegram_id."')";
    $res = mysqli_query($link, $sql);
    $data = mysqli_fetch_all($res);
    $sql = "INSERT INTO portfolio_positions (user_id,ticker,price,amount) VALUES (".$data[0][0].", '".$data[0][1]."',".$data[0][2].", ".$data[0][3].")";
    mysqli_query($link, $sql);
    mysqli_close($link);
}

function select_portfolio($telegram_id)
{
    $link = mysqli_connect(HOST,USER, PASS,DB);
        $sql = "SELECT * FROM portfolio_positions WHERE user_id = (SELECT id FROM users WHERE telegram_id = '".$telegram_id."')";
    $res = mysqli_query($link, $sql);
    $data = mysqli_fetch_all($res,MYSQLI_ASSOC);
    mysqli_close($link);
    return $data;
}

function select_flag($telegram_id)
{
    $link = mysqli_connect(HOST,USER, PASS,DB);
    $sql = "SELECT flag FROM users WHERE telegram_id = ".$telegram_id;
    $res = mysqli_query($link, $sql);
    $data = null;
    if($res != false)
    {
        $data = mysqli_fetch_all($res);
    }
    mysqli_close($link);
    return $data;
}

function update_flag($telegram_id, $flag)
{
    $link = mysqli_connect(HOST,USER, PASS,DB);
    $sql = "UPDATE users SET flag = '".$flag."' WHERE telegram_id = ".$telegram_id;
    $res = mysqli_query($link, $sql);
    mysqli_close($link);
}

function on_start($telegram_id)
{
    $link = mysqli_connect(HOST,USER, PASS,DB);
    $sql = "INSERT INTO users (telegram_id) VALUES ('".$telegram_id."')";
    mysqli_query($link, $sql);
    mysqli_close($link);
}

?>